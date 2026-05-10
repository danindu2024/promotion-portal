<?php

namespace App\Imports\Concerns;

use App\Models\MainRegistry;
use App\Models\StagingData;
use App\Services\RegistryValidator;
use App\Helpers\Current;
use App\Traits\NormalizesData;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

trait BaseRegistryImport
{
    use NormalizesData;
    /**
     * Shared processing logic for both sheets.
     * Modifies the main importer state directly.
     */
    public function processRows(Collection $mappedRows, $importer)
    {
        $uniqueNumbersForDbCheck = [];
        $chunkDataRows = [];

        foreach ($mappedRows as $data) {
            $importer->totalRows++;
            $data['failed_fields'] = []; // Track single field errors

            // Server-side trim
            foreach ($data as $key => $value) {
                if (is_string($value)) {
                    $data[$key] = trim($value);
                }
            }

            $contactNumber = $data['contact_number'];

            // throw error if the contact number is missing
            if (empty($contactNumber)) {
                $data['error'] = 'Contact number is missing.';
                $data['failed_fields'][] = 'contact_number';
                $importer->invalidRows[] = $data;
                continue;
            }

            // category aware duplicate check inside the excel sheet
            if ($importer->isContactNumberInFile($contactNumber, $data['category'])) {
                $data['error'] = 'Duplicate contact number found for this category within this Excel file.';
                $data['failed_fields'][] = 'contact_number';
                $importer->invalidRows[] = $data;
                continue;
            }

            // add unique numbers to hashmap for future reference
            $importer->addContactNumberToFile($contactNumber, $data['category']);
            $uniqueNumbersForDbCheck[] = $contactNumber;
            $chunkDataRows[] = $data;
        }

        // do nothing if the data row is empty 
        if (empty($chunkDataRows)) {
            return;
        }

        try {
            DB::transaction(function () use ($chunkDataRows, $uniqueNumbersForDbCheck, $importer) {
                // Fetch MainRegistry and map directly to keys
                $existingRecords = MainRegistry::where('is_deleted', false)
                    ->whereIn('contact_number', $uniqueNumbersForDbCheck)
                    ->get(['contact_number', 'category'])
                    ->mapWithKeys(fn($r) => ["{$r->contact_number}:{$r->category}" => true])
                    ->toArray();
                        
                // Fetch StagingData and map directly to keys
                // Using pluck() here to save memory by only fetching the JSON payload
                $pendingRecords = StagingData::where('validation_status', StagingData::STATUS_PENDING)
                    ->whereIn('data_payload->contact_number', $uniqueNumbersForDbCheck)
                    ->pluck('data_payload')
                    ->mapWithKeys(fn($payload) => ["{$payload['contact_number']}:{$payload['category']}" => true])
                    ->toArray();
                        
                // Merge the hash maps
                $existingPairs = $existingRecords + $pendingRecords;

                $stagedInsertData = [];
                foreach ($chunkDataRows as $data) {
                    // Normalize locations to Title Case before validation
                    $data['province']    = $this->normalizeLocationName($data['province'] ?? '');
                    $data['district']    = $this->normalizeLocationName($data['district'] ?? '');
                    $data['ds_division'] = $this->normalizeLocationName($data['ds_division'] ?? '');

                    $pair = "{$data['contact_number']}:{$data['category']}";

                    // Use isset() for duplicate checking
                    if (isset($existingPairs[$pair])) {
                        $data['error'] = 'Contact number already exists for this category in either pending or main database';
                        $data['failed_fields'][] = 'contact_number';
                        $importer->invalidRows[] = $data;
                        continue;
                    }

                    // Strip cross-category fields before validation
                    $category = $data['category'] ?? null;
                    if ($category === 'Self-Employed') {
                        unset($data['contact_person'], $data['members_count']);
                    } elseif ($category === 'Trade') {
                        unset($data['field_of_work'], $data['age'], $data['employees_count']);
                    }

                    $validator = RegistryValidator::validate($data);

                    if ($validator->fails()) {
                        $data['error'] = implode(' | ', $validator->errors()->all());
                        $data['failed_fields'] = array_merge($data['failed_fields'], array_keys($validator->errors()->toArray()));
                        $importer->invalidRows[] = $data;
                    } else {
                        $stagedInsertData[] = [
                            'batch_id'          => $importer->batchId,
                            'data_payload'      => json_encode($data),
                            'validation_status' => StagingData::STATUS_PENDING,
                            'submission_type'   => 'NEW',
                            'uploaded_by'       => Current::id(),
                            'created_at'        => now(),
                            'updated_at'        => now()
                        ];
                        $importer->validCount++;
                    }
                }

                if (!empty($stagedInsertData)) {
                    StagingData::insert($stagedInsertData);
                }
            });
        } catch (\Throwable $e) {
            Log::error('RegistryImport chunk failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            $importer->dbError = 'A database error occurred while processing your upload.';
        }
    }
}
