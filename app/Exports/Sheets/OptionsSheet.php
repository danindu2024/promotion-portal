<?php

namespace App\Exports\Sheets;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Collection;

class OptionsSheet implements FromCollection, WithTitle, WithHeadings, WithEvents
{
    public function collection()
    {
        $categories = ['Self-Employed', 'Trade'];
        $provinces = config('srilanka.provinces');
        $fields = [
            'Agriculture and Fisheries Entrepreneurs',
            'Cottage Industries / Small Industries',
            'Transport and Technical Services',
            'Construction Services',
            'Trade and Service Enterprises',
            'Tourism Industry',
            'Arts, Cultural, and Beauty Services',
            'Information Technology and Modern Services',
            'Educational Services',
            'Small-scale Trading'
        ];

        // add a loop to select the range of data. Null cells become empty values
        $data = [];
        $max = max(count($categories), count($provinces), count($fields));

        for ($i = 0; $i < $max; $i++) {
            $data[] = [
                $categories[$i] ?? '',
                $provinces[$i] ?? '',
                $fields[$i] ?? ''
            ];
        }

        return new Collection($data);
    }

    public function headings(): array
    {
        return ['Categories', 'Provinces', 'Fields of Work'];
    }

    public function title(): string
    {
        return 'Options';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                // Set the sheet as very hidden so user doesn't see it
                $event->sheet->getDelegate()->setSheetState(Worksheet::SHEETSTATE_VERYHIDDEN);
            },
        ];
    }
}
