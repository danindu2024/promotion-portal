<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Validator;
use App\Models\MainRegistry;

use App\Models\StagingData;
use App\Services\RegistryValidator;
use App\Helpers\Current;

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

        // DB Duplicate Check — main_registry
        $exists = MainRegistry::where('contact_number', $data['contact_number'])->exists();
        if ($exists) {
            return response()->json([
                'message' => 'A record with this contact number already exists. Use the Update Data tab to modify existing records.',
                'errors' => ['contact_number' => ['Contact number already exists.']]
            ], 409);
        }
        
        // Also check staging_data for pending duplicates
        $pendingDuplicate = StagingData::where('validation_status', StagingData::STATUS_PENDING)
            ->whereJsonContains('data_payload->contact_number', $data['contact_number']) //modern sql can store json object in columns
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

        // efficient for <1000 raws. Not suitable for larger files
        $rows = \Maatwebsite\Excel\Facades\Excel::toArray(new class implements \Maatwebsite\Excel\Concerns\ToArray {
            public function array(array $array) {}
        }, $request->file('file'))[0]; // Get the first sheet

        // Discard header row — column order is fixed by template
        array_shift($rows);

        // Initializing Counters and Storage
        $totalRows = 0;
        $validCount = 0; // count of valid rows — no need to store full payloads
        $invalidRows = [];
        $contactNumbersInFile = []; // use to check duplicate in file
        $uniqueNumbersForDbCheck = []; // use to check duplicate in db

        // In-file duplicate check & structural mapping
        foreach ($rows as $index => $row) {
            // remove completely empty rows
            if (empty(array_filter($row, function($value) { return $value !== null && $value !== ''; }))) {
                unset($rows[$index]);
                continue;
            }
            $totalRows++;

            // create data array with mapped values
            $data = [
                'category' => $row[0] ?? null,
                'full_name' => $row[1] ?? null,
                'national_id_number' => $row[2] ?? null,
                'contact_number' => $this->normalizePhoneNumber($row[3] ?? null),
                'province' => $row[4] ?? null,
                'district' => $row[5] ?? null,
                'ds_division' => $row[6] ?? null,
                'field_of_work' => isset($row[7]) && (string)$row[7] !== '' ? $row[7] : null,
                'age' => isset($row[8]) && (string)$row[8] !== '' ? (int)$row[8] : null,
                'address' => $row[9] ?? null,
                'whatsapp_number' => $this->normalizePhoneNumber($row[10] ?? null),
                'email' => $row[11] ?? null,
                'contact_person' => isset($row[12]) && (string)$row[12] !== '' ? $row[12] : null,
                'members_count' => isset($row[13]) && (string)$row[13] !== '' ? (int)$row[13] : null,
                'employees_count' => isset($row[14]) && (string)$row[14] !== '' ? (int)$row[14] : null,
            ];

            $contactNumber = $data['contact_number'];

            // remove row if contact number is missing
            if (empty($contactNumber)) {
                $data['error'] = 'Contact number is missing.';
                $invalidRows[] = $data;
                unset($rows[$index]);
                continue;
            }

            // remove row if contact number is duplicate in file
            if (in_array($contactNumber, $contactNumbersInFile)) {
                $data['error'] = 'Duplicate contact number found within this Excel file.';
                $invalidRows[] = $data;
                unset($rows[$index]);
                continue;
            }

            // add contact number to arrays
            $contactNumbersInFile[] = $contactNumber;
            $uniqueNumbersForDbCheck[] = $contactNumber;

            // Store mapped data instead of raw numeric array for later
            $rows[$index] = $data; 
        }

        // Database duplicate check — main_registry
        $existingNumbers = !empty($uniqueNumbersForDbCheck)
            ? MainRegistry::whereIn('contact_number', $uniqueNumbersForDbCheck)
                ->pluck('contact_number') // only get the contact numbers
                ->toArray()
            : [];

        // Check staging_data for pending duplicates
        // Use SQL JSON extraction to avoid loading full payloads into memory
        $pendingNumbers = !empty($uniqueNumbersForDbCheck)
            ? StagingData::where('validation_status', StagingData::STATUS_PENDING)
                ->whereIn('data_payload->contact_number', $uniqueNumbersForDbCheck)
                ->get(['data_payload'])
                ->pluck('data_payload.contact_number')
                ->filter()
                ->toArray()
            : [];
        $existingNumbers = array_unique(array_merge($existingNumbers, $pendingNumbers));

        $batchId = 'BATCH-' . uniqid('', true);
        $stagedInsertData = [];

        // Full Validation for remaining rows
        foreach ($rows as $data) {
            // Skip empty rows
            if (!is_array($data) || !array_key_exists('contact_number', $data)) {
                continue;
            }

            if (in_array($data['contact_number'], $existingNumbers)) {
                $data['error'] = 'Contact number already exists in either pending or main database';
                $invalidRows[] = $data;
                continue;
            }

            // Strip cross-category null fields before validation to prevent
            // the 'prohibited' rule from firing on empty template columns.
            $category = $data['category'] ?? null;
            if ($category === 'Self-Employed') {
                unset($data['contact_person'], $data['members_count']);
            } elseif ($category === 'Trade') {
                unset($data['field_of_work'], $data['age'], $data['employees_count']);
            }

            // Run through Category Validator
            $validator = RegistryValidator::validate($data);

            if ($validator->fails()) {
                $data['error'] = implode(' | ', $validator->errors()->all()); // join multiple array elements to single string
                $invalidRows[] = $data;
            } else {
                // Ensure correct types before insert
                $stagedInsertData[] = [
                    'batch_id' => $batchId,
                    'data_payload' => json_encode($data),
                    'validation_status' => StagingData::STATUS_PENDING,
                    'submission_type' => 'NEW',
                    'uploaded_by' => Current::id(),
                    'created_at' => now(),
                    'updated_at' => now()
                ];
                $validCount++;
            }
        }

        // Step 4: Bulk Insert valid records
        if (!empty($stagedInsertData)) {
            StagingData::insert($stagedInsertData);
        }

        return response()->json([
            'summary' => [
                'total_processed' => $totalRows,
                'valid_count' => $validCount,
                'invalid_count' => count($invalidRows),
            ],
            'invalid_rows' => $invalidRows,
            // Only provide batch_id if records were actually staged
            'batch_id' => $validCount > 0 ? $batchId : null
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
     * Get a specific rejected record by ID
     */
    public function getRejectedRecord($id)
    {
        $record = StagingData::where('uploaded_by', Current::id())
            ->where('validation_status', StagingData::STATUS_REJECTED)
            ->findOrFail($id);
            
        return response()->json($record);
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
        
        // Check for duplicates in main_registry
        $query = MainRegistry::where('contact_number', $data['contact_number']);
        // exclude the target record if this was an update
        if ($staging->submission_type === 'UPDATE' && $staging->target_record_id) {
            $query->where('id', '!=', $staging->target_record_id);
        }
        
        if ($query->exists()) {
            return response()->json([
                'message' => 'A record with this contact number already exists.',
                'errors' => ['contact_number' => ['Contact number already exists.']]
            ], 409);
        }

        // Check staging_data for pending duplicates (excluding this very record)
        $pendingDuplicate = StagingData::where('validation_status', StagingData::STATUS_PENDING)
            ->where('id', '!=', $staging->id)
            ->whereJsonContains('data_payload->contact_number', $data['contact_number'])
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
