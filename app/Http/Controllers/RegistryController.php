<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Validator;
use App\Models\MainRegistry;

use App\Models\StagingData;
use App\Services\RegistryValidator;
use App\Helpers\Current;
use App\Helpers\Logger;
use App\Imports\RegistryImport;
use Maatwebsite\Excel\Facades\Excel;

class RegistryController extends Controller
{
    /*
     * Submit a single new record for validator review
     */
    public function storeSingle(Request $request)
    {
        $data = $request->all();

        // trim leading and trailing spaces from all string values
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $data[$key] = trim($value);
            }
        }

        // Initial format check
        $validator = Validator::make($data, [
            'category' => 'required|in:Self-Employed,Trade',
            'contact_number' => 'required|string|regex:/^0\d{9}$/'
        ]);

        // if fails, throw exception. Laravel will automatically convert it to 422 response
        $validator->validate();

        // DB Duplicate Check — main_registry (category-aware)
        $exists = MainRegistry::where('contact_number', $data['contact_number'])
            ->where('category', $data['category'])
            ->exists();
        if ($exists) {
            return response()->json([
                'message' => "A record for this contact number as {$data['category']} already exists.",
                'errors' => ['contact_number' => ['Contact number already exists for this category.']]
            ], 409);
        }
        
        // Also check staging_data for pending duplicates (category-aware)
        $pendingDuplicate = StagingData::where('validation_status', StagingData::STATUS_PENDING)
            ->whereJsonContains('data_payload->contact_number', $data['contact_number'])
            ->whereJsonContains('data_payload->category', $data['category'])
            ->exists();
        if ($pendingDuplicate) {
            return response()->json([
                'message' => 'A record with this contact number is already pending review.',
                'errors' => ['contact_number' => ['Contact number is already pending approval.']]
            ], 409);
        }

        // Strip cross-category null fields to prevent 'prohibited' rule from firing on empty fields
        if ($data['category'] === 'Self-Employed') {
            unset($data['contact_person'], $data['members_count']);
        } elseif ($data['category'] === 'Trade') {
            unset($data['field_of_work'], $data['age'], $data['employees_count']);
        }

        // Full Category-Aware Validation
        $fullValidator = RegistryValidator::validate($data);
        $fullValidator->validate();

        // Insert to Staging
        $staging = StagingData::create([
            'batch_id' => 'SINGLE-' . uniqid('', true),
            'data_payload' => $data,
            'submission_type' => 'NEW',
            'uploaded_by' => Current::id(),
        ]);

        // Log single submission to database
        Logger::log('REGISTRY_ENTRY', "New {$data['category']} record submitted", 'REGISTRY', "Staging ID: {$staging->id}", [
            'category' => $data['category'],
            'contact_number' => $data['contact_number']
        ]);

        return response()->json([
            'message' => 'Record submitted for review successfully.',
            'staging_id' => $staging->id
        ], 201);
    }

    /**
     * Download the Excel template for bulk uploads
     */
    public function downloadTemplate()
    {
        return Excel::download(new \App\Exports\RegistryTemplateExport, 'bulk_upload_template.xlsx');
    }


    /**
     * Parse Excel, validate rows, check duplicates efficiently, and stage valid rows.
     */

    // validate file type and size
    public function uploadExcel(Request $request)
    {
        $request->validate([
            'file' => [
                'required',
                'file',
                'max:10000', // max size set to 10mb to save storage
                function ($attribute, $value, $fail) {
                    // Check file extension
                    $extension = strtolower($value->getClientOriginalExtension());
                    if (!in_array($extension, ['csv', 'xls', 'xlsx'])) {
                        $fail('The file must be a file of type: xlsx, xls, csv.');
                        return;
                    }
                    // Check real MIME type (prevents renamed malicious files)
                    $allowedMimes = [
                        'text/csv',
                        'text/plain',
                        'application/csv',
                        'application/vnd.ms-excel',
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    ];
                    $mime = $value->getMimeType();
                    if (!in_array($mime, $allowedMimes)) {
                        $fail('The file content does not match an allowed type (csv, xls, xlsx). Got: ' . $mime);
                    }
                },
            ],
        ]);

        // Using Chunked Reading to prevent memory overloading (prevents crashes on large files)
        $import = new RegistryImport();
        Excel::import($import, $request->file('file'));

        // If a DB transaction failed mid-import, the chunk was rolled back — surface to user
        if ($import->dbError) {
            return response()->json([
                'message' => 'Upload failed due to a server error. Any data already processed has been rolled back. Please try again in a few minutes.',
            ], 503);
        }

        // Log bulk upload attempt to database
        Logger::log('REGISTRY_BULK_UPLOAD', 'Excel bulk upload processed', 'REGISTRY', "Batch ID: {$import->batchId}", [
            'total_rows' => $import->totalRows,
            'valid_count' => $import->validCount,
            'invalid_count' => count($import->invalidRows),
            'filename' => $request->file('file')->getClientOriginalName()
        ]);

        return response()->json([
            'summary' => [
                'total_processed' => $import->totalRows,
                'valid_count'     => $import->validCount,
                'invalid_count'   => count($import->invalidRows),
            ],
            'invalid_rows' => $import->invalidRows,
            'batch_id'     => $import->validCount > 0 ? $import->batchId : null
        ], 200);


    }

    /**
     * Normalize phone numbers from Excel uploads
     * Handles stripped leading zeros and Sri Lankan country codes (+94 or 94)
     */
    private function normalizePhoneNumber($number)
    {
        if (empty($number)) return null;

        // Convert to string
        $number = trim((string) $number);
        // Strip all non-digit characters (spaces, dashes, dots, plus)
        $number = preg_replace('/\D/', '', $number);

        // Handle country code 94 (11 digits)
        if (str_starts_with($number, '94') && strlen($number) === 11) {
            $number = '0' . substr($number, 2);
        }
        else {
            // Handle Excel stripped leading zero (exactly 9 digits, doesn't start with 0)
            if (strlen($number) === 9 && !str_starts_with($number, '0')) {
                $number = '0' . $number;
            }
        }

        return $number;
    }

    /**
     * Get rejected records for the current Data Entry user
     */
    public function getRejected()
    {
        $records = StagingData::where('uploaded_by', Current::id())
            ->where('validation_status', StagingData::STATUS_REJECTED)
            ->orderBy('updated_at', 'desc')
            ->paginate(15);
            
        return response()->json($records);
    }
    


    /**
     * Resubmit a corrected rejected record
     */
    public function resubmitRejected(Request $request, $id)
    {
        // Get rejected record
        $staging = StagingData::where('uploaded_by', Current::id())
            ->where('validation_status', StagingData::STATUS_REJECTED)
            ->findOrFail($id);
            
        $data = $request->all();

        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $data[$key] = trim($value);
            }
        }

        // Initial format check before expensive db checks
        $preCheck = Validator::make($data, [
            'category'       => 'required|in:Self-Employed,Trade',
            'contact_number' => 'required|string|regex:/^0\d{9}$/'
        ]);
        $preCheck->validate();
        
        // Strip cross-category null fields
        if ($data['category'] === 'Self-Employed') {
            unset($data['contact_person'], $data['members_count']);
        } elseif ($data['category'] === 'Trade') {
            unset($data['field_of_work'], $data['age'], $data['employees_count']);
        }

        // Full Category-Aware Validation
        $validator = RegistryValidator::validate($data);
        $validator->validate();
        
        // Check for duplicates in main_registry (category-aware)
        $query = MainRegistry::where('contact_number', $data['contact_number'])
            ->where('category', $data['category']);
        // exclude the target record if this was an update
        if ($staging->submission_type === 'UPDATE' && $staging->target_record_id) {
            $query->where('id', '!=', $staging->target_record_id);
        }
        
        if ($query->exists()) {
            return response()->json([
                'message' => "A record for this contact number as {$data['category']} already exists.",
                'errors' => ['contact_number' => ["Contact number already exists for category {$data['category']}."]]
            ], 409);
        }

        // Check staging_data for pending duplicates (category-aware)
        $pendingDuplicate = StagingData::where('validation_status', StagingData::STATUS_PENDING)
            ->where('id', '!=', $staging->id)
            ->whereJsonContains('data_payload->contact_number', $data['contact_number'])
            ->whereJsonContains('data_payload->category', $data['category'])
            ->exists();
            
        if ($pendingDuplicate) {
            return response()->json([
                'message' => 'A record with this contact number is already pending review.',
                'errors' => ['contact_number' => ['Contact number is already pending approval.']]
            ], 409);
        }

        // Update the record and switch back to Pending.
        // created_at is intentionally preserved — it reflects the original submission time
        // and is used by ReviewController::pending() to order batches chronologically.
        // updated_at is auto-set by Eloquent's update() to reflect this resubmission time.
        $staging->update([
            'data_payload'      => $data,
            'validation_status' => StagingData::STATUS_PENDING,
            // Keep original batch_id and submission_type
            'rejection_reason'  => null
        ]);

        return response()->json([
            'message'    => 'Record resubmitted successfully.',
            'staging_id' => $staging->id
        ], 200);
    }

    /**
     * List records from main registry that can be updated.
     * Scoped by User Location (DS Division for Data Entry, District for Validator).
     */
    public function listUpdateable(Request $request)
    {
        $user = Current::user();
        $query = MainRegistry::query();

        // 1. Enforce Location Scoping
        if ($user->access_level === 'data entry') {
            $query->where('ds_division', $user->ds_division);
        } elseif ($user->access_level === 'validator') {
            $query->where('district', $user->district);
        }

        // 2. Apply Filters (Reusing logic from AnalyticsController)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                  ->orWhere('contact_number', 'like', "%{$search}%")
                  ->orWhere('national_id_number', 'like', "%{$search}%");
            });
        }

        if ($request->filled('province')) {
            $query->where('province', $request->province);
        }
        if ($request->filled('district') && $user->access_level !== 'validator' && $user->access_level !== 'data entry') {
             $query->where('district', $request->district);
        }
        if ($request->filled('ds_division') && $user->access_level !== 'data entry') {
            $query->where('ds_division', $request->ds_division);
        }
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        // 3. Check for Pending Updates
        $query->addSelect(['main_registry.*']);
        $query->selectSub(function($q) {
            $q->from('staging_data')
              ->selectRaw('1')
              ->whereColumn('target_record_id', 'main_registry.id')
              ->where('validation_status', StagingData::STATUS_PENDING)
              ->limit(1);
        }, 'has_pending_update');

        $results = $query->orderBy('full_name', 'asc')->paginate(15);

        return response()->json($results);
    }

    /**
     * Submit an update request for an existing record.
     */
    public function submitUpdate(Request $request, $id)
    {
        $mainRecord = MainRegistry::findOrFail($id);
        $user = Current::user();

        // Security: Ensure user has permission to update this record based on location
        if ($user->access_level === 'data entry' && $mainRecord->ds_division !== $user->ds_division) {
            return response()->json(['message' => 'Unauthorized: This record is outside your assigned DS Division.'], 403);
        }
        if ($user->access_level === 'validator' && $mainRecord->district !== $user->district) {
            return response()->json(['message' => 'Unauthorized: This record is outside your assigned District.'], 403);
        }

        // Check if a pending update already exists
        $pendingExists = StagingData::where('target_record_id', $id)
            ->where('validation_status', StagingData::STATUS_PENDING)
            ->exists();
        
        if ($pendingExists) {
            return response()->json(['message' => 'This record already has a pending update request.'], 409);
        }

        $data = $request->all();

        // Server-side trim
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $data[$key] = trim($value);
            }
        }

        // Category-Aware Validation
        $data['category'] = $mainRecord->category; // Force original category
        
        // Strip cross-category null fields
        if ($data['category'] === 'Self-Employed') {
            unset($data['contact_person'], $data['members_count']);
        } elseif ($data['category'] === 'Trade') {
            unset($data['field_of_work'], $data['age'], $data['employees_count']);
        }

        $validator = RegistryValidator::validate($data);
        $validator->validate();

        // Check for contact number duplicates (excluding the current record)
        $existsInMain = MainRegistry::where('contact_number', $data['contact_number'])
            ->where('category', $data['category'])
            ->where('id', '!=', $id)
            ->exists();
        
        if ($existsInMain) {
            return response()->json([
                'message' => 'Contact number already exists for this category.',
                'errors' => ['contact_number' => ['Contact number already exists for this category.']]
            ], 409);
        }

        $staging = StagingData::create([
            'batch_id' => 'UPDATE-' . strtoupper(uniqid()),
            'data_payload' => $data,
            'submission_type' => 'UPDATE',
            'target_record_id' => $id,
            'uploaded_by' => Current::id(),
            'validation_status' => StagingData::STATUS_PENDING
        ]);

        Logger::log('REGISTRY_UPDATE_SUBMITTED', "Update request submitted for {$data['category']} record", 'REGISTRY', "Staging ID: {$staging->id} | Target ID: {$id}", [
            'category' => $data['category'],
            'contact_number' => $data['contact_number']
        ]);

        return response()->json([
            'message' => 'Update request submitted for review successfully.',
            'staging_id' => $staging->id
        ], 201);
    }
}
