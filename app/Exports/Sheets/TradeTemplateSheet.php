<?php

namespace App\Exports\Sheets;

use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;

class TradeTemplateSheet implements WithHeadings, WithTitle, FromArray, WithEvents
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
            },
        ];
    }
}
