<?php

namespace App\Exports;

use App\Models\GiftCard;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class GiftCardsExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithColumnWidths, WithEvents
{
    protected ?Collection $giftCards = null;
    protected array $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        $query = GiftCard::with(['user', 'transactions'])
            ->withCount('transactions');

        // Apply filters
        if (!empty($this->filters['status'])) {
            $query->where('status', $this->filters['status'] === 'active');
        }

        if (!empty($this->filters['user_id'])) {
            $query->where('user_id', $this->filters['user_id']);
        }

        if (!empty($this->filters['has_balance'])) {
            if ($this->filters['has_balance'] === 'yes') {
                $query->where('balance', '>', 0);
            } elseif ($this->filters['has_balance'] === 'no') {
                $query->where('balance', '=', 0);
            }
        }

        $this->giftCards = $query->orderBy('legacy_id')->get();

        return $this->giftCards;
    }

    public function headings(): array
    {
        return [
            'UUID',
            'ID Tarjeta',
            'Empleado',
            'Email',
            'Saldo Actual',
            'Total Transacciones',
            'Estado',
            'Fecha Expiración',
            'Fecha Creación',
        ];
    }

    public function map($giftCard): array
    {
        return [
            $giftCard->id,
            $giftCard->legacy_id,
            $giftCard->user?->name ?? 'Sin asignar',
            $giftCard->user?->email ?? '',
            number_format($giftCard->balance ?? 0, 2),
            $giftCard->transactions_count,
            $giftCard->status ? 'Activa' : 'Inactiva',
            $giftCard->expiry_date?->format('d/m/Y') ?? '',
            $giftCard->created_at->format('d/m/Y'),
        ];
    }

    public function styles($sheet)
    {
        return [];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 38,  // UUID
            'B' => 15,  // ID Tarjeta
            'C' => 30,  // Empleado
            'D' => 35,  // Email
            'E' => 15,  // Saldo
            'F' => 18,  // Total Transacciones
            'G' => 12,  // Estado
            'H' => 18,  // Fecha Expiración
            'I' => 18,  // Fecha Creación
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $lastRow = $sheet->getHighestRow();

                // Header styling
                $sheet->getStyle('A1:I1')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 11,
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

                // Data rows styling
                if ($lastRow > 1) {
                    $sheet->getStyle("A2:I{$lastRow}")->applyFromArray([
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => Border::BORDER_THIN,
                            ],
                        ],
                    ]);

                    // Right align numbers
                    $sheet->getStyle("E2:F{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                    // Format currency
                    $sheet->getStyle("E2:E{$lastRow}")->getNumberFormat()->setFormatCode('#,##0.00');

                    // Color code status
                    for ($row = 2; $row <= $lastRow; $row++) {
                        $status = $sheet->getCell("G{$row}")->getValue();
                        if ($status === 'Activa') {
                            $sheet->getStyle("G{$row}")->applyFromArray([
                                'font' => [
                                    'color' => ['rgb' => '16A34A'],
                                    'bold' => true,
                                ],
                            ]);
                        } else {
                            $sheet->getStyle("G{$row}")->applyFromArray([
                                'font' => [
                                    'color' => ['rgb' => 'DC2626'],
                                ],
                            ]);
                        }

                        // Color code balance
                        $balance = floatval(str_replace(',', '', $sheet->getCell("E{$row}")->getValue()));
                        if ($balance > 0) {
                            $sheet->getStyle("E{$row}")->applyFromArray([
                                'font' => [
                                    'color' => ['rgb' => '16A34A'],
                                    'bold' => true,
                                ],
                            ]);
                        }
                    }

                    // Add summary row
                    $summaryRow = $lastRow + 2;
                    $sheet->setCellValue("A{$summaryRow}", 'RESUMEN');
                    $sheet->setCellValue("B{$summaryRow}", 'Total QR:');
                    $sheet->setCellValue("C{$summaryRow}", ($lastRow - 1));

                    $sheet->setCellValue("D{$summaryRow}", 'Saldo Total:');
                    $sheet->setCellValue("E{$summaryRow}", "=SUM(E2:E{$lastRow})");

                    $sheet->getStyle("A{$summaryRow}:I{$summaryRow}")->applyFromArray([
                        'font' => [
                            'bold' => true,
                        ],
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => 'F3F4F6'],
                        ],
                    ]);

                    $sheet->getStyle("E{$summaryRow}")->getNumberFormat()->setFormatCode('#,##0.00');
                }
            },
        ];
    }
}
