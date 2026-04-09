<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use App\Exports\Sheets\SelfEmployedErrorSheet;
use App\Exports\Sheets\TradeErrorSheet;
use App\Exports\Sheets\OptionsSheet;

class BulkImportErrorExport implements WithMultipleSheets, WithEvents
{
    protected $invalidRows;

    public function __construct(array $invalidRows)
    {
        $this->invalidRows = $invalidRows;
    }

    /**
     * @return array
     */
    public function sheets(): array
    {
        $selfEmployedRows = array_filter($this->invalidRows, function($row) {
            return ($row['category'] ?? '') === 'Self-Employed';
        });

        $tradeRows = array_filter($this->invalidRows, function($row) {
            return ($row['category'] ?? '') === 'Trade';
        });

        return [
            new SelfEmployedErrorSheet($selfEmployedRows),
            new TradeErrorSheet($tradeRows),
            new OptionsSheet(),
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
