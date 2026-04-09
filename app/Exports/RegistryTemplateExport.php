<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

class RegistryTemplateExport implements WithMultipleSheets, WithEvents
{
    /**
     * @return array
     */
    public function sheets(): array
    {
        return [
            new Sheets\SelfEmployedTemplateSheet(),
            new Sheets\TradeTemplateSheet(),
            new Sheets\OptionsSheet(),
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                // Access the spreadsheet parent from the current sheet
                $spreadsheet = $event->sheet->getDelegate()->getParent();
                
                // Protect the workbook structure (prevents renaming, deleting, adding sheets)
                $spreadsheet->getSecurity()->setLockStructure(true);
                $spreadsheet->getSecurity()->setWorkbookPassword('registry_template');
            },
        ];
    }
}
