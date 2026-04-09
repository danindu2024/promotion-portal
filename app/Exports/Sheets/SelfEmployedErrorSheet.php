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

class SelfEmployedErrorSheet implements WithHeadings, WithTitle, FromCollection, WithEvents, ShouldAutoSize, WithMapping
{
    protected $rows;

    private const COLUMN_MAP = [
        'full_name'          => 'A',
        'national_id_number' => 'B',
        'contact_number'     => 'C',
        'province'           => 'D',
        'district'           => 'E',
        'ds_division'        => 'F',
        'field_of_work'      => 'G',
        'age'                => 'H',
        'address'            => 'I',
        'whatsapp_number'    => 'J',
        'email'              => 'K',
        'employees_count'    => 'L',
    ];

    public function __construct(array $rows)
    {
        $this->rows = collect($rows);
    }

    // return the collection of rows to be exported
    public function collection()
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return [
            'Full Name',
            'National ID Number',
            'Contact Number',
            'Province',
            'District',
            'DS Division',
            'Field of Work',
            'Age',
            'Address',
            'WhatsApp Number',
            'Email',
            'Employees Count',
            'Error Message'
        ];
    }

    // handle missing fields and column alignment
    public function map($row): array
    {
        return [
            $row['full_name'] ?? '',
            $row['national_id_number'] ?? '',
            $row['contact_number'] ?? '',
            $row['province'] ?? '',
            $row['district'] ?? '',
            $row['ds_division'] ?? '',
            $row['field_of_work'] ?? '',
            $row['age'] ?? '',
            $row['address'] ?? '',
            $row['whatsapp_number'] ?? '',
            $row['email'] ?? '',
            $row['employees_count'] ?? '',
            $row['error'] ?? ''
        ];
    }

    public function title(): string
    {
        return 'Self-Employed';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                /** @var \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet */
                $sheet = $event->sheet->getDelegate();
                
                // --- HEADER STYLING (A1:M1)
                $sheet->getStyle('A1:M1')->applyFromArray([
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
                $sheet->getStyle('A1:M1')->getProtection()
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
                        $rowIndex = $index + 2; // skip the header row
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
                $rowCount = $this->rows->count() + 10; // Validation for existing rows + 10 buffer
                
                // Province (D2:D<row count>)
                $validationProvince = $sheet->getDataValidation("D2:D$rowCount");
                $validationProvince->setType(DataValidation::TYPE_LIST);
                $validationProvince->setErrorStyle(DataValidation::STYLE_STOP);
                $validationProvince->setShowErrorMessage(true);
                $validationProvince->setErrorTitle('Invalid Input');
                $validationProvince->setError('Please select a correct province from the dropdown.');
                $validationProvince->setShowDropDown(true);
                $validationProvince->setFormula1("'Options'!\$B\$2:\$B\$10");

                // Field of Work (G2:G<row count>)
                $validationField = $sheet->getDataValidation("G2:G$rowCount");
                $validationField->setType(DataValidation::TYPE_LIST);
                $validationField->setErrorStyle(DataValidation::STYLE_STOP);
                $validationField->setShowErrorMessage(true);
                $validationField->setErrorTitle('Invalid Input');
                $validationField->setError('Please select a correct field of work from the dropdown.');
                $validationField->setShowDropDown(true);
                $validationField->setFormula1("'Options'!\$C\$2:\$C\$11");
            },
        ];
    }
}
