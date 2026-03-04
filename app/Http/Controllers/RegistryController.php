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
            'batch_id' => 'SINGLE-' . time(),
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
            'Category', 'Full Name', 'Contact Number', 'Province', 'District', 'DS Division', 
            'Field of Work', 'Age', 'Address', 'WhatsApp Number', 'Email Address', 'Contact Person', 'Members Count', 'Employees Count'
        ];

        $callback = function() use ($headers) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $headers);
            fputcsv($file, ['Self-Employed', 'John Doe', '0771234567', 'Western', 'Colombo', 'Colombo', 'Information Technology and Modern Services', '30', '123 Main St', '0771234567', 'john@example.com', '', '', '5']);
            fputcsv($file, ['Trade', 'Acme Corp', '0719876543', 'Central', 'Kandy', 'Kandy', '', '', '456 Market St', '', '', 'Jane Smith', '10', '']);
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
                    $extension = strtolower($value->getClientOriginalExtension());
                    if (!in_array($extension, ['csv', 'xls', 'xlsx'])) {
                        $fail('The file must be a file of type: xlsx, xls, csv.');
                    }
                },
            ],
        ]);

        // efficient for >1000 raws. Not suitable for larger files
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
                'contact_number' => $this->normalizePhoneNumber($row[2] ?? null),
                'province' => $row[3] ?? null,
                'district' => $row[4] ?? null,
                'ds_division' => $row[5] ?? null,
                'field_of_work' => isset($row[6]) && (string)$row[6] !== '' ? $row[6] : null,
                'age' => isset($row[7]) && (string)$row[7] !== '' ? (int)$row[7] : null,
                'address' => $row[8] ?? null,
                'whatsapp_number' => $this->normalizePhoneNumber($row[9] ?? null),
                'email' => $row[10] ?? null,
                'contact_person' => isset($row[11]) && (string)$row[11] !== '' ? $row[11] : null,
                'members_count' => isset($row[12]) && (string)$row[12] !== '' ? (int)$row[12] : null,
                'employees_count' => isset($row[13]) && (string)$row[13] !== '' ? (int)$row[13] : null,
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
                ->whereIn('data_payload->contact_number', $uniqueNumbersForDbCheck) // Laravel JSON shorthand
                ->pluck('data_payload->contact_number') // only get the contact numbers
                ->filter()
                ->toArray()
            : [];
        $existingNumbers = array_unique(array_merge($existingNumbers, $pendingNumbers));

        $batchId = 'BATCH-' . time();
        $stagedInsertData = [];

        // Full Validation for remaining rows
        foreach ($rows as $data) {
            // Skip numeric-keyed rows (empty/unflagged rows)
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

        // Step 4: Bulk Insert valid records (1 Query)
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
}
