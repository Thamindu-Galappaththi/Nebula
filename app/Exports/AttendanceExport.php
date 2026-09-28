<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Font;

class AttendanceExport implements FromArray, WithHeadings, WithStyles, WithColumnWidths
{
    protected $data;
    protected $location;
    protected $courseName;
    protected $intake;
    protected $semester;
    protected $module;

    public function __construct($data, $location, $courseName, $intake, $semester, $module)
    {
        $this->data = $data;
        $this->location = $location;
        $this->courseName = $courseName;
        $this->intake = $intake;
        $this->semester = $semester;
        $this->module = $module;
    }

    public function array(): array
    {
        return $this->data;
    }

    public function headings(): array
    {
        return [
            'Registration Number',
            'Student Name',
            'Total Sessions',
            'Attended Sessions',
            'Attendance (%)'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Style the header row
        $sheet->getStyle('A1:E1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4472C4'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        // Add title row
        $sheet->insertNewRowBefore(1, 3);
        $sheet->mergeCells('A1:E1');
        $sheet->setCellValue('A1', 'ATTENDANCE REPORT');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 16,
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
            ],
        ]);

        $sheet->mergeCells('A2:E2');
        $sheet->setCellValue('A2', 'Location: ' . $this->location . ' | Course: ' . $this->courseName . ' | Intake: ' . $this->intake . ' | Semester: ' . $this->semester . ' | Module: ' . $this->module);
        $sheet->getStyle('A2')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 12,
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
            ],
        ]);

        // Style all cells
        $sheet->getStyle('A4:E' . (count($this->data) + 4))->applyFromArray([
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                ],
            ],
        ]);

        return $sheet;
    }

    public function columnWidths(): array
    {
        return [
            'A' => 20,
            'B' => 30,
            'C' => 15,
            'D' => 18,
            'E' => 15,
        ];
    }
}
