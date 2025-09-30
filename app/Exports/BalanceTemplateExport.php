<?php

namespace App\Exports;

use App\Models\GiftCard;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class BalanceTemplateExport implements FromArray, WithHeadings, WithStyles, WithColumnWidths, WithEvents
{
    public function array(): array
    {
        // Get some real UUIDs for examples
        $giftCards = GiftCard::with('user')->take(3)->get();

        $examples = [];

        if ($giftCards->count() >= 1) {
            $examples[] = [
                $giftCards[0]->id,
                '500',
                'Bono mensual Enero (CARGA: use + antes del número)',
                '',
            ];
        }

        if ($giftCards->count() >= 2) {
            $examples[] = [
                $giftCards[1]->id,
                '-200',
                'Descuento comida (DESCUENTO: use - antes del número)',
                'Mochomos Monterrey',
            ];
        }

        if ($giftCards->count() >= 3) {
            $examples[] = [
                $giftCards[2]->id,
                '1000.50',
                'Carga inicial (CARGA: use + antes del número)',
                '',
            ];
        }

        // Add empty rows for user to fill
        $examples[] = ['', '', '', ''];
        $examples[] = ['', '', '', ''];

        return $examples;
    }

    public function headings(): array
    {
        return [
            'uuid',
            'monto',
            'descripcion',
            'sucursal',
        ];
    }

    public function styles($sheet)
    {
        // Header row styling
        $sheet->getStyle('A1:D1')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 12,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4F46E5'],
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

        // Example rows styling
        $lastRow = count($this->array()) + 1;
        $sheet->getStyle("A2:D{$lastRow}")->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                ],
            ],
        ]);

        return [];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 38,  // uuid
            'B' => 15,  // monto
            'C' => 40,  // descripcion
            'D' => 25,  // sucursal
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // Add instructions at the top
                $sheet->insertNewRowBefore(1, 3);

                // Title
                $sheet->setCellValue('A1', 'PLANTILLA DE CARGA MASIVA DE SALDOS');
                $sheet->mergeCells('A1:D1');
                $sheet->getStyle('A1')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 16,
                        'color' => ['rgb' => '1F2937'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                    ],
                ]);

                // Instructions
                $sheet->setCellValue('A2', 'INSTRUCCIONES:');
                $sheet->getStyle('A2')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 11,
                        'color' => ['rgb' => '991B1B'],
                    ],
                ]);

                $sheet->setCellValue('A3', '• Para CARGAR saldo use números positivos: 500 o +500  |  Para DESCONTAR saldo use números negativos: -200');
                $sheet->setCellValue('B3', '• La columna "sucursal" es REQUERIDA solo para descuentos (montos negativos)');
                $sheet->mergeCells('A3:D3');
                $sheet->getStyle('A3')->applyFromArray([
                    'font' => [
                        'size' => 10,
                        'color' => ['rgb' => '374151'],
                    ],
                    'alignment' => [
                        'wrapText' => true,
                    ],
                ]);

                $sheet->getRowDimension(3)->setRowHeight(30);

                // Add notes column comments
                $sheet->getComment('A4')->getText()->createTextRun('UUID del QR Empleado (requerido)');
                $sheet->getComment('B4')->getText()->createTextRun('Use números positivos (500 o +500) para cargar, negativos (-200) para descontar.');
                $sheet->getComment('C4')->getText()->createTextRun('Descripción de la transacción (opcional)');
                $sheet->getComment('D4')->getText()->createTextRun('Nombre de la sucursal. Requerida SOLO para montos negativos.');

                // Color code the monto column examples
                $lastRow = count($this->array()) + 4;
                for ($row = 5; $row <= $lastRow; $row++) {
                    $cellValue = $sheet->getCell("B{$row}")->getValue();
                    if (!empty($cellValue) && is_numeric(str_replace(['+', '-', ' '], '', $cellValue))) {
                        // Negative numbers (debits) - red
                        if (strpos((string)$cellValue, '-') === 0) {
                            $sheet->getStyle("B{$row}")->applyFromArray([
                                'font' => [
                                    'color' => ['rgb' => 'DC2626'],
                                    'bold' => true,
                                ],
                            ]);
                        } else {
                            // Positive numbers (credits) - green
                            $sheet->getStyle("B{$row}")->applyFromArray([
                                'font' => [
                                    'color' => ['rgb' => '16A34A'],
                                    'bold' => true,
                                ],
                            ]);
                        }
                    }
                }
            },
        ];
    }
}
