<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\StagingData;
use App\Models\MainRegistry;
use App\Helpers\Current;
use App\Helpers\Logger;

class ReviewController extends Controller
{
    /**
     * Get the queue of pending batches.
     */
    public function pending()
    {
        $user = Current::user();
        $query = StagingData::where('validation_status', StagingData::STATUS_PENDING);

        if ($user && $user->access_level === 'validator' && !empty($user->district) && $user->district !== 'All') {
            $query->whereHas('uploader', function ($q) use ($user) {
                $q->where('district', $user->district);
            });
        }

        // Get unique pending batch IDs, their counts, and the MIN(id) as a stable representative row to avoid N+1 queries later.
        $paginated = $query->select(
                'batch_id',
                DB::raw('MIN(created_at) as batch_created_at'),
                DB::raw('COUNT(id) as record_count'),
                DB::raw('MIN(id) as representative_id')  // stable representative row
            )
            ->groupBy('batch_id')
            ->orderBy('batch_created_at', 'asc')
            ->paginate(50);

        // Fetch all representative records + their uploaders in ONE query
        $representativeIds = $paginated->getCollection()->pluck('representative_id');
        $representatives = StagingData::with('uploader')
            ->whereIn('id', $representativeIds)
            ->get()
            ->keyBy('id'); // keyed by id for O(1) lookup

            //map each batch data
        $paginated->getCollection()->transform(function ($batch) use ($representatives) {
            $rep = $representatives->get($batch->representative_id);

            return [
                'batch_id'        => $batch->batch_id,
                'created_at'      => $batch->batch_created_at,
                'record_count'    => $batch->record_count,
                'uploader'        => $rep?->uploader, // N/A is not added as this is object type
                'submission_type' => $rep?->submission_type ?? 'N/A',
                'category'        => $rep?->data_payload['category'] ?? 'N/A',
                'district'        => $rep?->data_payload['district'] ?? 'N/A',
                'ds_division'     => $rep?->data_payload['ds_division'] ?? 'N/A',
            ];
        });

        return response()->json($paginated);
    }

    /**
     * Get all pending rows for a specific batch.
     */
    public function batchDetails($batchId)
    {
        $user = Current::user();
        $query = StagingData::where('batch_id', $batchId)
            ->where('validation_status', StagingData::STATUS_PENDING)
            ->with(['uploader', 'targetRecord'])
            ->orderBy('id', 'asc');

        if ($user && strtolower(trim($user->access_level)) === 'validator' && !empty($user->district) && $user->district !== 'All') {
            $query->whereHas('uploader', function ($q) use ($user) {
                $q->where('district', $user->district);
            });
        }

        $records = $query->get();

        if ($records->isEmpty()) {
            return response()->json(['message' => 'Batch not found or has no pending records.'], 404);
        }

        return response()->json([
            'batch_id' => $batchId,
            'records' => $records
        ]);
    }

    /**
     * Approve all remaining pending rows in a batch.
     */
    public function approveBatch($batchId)
    {
        $user = Current::user();
        $query = StagingData::where('batch_id', $batchId)
            ->where('validation_status', StagingData::STATUS_PENDING);

        if ($user && strtolower(trim($user->access_level)) === 'validator' && !empty($user->district) && $user->district !== 'All') {
            $query->whereHas('uploader', function ($q) use ($user) {
                $q->where('district', $user->district);
            });
        }

        $records = $query->get();

        if ($records->isEmpty()) {
            return response()->json(['message' => 'No pending records found in this batch.'], 400);
        }

        $approvedCount = 0;
        $errors = [];

        DB::transaction(function () use ($records, &$approvedCount, &$errors) {
            foreach ($records as $staging) {
                /** @var \App\Models\StagingData $staging */
                $payload = $staging->data_payload;

                // Final duplicate guard — auto-reject duplicates so they don't stay PENDING
                $contactNumber = $payload['contact_number'] ?? null;
                $category = $payload['category'] ?? null;
                
                $duplicateQuery = MainRegistry::where('contact_number', $contactNumber)
                    ->where('category', $category);
                
                // exclude self id checking, if the record is update type
                if ($staging->submission_type === 'UPDATE') {
                    $duplicateQuery->where('id', '!=', $staging->target_record_id);
                }

                if ($contactNumber && $duplicateQuery->exists()) {
                    $reason = "Auto-rejected: contact number {$contactNumber} for category {$category} was already approved by another validator.";
                    $staging->reject($reason);
                    $errors[] = "Row ID {$staging->id} ({$contactNumber}): {$reason}";
                    continue;
                }

                // target guard for UPDATE — auto-reject so it doesn't stay PENDING
                if ($staging->submission_type === 'UPDATE' && !$staging->target_record_id) {
                    $reason = 'Auto-rejected: UPDATE submission is missing a target_record_id.';
                    $staging->reject($reason);
                    $errors[] = "Row ID {$staging->id}: {$reason}";
                    continue;
                }

                $payload['approved_by'] = Current::id();
                $payload['approved_at'] = now();

                if ($staging->submission_type === 'NEW') {
                    MainRegistry::create($payload);
                } elseif ($staging->submission_type === 'UPDATE') {
                    $mainRecord = MainRegistry::find($staging->target_record_id);
                    if (!$mainRecord) {
                        $reason = 'Auto-rejected: target record no longer exists in main registry.';
                        $staging->reject($reason);
                        $errors[] = "Row ID {$staging->id}: {$reason}";
                        continue;
                    }

                    // Optimization: Identify only dirty fields to minimize log payload
                    $mainRecord->fill($payload);
                    $oldValues = array_intersect_key($mainRecord->getOriginal(), $mainRecord->getDirty());
                    $dirtyFields = array_keys($mainRecord->getDirty());

                    $mainRecord->save();

                    // Log the update with original values of changed fields to database
                    Logger::log('REGISTRY_UPDATE_APPROVED', "Approved update for record", 'REGISTRY', json_encode($oldValues), [
                        'staging_id' => $staging->id,
                        'target_id' => $staging->target_record_id,
                        'changed_fields' => $dirtyFields
                    ]);
                }

                $staging->approve();
                $approvedCount++;
            }
        });

        // Log general batch approval event
        Logger::log('VALIDATION_BATCH_APPROVED', "Approved batch of records", 'REVIEW', "Batch ID: {$batchId}", [
            'approved_count' => $approvedCount,
            'skipped_count' => count($errors)
        ]);

        $message = "Successfully approved $approvedCount records.";
        if (count($errors) > 0) {
            return response()->json([
                'message' => $message . ' Some records were skipped due to conflicts.',
                'errors' => $errors
            ], 207); // 207 Multi-Status
        }

        return response()->json(['message' => $message]);
    }

    /**
     * Reject a single pending record.
     */
    public function reject(Request $request, $id)
    {
        $request->validate([
            'reason' => 'required|string|max:1000'
        ]);

        $user = Current::user();
        $query = StagingData::where('id', $id);

        if ($user && strtolower(trim($user->access_level)) === 'validator' && !empty($user->district) && $user->district !== 'All') {
            $query->whereHas('uploader', function ($q) use ($user) {
                $q->where('district', $user->district);
            });
        }

        $staging = $query->firstOrFail();
        /** @var \App\Models\StagingData $staging */

        if ($staging->validation_status !== StagingData::STATUS_PENDING) {
            return response()->json(['message' => 'Record is not in pending state.'], 400);
        }

        $staging->reject($request->input('reason'));

        // Log rejection to database
        Logger::log('VALIDATION_REJECT', "Rejected record", 'REVIEW', "Staging ID: {$id}", [
            'reason' => $request->input('reason'),
            'category' => $staging->data_payload['category'] ?? 'N/A'
        ]);

        return response()->json(['message' => 'Record rejected.']);
    }
}
