<?php

namespace App\Exports;

use App\Models\BankDeposit;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class BankDepositsExport implements FromQuery, WithHeadings, WithMapping, WithTitle, WithEvents, ShouldAutoSize
{
    protected $filters;

    public function __construct(array $filters)
    {
        $this->filters = $filters;
    }

    public function query()
    {
        $query = BankDeposit::with('creator');

        if (!empty($this->filters['search'])) {
            $search = $this->filters['search'];
            $query->where(function($q) use ($search) {
                $q->where('customer_name', 'LIKE', "%{$search}%")
                  ->orWhere('enrollment_number', 'LIKE', "%{$search}%");
            });
        }

        if (!empty($this->filters['from_date'])) {
            $query->whereDate('deposit_date', '>=', $this->filters['from_date']);
        }

        if (!empty($this->filters['to_date'])) {
            $query->whereDate('deposit_date', '<=', $this->filters['to_date']);
        }

        return $query->latest();
    }

    public function headings(): array
    {
        return [
            'Customer Name',
            'Enrollment Number',
            'Amount (LKR)',
            'Deposit Date',
            'Bank Name',
            'Branch',
            'Receipt Reference',
            'Remarks',
            'Recorded By',
            'District',
            'DS Division',
            'System Log Date'
        ];
    }

    public function map($row): array
    {
        return [
            $row->customer_name,
            $row->enrollment_number,
            $row->amount,
            $row->deposit_date,
            $row->bank_name,
            $row->branch,
            $row->receipt_reference_number,
            $row->remarks,
            $row->creator ? $row->creator->name : 'Unknown',
            $row->creator ? $row->creator->district : 'N/A',
            $row->creator ? $row->creator->ds_division : 'N/A',
            $row->created_at->format('Y-m-d H:i:s'),
        ];
    }

    public function title(): string
    {
        return 'Bank Deposits';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                
                // --- HEADER STYLING (A1:L1) ---
                $sheet->getStyle('A1:L1')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => 'FFFFFF'],
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '1D4ED8'], // primary-700 approx
                    ],
                ]);
            },
        ];
    }
}
