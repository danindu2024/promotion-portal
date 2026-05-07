<?php

namespace App\Exports\Sheets;

use App\Models\MainRegistry;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class SelfEmployedAudienceSheet implements FromQuery, WithHeadings, WithMapping, WithTitle, WithEvents, ShouldAutoSize
{
    protected $filters;

    public function __construct(array $filters)
    {
        $this->filters = $filters;
    }

    public function query()
    {
        $query = MainRegistry::query()->active()->where('category', 'Self-Employed');

        $query = $query->when(!empty($this->filters['province']), fn($q) => $q->where('province', $this->filters['province']))
                     ->when(!empty($this->filters['district']), fn($q) => $q->where('district', $this->filters['district']))
                     ->when(!empty($this->filters['ds_division']), fn($q) => $q->where('ds_division', $this->filters['ds_division']))
                     ->when(!empty($this->filters['field_of_work']), fn($q) => $q->where('field_of_work', $this->filters['field_of_work']));

        if (!empty($this->filters['search'])) {
            $search = $this->filters['search'];
            $query->where(function($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                  ->orWhere('contact_number', 'like', "%{$search}%")
                  ->orWhere('national_id_number', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('created_at', 'desc');
    }

    public function headings(): array
    {
        return [
            'Full Name',
            'Contact Number',
            'WhatsApp Number',
            'Email',
            'Age',
            'National Id Number',
            'Province',
            'District',
            'DS Division',
            'Address',
            'Field of Work',
            'Employees Count',
        ];
    }

    public function map($row): array
    {
        return [
            $row->full_name,
            $row->contact_number,
            $row->whatsapp_number,
            $row->email,
            $row->age,
            $row->national_id_number,
            $row->province,
            $row->district,
            $row->ds_division,
            $row->address,
            $row->field_of_work,
            $row->employees_count,
        ];
    }

    public function title(): string
    {
        return 'Self-Employed';
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
                        'startColor' => ['rgb' => '0056b3'],
                    ],
                ]);
            },
        ];
    }
}
