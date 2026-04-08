<?php

namespace App\Imports\Sheets;

use App\Imports\Concerns\BaseRegistryImport;
use App\Imports\RegistryImport;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Collection;

class SelfEmployedSheetImport implements ToCollection, WithChunkReading, WithHeadingRow
{
    use BaseRegistryImport;

    protected $importer;

    public function __construct(RegistryImport $importer)
    {
        $this->importer = $importer;
    }

    public function collection(Collection $rows)
    {
        $mappedRows = $rows->map(function ($row) {
            // WithHeadingRow gives us named keys matching the Excel header row
            $rowData = $row->toArray();
            
            // Skip empty rows
            if (empty(array_filter($rowData, fn($v) => $v !== null && (string)$v !== ''))) {
                return null;
            }

            return [
                'category'           => 'Self-Employed',
                'full_name'          => $rowData['full_name'] ?? null,
                'national_id_number' => $this->normalizeNationalId($rowData['national_id_number'] ?? null),
                'contact_number'     => $this->normalizePhoneNumber($rowData['contact_number'] ?? null),
                'province'           => $rowData['province'] ?? null,
                'district'           => $rowData['district'] ?? null,
                'ds_division'        => $rowData['ds_division'] ?? null,
                'field_of_work'      => $rowData['field_of_work'] ?? null,
                'age'                => $rowData['age'] ?? null,
                'address'            => $rowData['address'] ?? null,
                'whatsapp_number'    => $this->normalizePhoneNumber($rowData['whatsapp_number'] ?? null),
                'email'              => $rowData['email'] ?? null,
                'employees_count'    => $rowData['employees_count'] ?? null,
            ];
        })->filter(); // remove the empty rows

        // add data to staging table
        if ($mappedRows->isNotEmpty()) {
            $this->processRows($mappedRows, $this->importer);
        }
    }

    public function chunkSize(): int
    {
        return 500;
    }
}
