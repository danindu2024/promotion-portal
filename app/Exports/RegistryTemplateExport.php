<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use App\Exports\Sheets\SelfEmployedTemplateSheet;
use App\Exports\Sheets\TradeTemplateSheet;

class RegistryTemplateExport implements WithMultipleSheets
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
}
