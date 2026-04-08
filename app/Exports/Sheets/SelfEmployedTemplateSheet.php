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

class SelfEmployedTemplateSheet implements WithHeadings, WithTitle, FromArray, WithEvents, ShouldAutoSize
{
    // add sample data to excel
    public function array(): array
    {
        return [
            ['Danindu Ransika', '199012345678', '0771234567', 'Western', 'Colombo', 'Thimbirigasyaya', 'Information Technology and Modern Services', '30', '123 Main St, Colombo 05', '0771234567', 'danindu@gmail.com', '5']
        ];
    }

    // add headings to the sheet 
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
                
                // --- HEADER STYLING (A1:L1) ---
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

                // --- HEADER ROW PROTECTION ---
                // Inversion pattern: set the workbook default style to unlocked first
                // then lock only Header row relavant columns (A1:L1).
                $sheet->getParent()->getDefaultStyle()->getProtection()
                    ->setLocked(Protection::PROTECTION_UNPROTECTED);
                $sheet->getProtection()->setSheet(true);
                $sheet->getProtection()->setPassword('registry_template'); // password is included in code as this is not a highly sensitive data. This avoid unneccessary complexsity
                // Lock only the 12 header cells
                $sheet->getStyle('A1:L1')->getProtection()
                    ->setLocked(Protection::PROTECTION_PROTECTED);
                
                // --- DATA VALIDATION (500 Rows) ---
                
                // Province (D2:D501)
                $validationProvince = $sheet->getDataValidation('D2:D501');
                $validationProvince->setType(DataValidation::TYPE_LIST);
                $validationProvince->setErrorStyle(DataValidation::STYLE_STOP);
                $validationProvince->setShowErrorMessage(true);
                $validationProvince->setErrorTitle('Invalid Input');
                $validationProvince->setError('Please select a correct province from the dropdown.');
                $validationProvince->setShowDropDown(true);
                $validationProvince->setFormula1("'Options'!\$B\$2:\$B\$10");

                // Field of Work (G2:G501)
                $validationField = $sheet->getDataValidation('G2:G501');
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
