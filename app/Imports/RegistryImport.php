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
    
    // Shared state across all sheets to find duplicates within the same sheet/category
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
     * Check if a contact number has already been seen for this category in the current file.
     */
    public function isContactNumberInFile(string $number, string $category): bool
    {
        return isset($this->contactNumbersInFile["{$number}:{$category}"]);
    }

    /**
     * Add a contact number for a specific category to the file's seen list.
     */
    public function addContactNumberToFile(string $number, string $category): void
    {
        // We just assign true (or any value) to the key
        $this->contactNumbersInFile["{$number}:{$category}"] = true;
    }
}
