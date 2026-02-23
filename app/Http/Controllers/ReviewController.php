<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\StagingData;
use App\Models\MainRegistry;
use App\Helpers\Current;

class ReviewController extends Controller
{
    /**
     * Get the queue of pending records.
     */
    public function pending()
    {
        $records = StagingData::where('validation_status', StagingData::STATUS_PENDING)
            ->with(['uploader', 'targetRecord'])
            ->orderBy('created_at', 'asc')
            ->paginate(50);

        return response()->json($records);
    }

    /**
     * Approve a pending record and commit it to Main Registry.
     */
    public function approve($id)
    {
        $staging = StagingData::findOrFail($id);

        if ($staging->validation_status !== StagingData::STATUS_PENDING) {
            return response()->json(['message' => 'Record is not in pending state.'], 400);
        }

        $payload = $staging->data_payload;

        if ($staging->submission_type === 'NEW') {
            // Final duplicate guard — prevent race conditions
            $contactNumber = $payload['contact_number'] ?? null;
            if ($contactNumber && MainRegistry::where('contact_number', $contactNumber)->exists()) {
                return response()->json([
                    'message' => 'Cannot approve: a record with this contact number was already approved by another validator.',
                ], 409);
            }

            $payload['approved_by'] = Current::id();
            $payload['approved_at'] = now();
            MainRegistry::create($payload);
        } elseif ($staging->submission_type === 'UPDATE') {
            $mainRecord = MainRegistry::findOrFail($staging->target_record_id);
            // Optional: Save $mainRecord state to AuditLogs here
            
            $payload['approved_by'] = Current::id();
            $payload['approved_at'] = now();
            $mainRecord->update($payload);
        }

        // Mark as approved
        $staging->approve();

        return response()->json(['message' => 'Record approved successfully.']);
    }

    /**
     * Reject a pending record.
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
