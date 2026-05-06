<?php

namespace App\Exports;

use App\Models\MainRegistry;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class FilteredAudienceExport implements WithMultipleSheets
{
    protected $filters;

    public function __construct(array $filters)
    {
        $this->filters = $filters;
    }

    /**
     * @return array
     */
    public function sheets(): array
    {
        return [
            new Sheets\SelfEmployedAudienceSheet($this->filters),
            new Sheets\TradeAudienceSheet($this->filters),
        ];
    }
}

