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
        'contact_person'     => 'B',
        'contact_number'     => 'C',
        'whatsapp_number'    => 'D',
        'email'              => 'E',
        'national_id_number' => 'F',
        'province'           => 'G',
        'district'           => 'H',
        'ds_division'        => 'I',
        'address'            => 'J',
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
            'Contact Person Name',             
            'Contact Number',
            'WhatsApp Number',
            'Email',
            'National Id Number',
            'Province',
            'District',
            'DS Division',
            'Address',       
            'Members Count',
            'Error Message'
        ];
    }

    public function map($row): array
    {
        return [
            $row['full_name'] ?? '', // Trade Name (A)
            $row['contact_person'] ?? '', // Contact Person Name (B)
            $row['contact_number'] ?? '', // Contact Number (C)
            $row['whatsapp_number'] ?? '', // WhatsApp Number (D)
            $row['email'] ?? '', // Email (E)
            $row['national_id_number'] ?? '', // National Id Number (F)
            $row['province'] ?? '', // Province (G)
            $row['district'] ?? '', // District (H)
            $row['ds_division'] ?? '', // DS Division (I)
            $row['address'] ?? '', // Address (J)
            $row['members_count'] ?? '', // Members Count (K)
            $row['error'] ?? '' // Error Message (L)
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

                // 2. Apply styles to each error cell
                foreach ($errorCells as $coordinate) {
                    $sheet->getStyle($coordinate)->applyFromArray($errorStyle);
                }

                // --- Dropdown range ---
                $rowCount = $this->rows->count() + 10;
                
                // Province (G2:G<row count>)
                $validationProvince = $sheet->getDataValidation("G2:G$rowCount");
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
