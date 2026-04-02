<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

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
