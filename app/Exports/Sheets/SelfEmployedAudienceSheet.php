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
        $query = MainRegistry::query()->where('category', 'Self-Employed');

        if (!empty($this->filters['province'])) {
            $query->where('province', $this->filters['province']);
        }
        if (!empty($this->filters['district'])) {
            $query->where('district', $this->filters['district']);
        }
        if (!empty($this->filters['ds_division'])) {
            $query->where('ds_division', $this->filters['ds_division']);
        }
        if (!empty($this->filters['field_of_work'])) {
            $query->where('field_of_work', $this->filters['field_of_work']);
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
                        'startColor' => ['rgb' => '0056b3'], // Website Primary Blue
                    ],
                ]);
            },
        ];
    }
}
