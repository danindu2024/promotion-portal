<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\BeforeWriting;

class RegistryTemplateExport implements WithMultipleSheets, WithEvents
{
    /**
     * @return array
     */

    // get the multiple sheets
    public function sheets(): array
    {
        return [
            new Sheets\SelfEmployedTemplateSheet(),
            new Sheets\TradeTemplateSheet(),
            new Sheets\OptionsSheet(),
        ];
    }

    // add protection for tab modification
    public function registerEvents(): array
    {
        return [
            BeforeWriting::class => function(BeforeWriting $event) {
                $spreadsheet = $event->writer->getDelegate();
                
                // Protect the workbook structure (prevents renaming, deleting, adding, reordering sheets)
                $spreadsheet->getSecurity()->setLockStructure(true);
                $spreadsheet->getSecurity()->setWorkbookPassword('registry_template');
            },
        ];
    }
}
