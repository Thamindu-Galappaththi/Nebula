<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Font;

class StudentListExport implements FromArray, WithHeadings, WithStyles, WithColumnWidths
{
    protected $data;
    protected $courseName;
    protected $location;
    protected $intake;
    protected $status;
    protected bool $includeSpecialization;

    public function __construct($data, $courseName, $location, $intake, $status, bool $includeSpecialization = true)
    {
        $this->data = $data;
        $this->courseName = $courseName;
        $this->location = $location;
        $this->intake = $intake;
        $this->status = $status;
        $this->includeSpecialization = $includeSpecialization;
    }

    public function array(): array
    {
        return $this->data;
    }

    public function headings(): array
    {
        $headings = [
            'No.',
            'Course Registration ID',
            'Student ID',
            'Student Name',
        ];

        if ($this->includeSpecialization) {
            $headings[] = 'Specialization';
        }

        $headings[] = 'Status';

        return $headings;
    }

    public function styles(Worksheet $sheet)
    {
        $lastColumn = $this->lastColumn();

        // Style the header row
        $sheet->getStyle('A1:' . $lastColumn . '1')->applyFromArray([
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
        $sheet->mergeCells('A1:' . $lastColumn . '1');
        $sheet->setCellValue('A1', 'STUDENT LIST');
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
        $sheet->setCellValue('A2', 'Course: ' . $this->courseName . ' | Location: ' . $this->location . ' | Intake: ' . $this->intake . ' | Status: ' . ucfirst($this->status));
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
        $sheet->getStyle('A4:' . $lastColumn . (count($this->data) + 4))->applyFromArray([
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
        if ($this->includeSpecialization) {
            return [
                'A' => 8,
                'B' => 25,
                'C' => 15,
                'D' => 30,
                'E' => 40,
                'F' => 15,
            ];
        }

        return [
            'A' => 8,
            'B' => 25,
            'C' => 15,
            'D' => 30,
            'E' => 15,
        ];
    }

    private function lastColumn(): string
    {
        return $this->includeSpecialization ? 'F' : 'E';
    }
}
