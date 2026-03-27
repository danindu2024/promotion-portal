<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Validator;
use App\Models\MainRegistry;

use App\Models\StagingData;
use App\Services\RegistryValidator;
use App\Helpers\Current;
use App\Imports\RegistryImport;
use Maatwebsite\Excel\Facades\Excel;

class RegistryController extends Controller
{
    /**
     * Submit a single new record for Maker-Checker review.
     */
    public function storeSingle(Request $request)
    {
        $data = $request->all();

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
            'validation_status' => StagingData::STATUS_PENDING,
            'submission_type' => 'NEW',
            'uploaded_by' => Current::id(), // Use mocked user until real Auth
        ]);

        return response()->json([
            'message' => 'Record submitted for review successfully.',
            'staging_id' => $staging->id
        ], 201);
    }

    /**
     * Download the CSV template for bulk uploads
     */
    public function downloadTemplate()
    {
        $headers = [
            'Category', 'Full Name', 'National ID Number', 'Contact Number', 'Province', 'District', 'DS Division', 
            'Field of Work', 'Age', 'Address', 'WhatsApp Number', 'Email Address', 'Contact Person', 'Members Count', 'Employees Count'
        ];

        $callback = function() use ($headers) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $headers);
            fputcsv($file, ['Self-Employed', 'John Doe', '199012345678', '0771234567', 'Western', 'Colombo', 'Colombo', 'Information Technology and Modern Services', '30', '123 Main St', '0771234567', 'john@example.com', '', '', '5']);
            fputcsv($file, ['Trade', 'Acme Corp', '198512345678', '0719876543', 'Central', 'Kandy', 'Kandy', '', '', '456 Market St', '', '', 'Jane Smith', '10', '']);
            fclose($file);
        };

        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Content-Disposition' => 'attachment; filename="registry_upload_template.csv"',
        ]);
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
}
