<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class BalanceReportSummarySheet implements FromArray, WithTitle, WithStyles, WithColumnWidths
{
    protected array $stats;

    public function __construct(array $stats)
    {
        $this->stats = $stats;
    }

    public function array(): array
    {
        return [
            ['RESUMEN DE IMPORTACIÓN DE SALDOS'],
            [''],
            ['Fecha y Hora:', now()->format('d/m/Y H:i:s')],
            ['Usuario:', auth()->user()->name ?? 'Sistema'],
            [''],
            ['ESTADÍSTICAS'],
            ['Total de QR Procesados:', $this->stats['processed']],
            ['Total de Errores:', $this->stats['errors']],
            [''],
            ['MONTOS'],
            ['Total Cargado (+):', '$' . number_format($this->stats['total_credited'], 2)],
            ['Total Descontado (-):', '$' . number_format($this->stats['total_debited'], 2)],
            ['Cambio Neto:', '$' . number_format($this->stats['net_change'], 2)],
            [''],
            ['Total de Filas Procesadas:', $this->stats['total']],
        ];
    }

    public function title(): string
    {
        return 'Resumen';
    }

    public function styles($sheet)
    {
        // Title
        $sheet->getStyle('A1:B1')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 16,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4F46E5'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
            ],
        ]);
        $sheet->mergeCells('A1:B1');

        // Section headers
        $sheet->getStyle('A6')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 12,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E5E7EB'],
            ],
        ]);

        $sheet->getStyle('A10')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 12,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E5E7EB'],
            ],
        ]);

        // Bold labels
        $sheet->getStyle('A3:A15')->getFont()->setBold(true);

        // Number formatting for currency
        $sheet->getStyle('B11:B13')->getNumberFormat()->setFormatCode('$#,##0.00');

        // Color code net change
        if ($this->stats['net_change'] > 0) {
            $sheet->getStyle('B13')->applyFromArray([
                'font' => [
                    'color' => ['rgb' => '16A34A'],
                    'bold' => true,
                ],
            ]);
        } elseif ($this->stats['net_change'] < 0) {
            $sheet->getStyle('B13')->applyFromArray([
                'font' => [
                    'color' => ['rgb' => 'DC2626'],
                    'bold' => true,
                ],
            ]);
        }

        return [];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 30,
            'B' => 25,
        ];
    }
}
