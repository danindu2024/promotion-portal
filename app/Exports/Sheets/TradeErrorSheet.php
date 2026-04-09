<?php

namespace App\Exports\Sheets;

use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Protection;
use Illuminate\Support\Collection;

class TradeErrorSheet implements WithHeadings, WithTitle, FromCollection, WithEvents, ShouldAutoSize, WithMapping
{
    protected $rows;
    
    private const COLUMN_MAP = [
        'full_name'          => 'A', // Trade Name
        'national_id_number' => 'B',
        'contact_number'     => 'C',
        'province'           => 'D',
        'district'           => 'E',
        'ds_division'        => 'F',
        'address'            => 'G',
        'whatsapp_number'    => 'H',
        'email'              => 'I',
        'contact_person'     => 'J',
        'members_count'      => 'K',
    ];

    public function __construct(array $rows)
    {
        $this->rows = collect($rows);
    }

    public function collection()
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return [
            'Trade Name',           
            'National ID Number Of Contact Person',  
            'Contact Number',
            'Province',
            'District',
            'DS Division',
            'Address',
            'WhatsApp Number',
            'Email',               
            'Contact Person Name',      
            'Members Count',
            'Error Message'
        ];
    }

    public function map($row): array
    {
        return [
            $row['full_name'] ?? '', // Trade name is mapped to full_name key internally
            $row['national_id_number'] ?? '', // National ID Number of Contact Person is mapped to national_id_number key internally
            $row['contact_number'] ?? '',
            $row['province'] ?? '',
            $row['district'] ?? '',
            $row['ds_division'] ?? '',
            $row['address'] ?? '',
            $row['whatsapp_number'] ?? '',
            $row['email'] ?? '',
            $row['contact_person'] ?? '', // Contact Person name is mapped to contact_person key internally
            $row['members_count'] ?? '',
            $row['error'] ?? ''
        ];
    }

    public function title(): string
    {
        return 'Trade';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                /** @var \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet */
                $sheet = $event->sheet->getDelegate();
                
                // --- HEADER STYLING (A1:L1)
                $sheet->getStyle('A1:L1')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => 'FFFFFF'],
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '0056b3'],
                    ],
                ]);

                // --- PROTECTION ---
                $sheet->getParent()->getDefaultStyle()->getProtection()
                    ->setLocked(Protection::PROTECTION_UNPROTECTED);
                $sheet->getProtection()->setSheet(true);
                $sheet->getProtection()->setPassword('registry_template');
                
                // Lock Headers
                $sheet->getStyle('A1:L1')->getProtection()
                    ->setLocked(Protection::PROTECTION_PROTECTED);

                // define error highlight style
                $errorStyle = [
                    'fill' => [
                        'fillType'   => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'FFC7CE'], // Light Red background
                    ]
                ];

                // --- ERROR HIGHLIGHT ENGINE ---
                // 1. Collect all coordinates that need styling
                $errorCells = [];
                foreach ($this->rows as $index => $row) {
                    if (!empty($row['failed_fields'])) {
                        $rowIndex = $index + 2; 
                        foreach ($row['failed_fields'] as $field) {
                            if (isset(self::COLUMN_MAP[$field])) {
                                $errorCells[] = self::COLUMN_MAP[$field] . $rowIndex;
                            }
                        }
                    }
                }

                // 2. Apply styles in batches to drastically reduce memory usage and speed up execution
                if (!empty($errorCells)) {
                    // Chunk by 500 to prevent the coordinate string from becoming too large for PhpSpreadsheet to parse
                    $chunks = array_chunk($errorCells, 500); 

                    foreach ($chunks as $chunk) {
                        $coordinateString = implode(',', $chunk);
                        $sheet->getStyle($coordinateString)->applyFromArray($errorStyle);
                    }
                }

                // --- Dropdown range ---
                $rowCount = $this->rows->count() + 10;
                
                // Province (D2:D<row count>)
                $validationProvince = $sheet->getDataValidation("D2:D$rowCount");
                $validationProvince->setType(DataValidation::TYPE_LIST);
                $validationProvince->setErrorStyle(DataValidation::STYLE_STOP);
                $validationProvince->setShowErrorMessage(true);
                $validationProvince->setErrorTitle('Invalid Input');
                $validationProvince->setError('Please select the correct province from the dropdown.');
                $validationProvince->setShowDropDown(true);
                $validationProvince->setFormula1("'Options'!\$B\$2:\$B\$10");
            },
        ];
    }
}
