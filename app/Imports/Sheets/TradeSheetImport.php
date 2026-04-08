<?php

namespace App\Imports\Sheets;

use App\Imports\Concerns\BaseRegistryImport;
use App\Imports\RegistryImport;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Collection;

class TradeSheetImport implements ToCollection, WithChunkReading, WithHeadingRow
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
                'category'           => 'Trade',
                'trade_name'          => $rowData['trade_name'] ?? null,
                'national_id_number_of_contact_person' => $this->normalizeNationalId($rowData['national_id_number_of_contact_person'] ?? null),
                'contact_number'     => $this->normalizePhoneNumber($rowData['contact_number'] ?? null),
                'province'           => $rowData['province'] ?? null,
                'district'           => $rowData['district'] ?? null,
                'ds_division'        => $rowData['ds_division'] ?? null,
                'address'            => $rowData['address'] ?? null,
                'whatsapp_number'    => $this->normalizePhoneNumber($rowData['whatsapp_number'] ?? null),
                'email'              => $rowData['email'] ?? null,
                'contact_person_name'     => $rowData['contact_person_name'] ?? null,
                'members_count'      => $rowData['members_count'] ?? null,
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
