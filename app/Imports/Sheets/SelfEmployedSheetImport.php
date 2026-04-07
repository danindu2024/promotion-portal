<?php

namespace App\Imports\Sheets;

use App\Imports\Concerns\BaseRegistryImport;
use App\Imports\RegistryImport;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Illuminate\Support\Collection;

class SelfEmployedSheetImport implements ToCollection, WithChunkReading, WithStartRow
{
    use BaseRegistryImport;

    protected $importer;

    public function __construct(RegistryImport $importer)
    {
        $this->importer = $importer;
    }

    /**
     * Start reading from row 2 (skips the header row safely across all chunks)
     */
    public function startRow(): int
    {
        return 2;
    }

    public function collection(Collection $rows)
    {
        $mappedRows = $rows->map(function ($row) {
            // convert laravel collection object to array
            $rowData = $row->toArray();
            
            // Skip empty rows
            if (empty(array_filter($rowData, fn($v) => $v !== null && (string)$v !== ''))) {
                return null;
            }

            // map the values with the keys of the database table
            return [
                'category'           => 'Self-Employed',
                'full_name'          => $rowData[0] ?? null, // null correlation is used here to prevent PHP warning
                'national_id_number' => $this->normalizeNationalId($rowData[1] ?? null), 
                'contact_number'     => $this->normalizePhoneNumber($rowData[2] ?? null),
                'province'           => $rowData[3] ?? null,
                'district'           => $rowData[4] ?? null,
                'ds_division'        => $rowData[5] ?? null,
                'field_of_work'      => $rowData[6] ?? null,
                'age'                => $rowData[7] ?? null,
                'address'            => $rowData[8] ?? null,
                'whatsapp_number'    => $this->normalizePhoneNumber($rowData[9] ?? null),
                'email'              => $rowData[10] ?? null,
                'employees_count'    => $rowData[11] ?? null,
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
