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

class BalanceReportDetailSheet implements FromArray, WithHeadings, WithTitle, WithStyles, WithColumnWidths
{
    protected array $processed;

    public function __construct(array $processed)
    {
        $this->processed = $processed;
    }

    public function array(): array
    {
        $data = [];

        foreach ($this->processed as $item) {
            $data[] = [
                $item['row'],
                $item['legacy_id'],
                $item['employee'],
                $item['type'],
                $item['amount'],
                number_format($item['balance_before'], 2),
                number_format($item['balance_after'], 2),
                $item['branch'],
            ];
        }

        return $data;
    }

    public function headings(): array
    {
        return [
            'Fila',
            'ID Tarjeta',
            'Empleado',
            'Tipo',
            'Monto',
            'Saldo Anterior',
            'Saldo Nuevo',
            'Sucursal',
        ];
    }

    public function title(): string
    {
        return 'Detalle de Procesados';
    }

    public function styles($sheet)
    {
        // Header row
        $sheet->getStyle('A1:H1')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 11,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '059669'],
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
        $lastRow = count($this->processed) + 1;
        if ($lastRow > 1) {
            $sheet->getStyle("A2:H{$lastRow}")->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                    ],
                ],
            ]);

            // Right align numbers
            $sheet->getStyle("E2:G{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

            // Format currency columns
            $sheet->getStyle("F2:G{$lastRow}")->getNumberFormat()->setFormatCode('#,##0.00');

            // Color code amounts
            for ($row = 2; $row <= $lastRow; $row++) {
                $amount = $sheet->getCell("E{$row}")->getValue();
                if ($amount > 0) {
                    $sheet->getStyle("E{$row}")->applyFromArray([
                        'font' => [
                            'color' => ['rgb' => '16A34A'],
                            'bold' => true,
                        ],
                    ]);
                } elseif ($amount < 0) {
                    $sheet->getStyle("E{$row}")->applyFromArray([
                        'font' => [
                            'color' => ['rgb' => 'DC2626'],
                            'bold' => true,
                        ],
                    ]);
                }
            }
        }

        return [];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 8,   // Fila
            'B' => 15,  // ID Tarjeta
            'C' => 25,  // Empleado
            'D' => 12,  // Tipo
            'E' => 12,  // Monto
            'F' => 15,  // Saldo Anterior
            'G' => 15,  // Saldo Nuevo
            'H' => 20,  // Sucursal
        ];
    }
}
