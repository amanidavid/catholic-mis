<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class WeeklyAttendanceActionListExport implements FromArray, WithHeadings
{
    /**
     * @var array<int, array<int, mixed>>
     */
    protected array $rows;

    /**
     * @param array<int, array<int, mixed>> $rows
     */
    public function __construct(array $rows)
    {
        $this->rows = $rows;
    }

    public function headings(): array
    {
        return [
            'Member',
            'Family',
            'Consecutive Absences',
        ];
    }

    public function array(): array
    {
        return $this->rows;
    }
}
