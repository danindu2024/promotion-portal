<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MainRegistry;
use App\Services\RegistryValidator;
use App\Helpers\Current;
use App\Helpers\Logger;

class MainRegistryController extends Controller
{
    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $record = MainRegistry::with(['approver', 'deleter'])->findOrFail($id);
        return response()->json($record);
    }

    /**
     * Soft delete the specified resource.
     */
    public function destroy(Request $request, $id)
    {
        $record = MainRegistry::findOrFail($id);

        if ($record->is_deleted) {
            return response()->json(['message' => 'Record is already deleted.'], 400);
        }

        $reason = $request->input('reason', 'Deleted via dashboard');

        $record->update([
            'is_deleted' => true,
            'deleted_at' => now(),
            'deleted_by' => Current::id(),
            'deletion_reason' => $reason,
        ]);

        Logger::log('REGISTRY_DELETED', "Admin soft-deleted record", 'REGISTRY', "Record ID: {$record->id}", [
            'reason' => $reason
        ]);

        return response()->json([
            'message' => 'Record deleted successfully.'
        ]);
    }
}
