<?php

namespace App\Imports\Sheets;

use App\Imports\Concerns\BaseRegistryImport;
use App\Imports\RegistryImport;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Illuminate\Support\Collection;

class SelfEmployedSheetImport implements ToCollection, WithChunkReading
{
    use BaseRegistryImport;

    protected $importer;

    public function __construct(RegistryImport $importer)
    {
        $this->importer = $importer;
    }

    public function collection(Collection $rows)
    {
        // Skip header and limit to first 1000 data rows (matching template capacity)
        $rows->shift();
        $rows = $rows->take(1000);

        $mappedRows = $rows->map(function ($row) {
            $rowData = $row->toArray();
            
            // Skip empty rows
            if (empty(array_filter($rowData, fn($v) => $v !== null && (string)$v !== ''))) {
                return null;
            }

            return [
                'category'           => $rowData[0] ?? 'Self-Employed', // fallback to Self-Employed if missing
                'full_name'          => $rowData[1] ?? null,
                'national_id_number' => $this->normalizeNationalId($rowData[2] ?? null),
                'contact_number'     => $this->normalizePhoneNumber($rowData[3] ?? null),
                'province'           => $rowData[4] ?? null,
                'district'           => $rowData[5] ?? null,
                'ds_division'        => $rowData[6] ?? null,
                'field_of_work'      => $rowData[7] ?? null,
                'age'                => $rowData[8] ?? null,
                'address'            => $rowData[9] ?? null,
                'whatsapp_number'    => $this->normalizePhoneNumber($rowData[10] ?? null),
                'email'              => $rowData[11] ?? null,
                'employees_count'    => $rowData[12] ?? null,
            ];
        })->filter();

        if ($mappedRows->isNotEmpty()) {
            $this->processRows($mappedRows, $this->importer);
        }
    }

    public function chunkSize(): int
    {
        return 500;
    }
}
