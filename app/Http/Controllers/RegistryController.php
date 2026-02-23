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

        // 1. Initial format check
        $validator = Validator::make($data, [
            'category' => 'required|in:Self-Employed,Trade',
            'contact_number' => 'required|string|regex:/^0\d{9}$/'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation Failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // 2. DB Duplicate Check — main_registry (1 DB call)
        $exists = MainRegistry::where('contact_number', $data['contact_number'])->exists();
        if ($exists) {
            return response()->json([
                'message' => 'A record with this contact number already exists. Use the Update Data tab to modify existing records.',
                'errors' => ['contact_number' => ['Contact number already exists.']]
            ], 409);
        }

        // 2b. Also check staging_data for pending duplicates
        $pendingDuplicate = StagingData::where('validation_status', StagingData::STATUS_PENDING)
            ->whereJsonContains('data_payload->contact_number', $data['contact_number'])
            ->exists();
        if ($pendingDuplicate) {
            return response()->json([
                'message' => 'A record with this contact number is already pending review.',
                'errors' => ['contact_number' => ['Contact number is already pending approval.']]
            ], 409);
        }

        // 3. Full Category-Aware Validation
        $fullValidator = RegistryValidator::validate($data);
        if ($fullValidator->fails()) {
            return response()->json([
                'message' => 'Validation Failed',
                'errors' => $fullValidator->errors()
            ], 422);
        }

        // 4. Insert to Staging
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
     * Parse Excel, validate rows, check duplicates efficiently, and stage valid rows.
     */
    public function uploadExcel(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:10000',
        ]);

        $rows = \Maatwebsite\Excel\Facades\Excel::toArray(new class implements \Maatwebsite\Excel\Concerns\ToArray {
            public function array(array $array) {}
        }, $request->file('file'))[0]; // Get the first sheet

        // Remove header row assuming first row is headers
        $headers = array_shift($rows);
        $totalRows = count($rows);

        $validRows = [];
        $invalidRows = [];
        $contactNumbersInFile = [];
        $uniqueNumbersForDbCheck = [];

        // Step 1: In-file duplicate check & structural mapping
        foreach ($rows as $index => $row) {
            // Map array columns based on known order or match against headers
            // Assuming order: Category, Full Name, Contact Number, Province, District, DS Division, Field of Work, Age, Address, Contact Person, Members Count, Employees Count
            $data = [
                'category' => $row[0] ?? null,
                'full_name' => $row[1] ?? null,
                'contact_number' => $row[2] ?? null,
                'province' => $row[3] ?? null,
                'district' => $row[4] ?? null,
                'ds_division' => $row[5] ?? null,
                'field_of_work' => $row[6] ?? null,
                'age' => isset($row[7]) && rtrim($row[7]) !== '' ? (int)$row[7] : null,
                'address' => $row[8] ?? null,
                'contact_person' => $row[9] ?? null,
                'members_count' => isset($row[10]) && rtrim($row[10]) !== '' ? (int)$row[10] : null,
                'employees_count' => isset($row[11]) && rtrim($row[11]) !== '' ? (int)$row[11] : null,
            ];

            $contactNumber = $data['contact_number'];

            if (empty($contactNumber)) {
                $data['error'] = 'Contact number is missing.';
                $invalidRows[] = $data;
                continue;
            }

            if (in_array($contactNumber, $contactNumbersInFile)) {
                $data['error'] = 'Duplicate contact number found within this Excel file.';
                $invalidRows[] = $data;
                continue;
            }

            $contactNumbersInFile[] = $contactNumber;
            $uniqueNumbersForDbCheck[] = $contactNumber;
            // Store mapped data instead of raw numeric array for later
            $rows[$index] = $data; 
        }

        // Step 2: Database duplicate check — main_registry (1 Query)
        $existingNumbers = MainRegistry::whereIn('contact_number', $uniqueNumbersForDbCheck)
            ->pluck('contact_number')
            ->toArray();

        // Step 2b: Also check staging_data for pending duplicates (1 Query)
        $pendingNumbers = StagingData::where('validation_status', StagingData::STATUS_PENDING)
            ->get()
            ->pluck('data_payload.contact_number')
            ->filter()
            ->toArray();
        $existingNumbers = array_unique(array_merge($existingNumbers, $pendingNumbers));

        $batchId = 'BATCH-' . time();
        $stagedInsertData = [];

        // Step 3: Full Validation for remaining rows
        foreach ($rows as $data) {
            // Skip rows already marked invalid or completely empty rows
            if (isset($data['error']) || !isset($data['contact_number'])) {
                continue;
            }

            if (in_array($data['contact_number'], $existingNumbers)) {
                $data['error'] = 'Contact number already exists in the system.';
                $invalidRows[] = $data;
                continue;
            }

            // Run through Category Validator
            $validator = RegistryValidator::validate($data);

            if ($validator->fails()) {
                $data['error'] = implode(' | ', $validator->errors()->all());
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
                $validRows[] = $data;
            }
        }

        // Step 4: Bulk Insert valid records (1 Query)
        if (!empty($stagedInsertData)) {
            StagingData::insert($stagedInsertData);
        }

        return response()->json([
            'summary' => [
                'total_processed' => $totalRows,
                'valid_count' => count($validRows),
                'invalid_count' => count($invalidRows),
            ],
            'invalid_rows' => $invalidRows,
            'batch_id' => $batchId
        ], 200);
    }
}
