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
use PhpOffice\PhpSpreadsheet\Style\Protection;

class TradeTemplateSheet implements WithHeadings, WithTitle, FromArray, WithEvents, ShouldAutoSize
{
    public function array(): array
    {
        return [
            ['Sakindu Geethula', '198512345678', '0719876543', 'Central', 'Kandy', 'Kandy', '456 Market St', '0719876543', 'sakindu@gmail.com', 'Jane Smith', '10']
        ];
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
                
                // --- HEADER STYLING (A1:K1) ---
                $sheet->getStyle('A1:K1')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => 'FFFFFF'],
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '0056b3'], // Website Primary Blue
                    ],
                ]);

                // --- HEADER ROW PROTECTION ---
                // Inversion pattern: set the workbook default style to unlocked first
                // then lock only Header row relavant columns (A1:K1).
                $sheet->getParent()->getDefaultStyle()->getProtection()
                    ->setLocked(Protection::PROTECTION_UNPROTECTED);
                $sheet->getProtection()->setSheet(true);
                $sheet->getProtection()->setPassword('registry_template'); // password is included in code as this is not a highly sensitive data. This avoid unneccessary complexsity
                // Lock only the 11 header cells
                $sheet->getStyle('A1:K1')->getProtection()
                    ->setLocked(Protection::PROTECTION_PROTECTED);

                // --- DATA VALIDATION (500 Rows) ---
                
                // Province (D2:D501)
                $validationProvince = $sheet->getDataValidation('D2:D501');
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
