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
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $record = MainRegistry::findOrFail($id);
        $data = $request->all();

        // Force the category to remain the same as the existing record to prevent structural issues, 
        // or allow change if we handle it properly. The easiest is to use the existing category if not provided.
        $data['category'] = $data['category'] ?? $record->category;

        // Strip cross-category null fields to prevent 'prohibited' rule from firing on empty fields
        if ($data['category'] === 'Self-Employed') {
            unset($data['contact_person'], $data['members_count']);
        } elseif ($data['category'] === 'Trade') {
            unset($data['field_of_work'], $data['age'], $data['employees_count']);
        }

        // Validate using the shared validator
        $validator = RegistryValidator::validate($data);
        $validator->validate();

        // Check for duplicate contact_number if changed
        if (isset($data['contact_number']) && $data['contact_number'] !== $record->contact_number) {
            $exists = MainRegistry::where('contact_number', $data['contact_number'])
                ->where('category', $data['category'])
                ->where('id', '!=', $id)
                ->exists();
            if ($exists) {
                return response()->json([
                    'message' => "A record for this contact number as {$data['category']} already exists.",
                    'errors' => ['contact_number' => ['Contact number already exists for this category.']]
                ], 409);
            }
        }

        $record->fill($data);
        $dirtyFields = array_keys($record->getDirty());
        $oldValues = array_intersect_key($record->getOriginal(), $record->getDirty());

        $record->save();

        if (count($dirtyFields) > 0) {
            Logger::log('REGISTRY_DIRECT_EDIT', "Admin directly edited record", 'REGISTRY', "Record ID: {$record->id}", [
                'changed_fields' => $dirtyFields,
                'old_values' => $oldValues
            ]);
        }

        return response()->json([
            'message' => 'Record updated successfully.',
            'record' => $record
        ]);
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
