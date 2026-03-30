<?php

namespace App\Imports;

use App\Models\MainRegistry;
use App\Models\StagingData;
use App\Services\RegistryValidator;
use App\Helpers\Current;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Illuminate\Support\Facades\Log;

class RegistryImport implements ToCollection, WithChunkReading
{
    public $totalRows = 0;
    public $validCount = 0;
    public $invalidRows = [];
    public $batchId;
    
    // Track contact numbers within the entire file to find internal duplicates
    protected $contactNumbersInFile = [];

    // Track whether the header row has been skipped (instance property, not static)
    protected $headerSkipped = false;

    public function __construct()
    {
        $this->batchId = 'BATCH-' . uniqid('', true);
    }

    /**
     * Process a chunk of rows.
     */
    public function collection(Collection $rows)
    {
        // Skip header row on the first chunk only
        if (!$this->headerSkipped) {
            $rows->shift();
            $this->headerSkipped = true;
        }

        $uniqueNumbersForDbCheck = [];
        $chunkDataRows = [];

        // 1. Initial mapping and in-file duplicate check for the current chunk
        foreach ($rows as $row) {
            // Skip completely empty rows
            $rowData = $row->toArray();
            if (empty(array_filter($rowData, function($value) { return $value !== null && (string)$value !== ''; }))) {
                continue;
            }

            $this->totalRows++;

            // Create data array with mapped values
            $data = [
                'category'           => $rowData[0] ?? null,
                'full_name'          => $rowData[1] ?? null,
                'national_id_number' => $this->normalizeNationalId($rowData[2] ?? null),
                'contact_number'     => $this->normalizePhoneNumber($rowData[3] ?? null),
                'province'           => $rowData[4] ?? null,
                'district'           => $rowData[5] ?? null,
                'ds_division'        => $rowData[6] ?? null,
                'field_of_work'      => isset($rowData[7]) && (string)$rowData[7] !== '' ? $rowData[7] : null,
                'age'                => isset($rowData[8]) && (string)$rowData[8] !== '' ? (int)$rowData[8] : null,
                'address'            => $rowData[9] ?? null,
                'whatsapp_number'    => $this->normalizePhoneNumber($rowData[10] ?? null),
                'email'              => $rowData[11] ?? null,
                'contact_person'     => isset($rowData[12]) && (string)$rowData[12] !== '' ? $rowData[12] : null,
                'members_count'      => isset($rowData[13]) && (string)$rowData[13] !== '' ? (int)$rowData[13] : null,
                'employees_count'    => isset($rowData[14]) && (string)$rowData[14] !== '' ? (int)$rowData[14] : null,
            ];

            foreach ($data as $key => $value) {
                if (is_string($value)) {
                    $data[$key] = trim($value);
                }
            }

            $contactNumber = $data['contact_number'];

            // Validate missing contact number
            if (empty($contactNumber)) {
                $data['error'] = 'Contact number is missing.';
                $this->invalidRows[] = $data;
                continue;
            }

            // Identify internal duplicates (across the whole file)
            if (in_array($contactNumber, $this->contactNumbersInFile)) {
                $data['error'] = 'Duplicate contact number found within this Excel file.';
                $this->invalidRows[] = $data;
                continue;
            }

            $this->contactNumbersInFile[] = $contactNumber;
            $uniqueNumbersForDbCheck[] = $contactNumber;
            $chunkDataRows[] = $data;
        }

        if (empty($chunkDataRows)) {
            return;
        }

        // Wrap the DB duplicate check and bulk insert in a transaction per chunk.
        // A failed chunk is fully rolled back — no partial data is left in the DB.
        try {
            DB::transaction(function () use ($chunkDataRows, $uniqueNumbersForDbCheck) {
                // 2. Database duplicate check for the current chunk (Category-Aware)
                $existingRecords = MainRegistry::whereIn('contact_number', $uniqueNumbersForDbCheck)
                    ->get(['contact_number', 'category'])
                    ->map(fn($r) => "{$r->contact_number}:{$r->category}")
                    ->toArray();

                $pendingRecords = StagingData::where('validation_status', StagingData::STATUS_PENDING)
                    ->whereIn('data_payload->contact_number', $uniqueNumbersForDbCheck)
                    ->get(['data_payload'])
                    ->map(fn($s) => "{$s->data_payload['contact_number']}:{$s->data_payload['category']}")
                    ->toArray();

                $existingPairs = array_unique(array_merge($existingRecords, $pendingRecords));

                // 3. Full Validation and Insertion
                $stagedInsertData = [];
                foreach ($chunkDataRows as $data) {
                    $pair = "{$data['contact_number']}:{$data['category']}";
                    if (in_array($pair, $existingPairs)) {
                        $data['error'] = 'Contact number already exists for this category in either pending or main database';
                        $this->invalidRows[] = $data;
                        continue;
                    }

                    // Strip cross-category fields before validation
                    $category = $data['category'] ?? null;
                    if ($category === 'Self-Employed') {
                        unset($data['contact_person'], $data['members_count']);
                    } elseif ($category === 'Trade') {
                        unset($data['field_of_work'], $data['age'], $data['employees_count']);
                    }

                    // Category Validator
                    $validator = RegistryValidator::validate($data);

                    if ($validator->fails()) {
                        $data['error'] = implode(' | ', $validator->errors()->all());
                        $this->invalidRows[] = $data;
                    } else {
                        $stagedInsertData[] = [
                            'batch_id'          => $this->batchId,
                            'data_payload'      => json_encode($data),
                            'validation_status' => StagingData::STATUS_PENDING,
                            'submission_type'   => 'NEW',
                            'uploaded_by'       => Current::id(),
                            'created_at'        => now(),
                            'updated_at'        => now()
                        ];
                        $this->validCount++;
                    }
                }

                if (!empty($stagedInsertData)) {
                    StagingData::insert($stagedInsertData);
                }
            });
        } catch (\Throwable $e) {
            Log::error('RegistryImport chunk failed', ['error' => $e->getMessage()]);
            $this->dbError = 'A database error occurred while processing your upload. The operation has been rolled back. Please try again in a few minutes.';
        }
    }

    /** Set if a fatal DB error occurs mid-import, so the controller can surface it */
    public $dbError = null;

    public function chunkSize(): int
    {
        return 500;
    }

    /**
     * Logic from RegistryController
     */
    private function normalizePhoneNumber($number)
    {
        if (empty($number)) return null;
        $number = trim((string) $number);
        $number = preg_replace('/\D/', '', $number);

        if (str_starts_with($number, '94') && strlen($number) === 11) {
            $number = '0' . substr($number, 2);
        } else {
            if (strlen($number) === 9 && !str_starts_with($number, '0')) {
                $number = '0' . $number;
            }
        }
        return $number;
    }

    private function normalizeNationalId($id)
    {
        if (empty($id)) return null;

        // If Excel loaded it as float (e.g. 200228002270.0), convert to plain string without scientific notation
        if (is_numeric($id)) {
            return number_format((float) $id, 0, '', '');
        }

        return trim((string) $id);
    }
}
