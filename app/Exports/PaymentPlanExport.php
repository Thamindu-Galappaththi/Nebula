<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PaymentPlanExport implements FromArray, WithHeadings, WithStyles, WithColumnWidths
{
    public function __construct(private array $data)
    {
    }

    public function array(): array
    {
        return array_map(fn ($row) => array_values($row), $this->data);
    }

    public function headings(): array
    {
        return [
            'ID',
            'Location',
            'Course',
            'Intake',
            'Reg. Fee (LKR)',
            'Local Fee (LKR)',
            'Franchise Fee',
            'Currency',
            'Discount',
            'Installment Plan',
            'Created At',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:K1')->applyFromArray([
            'font' => ['bold' => true],
        ]);
        $sheet->getStyle('A1:K1')->getAlignment()->setHorizontal('center');
    }

    public function columnWidths(): array
    {
        return [
            'A' => 10,
            'B' => 42,
            'C' => 36,
            'D' => 18,
            'E' => 16,
            'F' => 16,
            'G' => 16,
            'H' => 12,
            'I' => 12,
            'J' => 16,
            'K' => 18,
        ];
    }
}
