<?php

namespace App\Imports\Sheets;

use App\Imports\Concerns\BaseRegistryImport;
use App\Imports\RegistryImport;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Illuminate\Support\Collection;

class TradeSheetImport implements ToCollection, WithChunkReading, WithStartRow
{
    use BaseRegistryImport;

    protected $importer;

    public function __construct(RegistryImport $importer)
    {
        $this->importer = $importer;
    }

    /**
     * Start reading from row 2 (skips header)
     */
    public function startRow(): int
    {
        return 2;
    }

    public function collection(Collection $rows)
    {

        $mappedRows = $rows->map(function ($row) {
            $rowData = $row->toArray();
            
            // Skip empty rows
            if (empty(array_filter($rowData, fn($v) => $v !== null && (string)$v !== ''))) {
                return null;
            }

            return [
                'category'           => 'Trade',
                'full_name'          => $rowData[0] ?? null,
                'national_id_number' => $this->normalizeNationalId($rowData[1] ?? null),
                'contact_number'     => $this->normalizePhoneNumber($rowData[2] ?? null),
                'province'           => $rowData[3] ?? null,
                'district'           => $rowData[4] ?? null,
                'ds_division'        => $rowData[5] ?? null,
                'address'            => $rowData[6] ?? null,
                'whatsapp_number'    => $this->normalizePhoneNumber($rowData[7] ?? null),
                'email'              => $rowData[8] ?? null,
                'contact_person'     => $rowData[9] ?? null,
                'members_count'      => $rowData[10] ?? null,
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
