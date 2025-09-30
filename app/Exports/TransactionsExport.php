<?php

namespace App\Exports;

use App\Models\GiftCard;
use App\Models\Transaction;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Sheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

class TransactionsExport implements
    FromCollection,
    WithHeadings,
    WithMapping,
    WithTitle,
    WithStyles,
    WithColumnWidths,
    WithEvents,
    WithCustomStartCell
{
    protected ?GiftCard $giftCard;
    protected Collection $transactions;
    protected array $filters;
    protected $headerRowCount = 0;

    public function __construct(?GiftCard $giftCard = null, array $filters = [])
    {
        $this->giftCard = $giftCard;
        $this->filters = $filters;
        $this->calculateHeaderRowCount();
    }

    public function startCell(): string
    {
        return 'A' . ($this->headerRowCount + 1);
    }

    protected function calculateHeaderRowCount(): void
    {
        $rows = 1; // Title
        $rows++; // Empty row

        if ($this->giftCard) {
            $rows++; // Section title "INFORMACIÓN DE LA TARJETA"
            $rows++; // ID Tarjeta
            $rows++; // Empleado
            $rows++; // Saldo Actual
            $rows++; // Estado
            $rows++; // Empty row
        }

        if (!empty($this->filters['date_from']) || !empty($this->filters['date_to'])) {
            $rows++; // Período
        }

        $rows++; // Generado
        $rows++; // Empty row before table

        $this->headerRowCount = $rows;
    }

    public function collection()
    {
        $query = $this->giftCard
            ? $this->giftCard->transactions()
            : Transaction::with(['giftCard', 'branch', 'admin']);

        // Apply filters
        if (!empty($this->filters['date_from'])) {
            $query->whereDate('created_at', '>=', $this->filters['date_from']);
        }

        if (!empty($this->filters['date_to'])) {
            $query->whereDate('created_at', '<=', $this->filters['date_to']);
        }

        if (!empty($this->filters['type'])) {
            $query->where('type', $this->filters['type']);
        }

        if (!empty($this->filters['branch_id'])) {
            $query->where('branch_id', $this->filters['branch_id']);
        }

        if (!empty($this->filters['admin_user_id'])) {
            $query->where('admin_user_id', $this->filters['admin_user_id']);
        }

        $this->transactions = $query->orderBy('created_at')->get();

        return $this->transactions;
    }

    public function headings(): array
    {
        return [
            'Fecha',
            'Tipo',
            'Descripción',
            'Sucursal',
            'Cargo',
            'Abono',
            'Saldo'
        ];
    }

    public function map($transaction): array
    {
        $isCredit = $transaction->type === 'credit' ||
                   ($transaction->type === 'adjustment' && $transaction->amount > 0);

        return [
            $transaction->created_at->format('d/m/Y H:i'),
            $this->formatType($transaction->type),
            $transaction->description ?? '-',
            $transaction->branch?->name ?? '-',
            !$isCredit ? number_format($transaction->amount, 2) : '',
            $isCredit ? number_format($transaction->amount, 2) : '',
            number_format($transaction->balance_after, 2)
        ];
    }

    protected function formatType(string $type): string
    {
        return match($type) {
            'credit' => 'Carga',
            'debit' => 'Descuento',
            'adjustment' => 'Ajuste',
            default => $type
        };
    }

    public function title(): string
    {
        return 'Transacciones';
    }

    public function styles($sheet)
    {
        // Este método se ejecuta antes del AfterSheet, por lo que no hacemos nada aquí
        // El styling se hace todo en AfterSheet
        return [];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 18,  // Fecha
            'B' => 12,  // Tipo
            'C' => 40,  // Descripción
            'D' => 20,  // Sucursal
            'E' => 15,  // Cargo
            'F' => 15,  // Abono
            'G' => 15,  // Saldo
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // Add header information FIRST
                $this->addHeader($sheet);

                // Get the actual data range
                $lastRow = $sheet->getHighestRow();
                $headerRow = $this->headerRowCount + 1;
                $dataStartRow = $headerRow + 1;

                // Style header row
                $sheet->getStyle("A{$headerRow}:G{$headerRow}")
                    ->getFont()
                    ->setBold(true)
                    ->setSize(11);

                $sheet->getStyle("A{$headerRow}:G{$headerRow}")
                    ->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()
                    ->setRGB('E2E8F0');

                $sheet->getStyle("A{$headerRow}:G{$headerRow}")
                    ->getBorders()
                    ->getAllBorders()
                    ->setBorderStyle(Border::BORDER_THIN);

                // Align currency columns to the right
                if ($lastRow >= $dataStartRow) {
                    $sheet->getStyle("E{$dataStartRow}:G{$lastRow}")
                        ->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                    // Add thousand separators to currency
                    $sheet->getStyle("E{$dataStartRow}:G{$lastRow}")
                        ->getNumberFormat()
                        ->setFormatCode('#,##0.00');

                    // Add borders to data
                    $sheet->getStyle("A{$dataStartRow}:G{$lastRow}")
                        ->getBorders()
                        ->getAllBorders()
                        ->setBorderStyle(Border::BORDER_THIN);

                    // Add totals row if there are transactions
                    if ($this->transactions->isNotEmpty()) {
                        $this->addTotalsRow($sheet, $lastRow + 1);
                    }
                }
            },
        ];
    }

    protected function addHeader($sheet): void
    {
        $row = 1;

        // Title
        $sheet->setCellValue("A{$row}", 'REPORTE DE TRANSACCIONES');
        $sheet->mergeCells("A{$row}:G{$row}");
        $sheet->getStyle("A{$row}")->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $row++;

        // Empty row
        $row++;

        if ($this->giftCard) {
            // Section title
            $sheet->setCellValue("A{$row}", "INFORMACIÓN DE LA TARJETA");
            $sheet->mergeCells("A{$row}:G{$row}");
            $sheet->getStyle("A{$row}")->getFont()->setBold(true)->setSize(12);
            $sheet->getStyle("A{$row}")
                ->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()
                ->setRGB('E2E8F0');
            $row++;

            // Gift Card ID
            $sheet->setCellValue("A{$row}", "ID Tarjeta:");
            $sheet->setCellValue("B{$row}", $this->giftCard->legacy_id);
            $sheet->mergeCells("B{$row}:C{$row}");
            $sheet->getStyle("A{$row}")->getFont()->setBold(true);
            $row++;

            // Employee name
            $sheet->setCellValue("A{$row}", "Empleado:");
            $sheet->setCellValue("B{$row}", $this->giftCard->user?->name ?? 'Sin asignar');
            $sheet->mergeCells("B{$row}:D{$row}");
            $sheet->getStyle("A{$row}")->getFont()->setBold(true);
            $row++;

            // Current balance
            $sheet->setCellValue("A{$row}", "Saldo Actual:");
            $sheet->setCellValue("B{$row}", '$' . number_format($this->giftCard->balance, 2));
            $sheet->getStyle("A{$row}")->getFont()->setBold(true);
            $sheet->getStyle("B{$row}")->getFont()->setSize(12)->getColor()->setRGB('16A34A');
            $row++;

            // Status
            $sheet->setCellValue("A{$row}", "Estado:");
            $sheet->setCellValue("B{$row}", $this->giftCard->status ? 'Activa' : 'Inactiva');
            $sheet->getStyle("A{$row}")->getFont()->setBold(true);
            $row++;

            // Empty row
            $row++;
        }

        // Date range
        if (!empty($this->filters['date_from']) || !empty($this->filters['date_to'])) {
            $dateFrom = $this->filters['date_from'] ?? 'Inicio';
            $dateTo = $this->filters['date_to'] ?? 'Hoy';
            $sheet->setCellValue("A{$row}", "Período:");
            $sheet->setCellValue("B{$row}", "{$dateFrom} - {$dateTo}");
            $sheet->mergeCells("B{$row}:D{$row}");
            $sheet->getStyle("A{$row}")->getFont()->setBold(true);
            $row++;
        }

        // Generated by
        $sheet->setCellValue("A{$row}", "Generado:");
        $sheet->setCellValue("B{$row}", now()->format('d/m/Y H:i') . ' por ' . (auth()->user()->name ?? 'Sistema'));
        $sheet->mergeCells("B{$row}:G{$row}");
        $sheet->getStyle("A{$row}")->getFont()->setBold(true);
        $sheet->getStyle("B{$row}")->getFont()->setSize(9)->getColor()->setRGB('64748B');
        $row++;

        // Empty row before table
        $row++;
    }

    protected function addTotalsRow($sheet, int $row): void
    {
        $totalCargos = $this->transactions->filter(function($t) {
            return $t->type === 'debit' || ($t->type === 'adjustment' && $t->amount < 0);
        })->sum('amount');

        $totalAbonos = $this->transactions->filter(function($t) {
            return $t->type === 'credit' || ($t->type === 'adjustment' && $t->amount > 0);
        })->sum('amount');

        $sheet->setCellValue("A{$row}", "TOTALES:");
        $sheet->setCellValue("E{$row}", number_format($totalCargos, 2));
        $sheet->setCellValue("F{$row}", number_format($totalAbonos, 2));

        $finalBalance = $this->transactions->last()?->balance_after ?? 0;
        $sheet->setCellValue("G{$row}", number_format($finalBalance, 2));

        // Style totals row
        $sheet->getStyle("A{$row}:G{$row}")->getFont()->setBold(true);
        $sheet->getStyle("A{$row}:G{$row}")
            ->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()
            ->setRGB('F1F5F9');

        $sheet->getStyle("A{$row}:G{$row}")
            ->getBorders()
            ->getAllBorders()
            ->setBorderStyle(Border::BORDER_MEDIUM);

        $sheet->getStyle("E{$row}:G{$row}")
            ->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        $sheet->getStyle("E{$row}:G{$row}")
            ->getNumberFormat()
            ->setFormatCode('#,##0.00');
    }
}
