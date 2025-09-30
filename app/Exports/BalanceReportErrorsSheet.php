<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class BalanceReportErrorsSheet implements FromArray, WithHeadings, WithTitle, WithStyles, WithColumnWidths
{
    protected array $errors;

    public function __construct(array $errors)
    {
        $this->errors = $errors;
    }

    public function array(): array
    {
        $data = [];

        foreach ($this->errors as $error) {
            $data[] = [
                $error['row'],
                $error['uuid'],
                $error['error'],
                $error['data']['monto'] ?? '',
                $error['data']['descripcion'] ?? '',
                $error['data']['sucursal'] ?? '',
            ];
        }

        return $data;
    }

    public function headings(): array
    {
        return [
            'Fila',
            'UUID',
            'Error',
            'Monto',
            'Descripción',
            'Sucursal',
        ];
    }

    public function title(): string
    {
        return 'Errores';
    }

    public function styles($sheet)
    {
        // Header row
        $sheet->getStyle('A1:F1')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 11,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'DC2626'],
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                ],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
            ],
        ]);

        // Data rows
        $lastRow = count($this->errors) + 1;
        if ($lastRow > 1) {
            $sheet->getStyle("A2:F{$lastRow}")->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                    ],
                ],
            ]);

            // Wrap text in error column
            $sheet->getStyle("C2:C{$lastRow}")->getAlignment()->setWrapText(true);
        }

        return [];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 8,   // Fila
            'B' => 38,  // UUID
            'C' => 50,  // Error
            'D' => 12,  // Monto
            'E' => 30,  // Descripción
            'F' => 20,  // Sucursal
        ];
    }
}
