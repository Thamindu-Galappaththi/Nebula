<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class StudentViewExport implements FromArray, WithHeadings, WithStyles, WithColumnWidths
{
    protected $data;
    protected $headingRow;
    protected $meta;

    public function __construct(array $data, array $headings, array $meta = [])
    {
        $this->data = $data;
        $this->headingRow = $headings;
        $this->meta = $meta;
    }

    public function array(): array
    {
        return $this->data;
    }

    public function headings(): array
    {
        return $this->headingRow;
    }

    public function styles(Worksheet $sheet)
    {
        $lastColumn = $this->columnLetter(count($this->headingRow));
        $headerFill = [
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4472C4'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ];

        $sheet->getStyle('A1:' . $lastColumn . '1')->applyFromArray($headerFill);

        $sheet->insertNewRowBefore(1, 3);
        $sheet->mergeCells('A1:' . $lastColumn . '1');
        $sheet->setCellValue('A1', 'ALL STUDENTS VIEW');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 16,
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
            ],
        ]);

        $sheet->mergeCells('A2:' . $lastColumn . '2');
        $sheet->setCellValue('A2', sprintf(
            'Student: %s | Course: %s | Intake: %s | Specialization: %s | Status: %s',
            $this->meta['studentId'] ?? 'All',
            $this->meta['courseText'] ?? 'All Courses',
            $this->meta['intakeText'] ?? 'All Intakes',
            $this->meta['specializationText'] ?? 'All',
            $this->meta['statusText'] ?? 'All'
        ));
        $sheet->getStyle('A2')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 11,
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
            ],
        ]);

        // Header is row 4 after insertNewRowBefore(1, 3); last data row is count + 4.
        $lastRow = max(4, count($this->data) + 4);
        $sheet->getStyle('A4:' . $lastColumn . $lastRow)->applyFromArray([
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                ],
            ],
        ]);

        if (count($this->data) > 0) {
            $leftAligned = ['Student', 'Course', 'Specialization'];
            foreach ($this->headingRow as $index => $heading) {
                if (!in_array($heading, $leftAligned, true)) {
                    continue;
                }
                $letter = $this->columnLetter($index + 1);
                $sheet->getStyle($letter . '5:' . $letter . $lastRow)->applyFromArray([
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_LEFT,
                        'vertical' => Alignment::VERTICAL_CENTER,
                        'wrapText' => true,
                    ],
                ]);
            }
        }

        return $sheet;
    }

    public function columnWidths(): array
    {
        $widthsByHeading = [
            'No.' => 8,
            'Student' => 36,
            'NIC' => 18,
            'Course' => 40,
            'Intake' => 22,
            'Specialization' => 40,
            'Location' => 16,
            'Status' => 14,
        ];

        $used = [];
        foreach ($this->headingRow as $index => $heading) {
            $letter = $this->columnLetter($index + 1);
            $used[$letter] = $widthsByHeading[$heading] ?? 18;
        }

        return $used;
    }

    private function columnLetter(int $index): string
    {
        $letter = '';
        while ($index > 0) {
            $index--;
            $letter = chr(65 + ($index % 26)) . $letter;
            $index = intdiv($index, 26);
        }

        return $letter;
    }
}
