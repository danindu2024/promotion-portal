<?php

namespace App\Exports\Sheets;

use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;

class SelfEmployedTemplateSheet implements WithHeadings, WithTitle, FromArray, WithEvents
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
                
                // DATA VALIDATION - Category (A2:A100)
                $validationCategory = $sheet->getDataValidation('A2:A100');
                $validationCategory->setType(DataValidation::TYPE_LIST);
                $validationCategory->setErrorStyle(DataValidation::STYLE_STOP);
                $validationCategory->setAllowBlank(false);
                $validationCategory->setShowInputMessage(true);
                $validationCategory->setShowErrorMessage(true);
                $validationCategory->setShowDropDown(true);
                // Reference the Options sheet (A2:A3 is Categories)
                $validationCategory->setFormula1("'Options'!\$A\$2:\$A\$3");

                // DATA VALIDATION - Province (E2:E100)
                // Use list from reference sheet (B2:B10 is Provinces)
                $validationProvince = $sheet->getDataValidation('E2:E100');
                $validationProvince->setType(DataValidation::TYPE_LIST);
                $validationProvince->setFormula1("'Options'!\$B\$2:\$B\$10");
                $validationProvince->setShowDropDown(true);

                // DATA VALIDATION - Field of Work (H2:H100)
                // Use list from reference sheet (C2:C11 is Fields)
                $validationField = $sheet->getDataValidation('H2:H100');
                $validationField->setType(DataValidation::TYPE_LIST);
                $validationField->setFormula1("'Options'!\$C\$2:\$C\$11");
                $validationField->setShowDropDown(true);
            },
        ];
    }
}
