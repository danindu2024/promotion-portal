<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\BeforeWriting;
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
        // array_values() re-indexes the array so $index starts from 0 in the sheet
        // Without this, $rowIndex = $index + 2 would be offset and highlight wrong rows
        $selfEmployedRows = array_values(array_filter($this->invalidRows, function($row) {
            return ($row['category'] ?? '') === 'Self-Employed';
        }));

        $tradeRows = array_values(array_filter($this->invalidRows, function($row) {
            return ($row['category'] ?? '') === 'Trade';
        }));

        return [
            new SelfEmployedErrorSheet($selfEmployedRows),
            new TradeErrorSheet($tradeRows),
            new OptionsSheet(),
        ];
    }

    public function registerEvents(): array
    {
        return [
            // BeforeWriting fires ONCE for the entire workbook, before it is written to disk.
            // This is the correct place for workbook-level settings like structure locking.
            BeforeWriting::class => function(BeforeWriting $event) {
                $spreadsheet = $event->writer->getDelegate();

                // Protect the workbook structure (prevents renaming, deleting, adding sheets)
                $spreadsheet->getSecurity()->setLockStructure(true);
                $spreadsheet->getSecurity()->setWorkbookPassword('registry_template');
            },
        ];
    }
}
