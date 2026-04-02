<?php

namespace App\Exports\Sheets;

use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

class SelfEmployedTemplateSheet implements WithHeadings, WithTitle, FromArray, WithEvents, ShouldAutoSize
{
    public function array(): array
    {
        return [
            ['Self-Employed', 'Danindu Ransika', '199012345678', '0771234567', 'Western', 'Colombo', 'Thimbirigasyaya', 'Information Technology and Modern Services', '30', '123 Main St, Colombo 05', '0771234567', 'danindu@gmail.com', '5']
        ];
    }

    public function headings(): array
    {
        return [
            'Category',
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
            'Email Address',
            'Employees Count'
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
                
                // --- 1. HEADER STYLING (A1:M1) ---
                $sheet->getStyle('A1:M1')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => 'FFFFFF'],
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '0056b3'], // Website Primary Blue
                    ],
                ]);

                // --- 2. INTERACTIVE DATA RANGE STYLING (A2:M1001) ---
                // Apply a light background and branded borders to 1000 data rows
                $sheet->getStyle('A2:M1001')->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'F8F9FA'], // Website Background
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => '0056b3'], // Website Primary Blue
                        ],
                    ],
                ]);

                // --- 3. AUTO FILTER ---
                $sheet->setAutoFilter('A1:M1');
                
                // --- 4. DATA VALIDATION (1000 Rows) ---
                
                // Category (A2:A1001)
                $validationCategory = $sheet->getDataValidation('A2:A1001');
                $validationCategory->setType(DataValidation::TYPE_LIST);
                $validationCategory->setErrorStyle(DataValidation::STYLE_STOP);
                $validationCategory->setAllowBlank(false);
                $validationCategory->setShowInputMessage(true);
                $validationCategory->setShowErrorMessage(true);
                $validationCategory->setShowDropDown(true);
                $validationCategory->setFormula1("'Options'!\$A\$2:\$A\$3");

                // Province (E2:E1001)
                $validationProvince = $sheet->getDataValidation('E2:E1001');
                $validationProvince->setType(DataValidation::TYPE_LIST);
                $validationProvince->setFormula1("'Options'!\$B\$2:\$B\$10");
                $validationProvince->setShowDropDown(true);

                // Field of Work (H2:H1001)
                $validationField = $sheet->getDataValidation('H2:H1001');
                $validationField->setType(DataValidation::TYPE_LIST);
                $validationField->setFormula1("'Options'!\$C\$2:\$C\$11");
                $validationField->setShowDropDown(true);
            },
        ];
    }
}
