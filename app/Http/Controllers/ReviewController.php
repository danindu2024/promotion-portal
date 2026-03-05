<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\StagingData;
use App\Models\MainRegistry;
use App\Helpers\Current;

class ReviewController extends Controller
{
    /**
     * Get the queue of pending batches.
     */
    public function pending()
    {
        // Get unique pending batch IDs and their counts, paginated
        $query = StagingData::where('validation_status', StagingData::STATUS_PENDING)
            ->select('batch_id', DB::raw('MIN(created_at) as batch_created_at'), DB::raw('COUNT(id) as record_count'))
            ->groupBy('batch_id')
            ->orderBy('batch_created_at', 'asc');
            
        $paginated = $query->paginate(50);
        
        // Enhance with representative data from the first record in the batch
        $paginated->getCollection()->transform(function ($batch) {
            $firstRecord = StagingData::with('uploader')
                ->where('batch_id', $batch->batch_id)
                ->where('validation_status', StagingData::STATUS_PENDING)
                ->first();
                
            return [
                'batch_id' => $batch->batch_id,
                'created_at' => $batch->batch_created_at,
                'record_count' => $batch->record_count,
                'uploader' => $firstRecord ? $firstRecord->uploader : null,
                'submission_type' => $firstRecord ? $firstRecord->submission_type : 'N/A',
                'category' => $firstRecord ? ($firstRecord->data_payload['category'] ?? 'N/A') : 'N/A',
                'district' => $firstRecord ? ($firstRecord->data_payload['district'] ?? 'N/A') : 'N/A',
                'ds_division' => $firstRecord ? ($firstRecord->data_payload['ds_division'] ?? 'N/A') : 'N/A',
            ];
        });

        return response()->json($paginated);
    }

    /**
     * Get all pending rows for a specific batch.
     */
    public function batchDetails($batchId)
    {
        $records = StagingData::where('batch_id', $batchId)
            ->where('validation_status', StagingData::STATUS_PENDING)
            ->with(['uploader', 'targetRecord'])
            ->orderBy('id', 'asc')
            ->get();

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
        $records = StagingData::where('batch_id', $batchId)
            ->where('validation_status', StagingData::STATUS_PENDING)
            ->get();

        if ($records->isEmpty()) {
            return response()->json(['message' => 'No pending records found in this batch.'], 400);
        }

        $approvedCount = 0;
        $errors = [];

        DB::transaction(function () use ($records, &$approvedCount, &$errors) {
            foreach ($records as $staging) {
                $payload = $staging->data_payload;

                // Final duplicate guard for this specific row
                $contactNumber = $payload['contact_number'] ?? null;
                if ($contactNumber && MainRegistry::where('contact_number', $contactNumber)->exists()) {
                    $errors[] = "Row ID {$staging->id} ({$contactNumber}) was already approved elsewhere and skipped.";
                    continue; // Skip this row instead of failing the whole batch
                }

                $payload['approved_by'] = Current::id();
                $payload['approved_at'] = now();

                if ($staging->submission_type === 'NEW') {
                    MainRegistry::create($payload);
                } elseif ($staging->submission_type === 'UPDATE') {
                    if (!$staging->target_record_id) {
                        $errors[] = "Row ID {$staging->id} missing target_record_id.";
                        continue;
                    }
                    $mainRecord = MainRegistry::find($staging->target_record_id);
                    if ($mainRecord) {
                        $mainRecord->update($payload);
                    } else {
                        $errors[] = "Row ID {$staging->id} target record not found.";
                        continue;
                    }
                }

                $staging->approve();
                $approvedCount++;
            }
        });

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

        $staging = StagingData::findOrFail($id);

        if ($staging->validation_status !== StagingData::STATUS_PENDING) {
            return response()->json(['message' => 'Record is not in pending state.'], 400);
        }

        $staging->reject($request->input('reason'));

        return response()->json(['message' => 'Record rejected.']);
    }
}
