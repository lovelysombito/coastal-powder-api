<?php

namespace App\Exports;

use App\Models\Job;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class PowderBreakDownExport implements FromArray, WithHeadings
{

    public function __construct($jobs)
    {
        $this->jobs = $jobs;
    }

    public function array(): array
    {
        return $this->jobs;
    }

    public function headings(): array
    {
        return [
            'Job Prefix',
            'Job Number',
            'Material',
            'Treatment',
            'Powder Date',
            'Powder Bay',
            'Amount',
            'Treatment Amount',
            'Powder Amount',
        ];
    }
}
