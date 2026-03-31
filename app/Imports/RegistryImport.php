<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use App\Imports\Sheets\SelfEmployedSheetImport;
use App\Imports\Sheets\TradeSheetImport;

class RegistryImport implements WithMultipleSheets
{
    public $totalRows = 0;
    public $validCount = 0;
    public $invalidRows = [];
    public $batchId;
    public $dbError = null;
    
    // Shared state across all sheets to find duplicates within the entire Excel file
    protected $contactNumbersInFile = [];

    public function __construct()
    {
        $this->batchId = 'BATCH-' . uniqid('', true);
    }

    /**
     * Map sheets to their respective import classes.
     * The array keys match the sheet titles generated in RegistryTemplateExport.
     */
    public function sheets(): array
    {
        return [
            'Self-Employed' => new SelfEmployedSheetImport($this),
            'Trade'         => new TradeSheetImport($this),
        ];
    }

    /**
     * Get the global list of contact numbers already seen in the current file.
     */
    public function getContactNumbersInFile(): array
    {
        return $this->contactNumbersInFile;
    }

    /**
     * Add a contact number to the global file list.
     */
    public function addContactNumberToFile(string $number): void
    {
        $this->contactNumbersInFile[] = $number;
    }
}
