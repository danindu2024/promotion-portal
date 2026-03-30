<?php

namespace App\Exports;

use App\Models\MainRegistry;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class FilteredAudienceExport implements FromQuery, WithHeadings, WithMapping
{
    use Exportable;

    protected $filters;

    public function __construct(array $filters)
    {
        $this->filters = $filters;
    }

    public function query()
    {
        $query = MainRegistry::query();

        if (!empty($this->filters['province'])) {
            $query->where('province', $this->filters['province']);
        }
        if (!empty($this->filters['district'])) {
            $query->where('district', $this->filters['district']);
        }
        if (!empty($this->filters['ds_division'])) {
            $query->where('ds_division', $this->filters['ds_division']);
        }
        if (!empty($this->filters['category'])) {
            $query->where('category', $this->filters['category']);
        }
        if (!empty($this->filters['field_of_work'])) {
            $query->where('field_of_work', $this->filters['field_of_work']);
        }

        return $query->orderBy('created_at', 'desc');
    }

    public function headings(): array
    {
        return [
            'Category',
            'Full Name',
            'National ID Number',
            'Contact Number',
            'Province',
            'District',
            'DS Division',
            'Field of Work',
            'Age',
            'Address',
            'Whatsapp Number',
            'Email',
            'Contact Person Name',
            'Number of Members',
            'Number of Employees'
        ];
    }

    /**
     * @var MainRegistry $row
     */
    public function map($row): array
    {
        return [
            $row->category,
            $row->full_name,
            $row->national_id_number,
            $row->contact_number,
            $row->province,
            $row->district,
            $row->ds_division,
            $row->field_of_work,
            $row->age,
            $row->address,
            $row->whatsapp_number,
            $row->email,
            $row->contact_person,
            $row->members_count,
            $row->employees_count
        ];
    }
}
