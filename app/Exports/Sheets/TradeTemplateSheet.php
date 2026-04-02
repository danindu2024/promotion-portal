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

class TradeTemplateSheet implements WithHeadings, WithTitle, FromArray, WithEvents, ShouldAutoSize
{
    public function array(): array
    {
        return [
            ['Trade', 'Sakindu Geethula', '198512345678', '0719876543', 'Central', 'Kandy', 'Kandy', '456 Market St', '0719876543', 'sakindu@gmail.com', 'Jane Smith', '10']
        ];
    }

    public function headings(): array
    {
        return [
            'Category',
            'Trade Name',
            'National ID Number of Contact Person',
            'Contact Number',
            'Province',
            'District',
            'DS Division',
            'Address',
            'WhatsApp Number',
            'Email Address',
            'Contact Person Name',
            'Members Count'
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
                
                // --- 1. HEADER STYLING (A1:L1) ---
                $sheet->getStyle('A1:L1')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => 'FFFFFF'],
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '0056b3'], // Website Primary Blue
                    ],
                ]);

                // --- 2. INTERACTIVE DATA RANGE STYLING (A2:L1001) ---
                $sheet->getStyle('A2:L1001')->applyFromArray([
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
                $sheet->setAutoFilter('A1:L1');

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
            },
        ];
    }
}
