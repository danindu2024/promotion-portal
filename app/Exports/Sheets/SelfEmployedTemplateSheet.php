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
    // add sample data to excel
    public function array(): array
    {
        return [
            ['Self-Employed', 'Danindu Ransika', '199012345678', '0771234567', 'Western', 'Colombo', 'Thimbirigasyaya', 'Information Technology and Modern Services', '30', '123 Main St, Colombo 05', '0771234567', 'danindu@gmail.com', '5']
        ];
    }

    // add headings to the sheet 
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

    // add tab name 
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
                
                // --- HEADER STYLING (A1:M1) ---
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
                
                // --- DATA VALIDATION (1000 Rows) ---
                
                // Category (A2:A201)
                $validationCategory = $sheet->getDataValidation('A2:A201');
                $validationCategory->setType(DataValidation::TYPE_LIST);
                $validationCategory->setErrorStyle(DataValidation::STYLE_STOP);
                $validationCategory->setAllowBlank(false);
                $validationCategory->setShowInputMessage(true);
                $validationCategory->setShowErrorMessage(true);
                $validationCategory->setShowDropDown(true);
                $validationCategory->setFormula1("'Options'!\$A\$2:\$A\$3");

                // Province (E2:E201)
                $validationProvince = $sheet->getDataValidation('E2:E201');
                $validationProvince->setType(DataValidation::TYPE_LIST);
                $validationProvince->setErrorStyle(DataValidation::STYLE_STOP);
                $validationProvince->setAllowBlank(false);
                $validationProvince->setShowInputMessage(true);
                $validationProvince->setShowErrorMessage(true);
                $validationProvince->setShowDropDown(true);
                $validationProvince->setFormula1("'Options'!\$B\$2:\$B\$10");

                // Field of Work (H2:H201)
                $validationField = $sheet->getDataValidation('H2:H201');
                $validationField->setType(DataValidation::TYPE_LIST);
                $validationField->setErrorStyle(DataValidation::STYLE_STOP);
                $validationField->setAllowBlank(false);
                $validationField->setShowInputMessage(true);
                $validationField->setShowErrorMessage(true);
                $validationField->setShowDropDown(true);
                $validationField->setFormula1("'Options'!\$C\$2:\$C\$11");
            },
        ];
    }
}
