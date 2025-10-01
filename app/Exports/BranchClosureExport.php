<?php

namespace App\Exports;

use App\Models\Branch;
use App\Models\Transaction;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

class BranchClosureExport implements
    FromCollection,
    WithHeadings,
    WithMapping,
    WithTitle,
    WithStyles,
    WithColumnWidths,
    WithEvents,
    WithCustomStartCell
{
    protected Branch $branch;
    protected Collection $transactions;
    protected array $filters;
    protected array $stats;
    protected int $headerRowCount = 0;

    public function __construct(Branch $branch, array $filters = [])
    {
        $this->branch = $branch;
        $this->filters = $filters;
        $this->loadTransactions();
        $this->calculateStats();
        $this->calculateHeaderRowCount();
    }

    public function startCell(): string
    {
        return 'A' . ($this->headerRowCount + 1);
    }

    protected function loadTransactions(): void
    {
        $query = Transaction::with(['giftCard.user', 'branch', 'admin'])
            ->where('branch_id', $this->branch->id);

        // Date and time filters
        if (!empty($this->filters['date'])) {
            $date = $this->filters['date'];
            $timeFrom = $this->filters['time_from'] ?? '00:00';
            $timeTo = $this->filters['time_to'] ?? '23:59';

            $datetimeFrom = "{$date} {$timeFrom}:00";
            $datetimeTo = "{$date} {$timeTo}:59";

            $query->whereBetween('created_at', [$datetimeFrom, $datetimeTo]);
        }

        // Optional filters
        if (!empty($this->filters['type'])) {
            $query->where('type', $this->filters['type']);
        }

        if (!empty($this->filters['admin_user_id'])) {
            $query->where('admin_user_id', $this->filters['admin_user_id']);
        }

        $this->transactions = $query->orderBy('created_at')->get();
    }

    protected function calculateStats(): void
    {
        $totalTransactions = $this->transactions->count();
        $uniqueQRs = $this->transactions->pluck('gift_card_id')->unique()->count();

        // Calculate totals by transaction type
        $totalDebits = 0;
        $totalCredits = 0;

        $creditCount = 0;
        $creditAmount = 0;
        $debitCount = 0;
        $debitAmount = 0;
        $adjustmentCount = 0;
        $adjustmentAmount = 0;

        foreach ($this->transactions as $transaction) {
            if ($transaction->type === 'credit') {
                $totalCredits += $transaction->amount;
                $creditCount++;
                $creditAmount += $transaction->amount;
            } elseif ($transaction->type === 'debit') {
                $totalDebits += $transaction->amount;
                $debitCount++;
                $debitAmount += $transaction->amount;
            } elseif ($transaction->type === 'adjustment') {
                if ($transaction->amount > 0) {
                    $totalCredits += $transaction->amount;
                } else {
                    $totalDebits += abs($transaction->amount);
                }
                $adjustmentCount++;
                $adjustmentAmount += $transaction->amount;
            }
        }

        $this->stats = [
            'total_transactions' => $totalTransactions,
            'unique_qrs' => $uniqueQRs,
            'total_debits' => $totalDebits,
            'total_credits' => $totalCredits,
            'net_difference' => $totalCredits - $totalDebits,
            'by_type' => [
                'credit' => ['count' => $creditCount, 'amount' => $creditAmount],
                'debit' => ['count' => $debitCount, 'amount' => $debitAmount],
                'adjustment' => ['count' => $adjustmentCount, 'amount' => $adjustmentAmount],
            ],
        ];
    }

    protected function calculateHeaderRowCount(): void
    {
        $rows = 1; // Title
        $rows++; // Empty row
        $rows++; // Section "INFORMACIÓN DE LA SUCURSAL"
        $rows++; // Sucursal
        $rows++; // Fecha
        $rows++; // Horario
        $rows++; // Generado por
        $rows++; // Fecha de generación
        $rows++; // Empty row
        $rows++; // Section "RESUMEN DEL PERÍODO"
        $rows++; // Total de Transacciones
        $rows++; // QR Únicos
        $rows++; // Total Cargos
        $rows++; // Total Abonos
        $rows++; // Diferencia Neta
        $rows++; // Empty row
        $rows++; // "Por Tipo de Transacción:"
        $rows++; // Cargas
        $rows++; // Descuentos
        $rows++; // Ajustes
        $rows++; // Empty row
        $rows++; // Section "DETALLE DE TRANSACCIONES"
        $rows++; // Empty row

        $this->headerRowCount = $rows;
    }

    public function collection()
    {
        return $this->transactions;
    }

    public function headings(): array
    {
        return [
            'Fecha/Hora',
            'QR Empleado',
            'Empleado',
            'Tipo',
            'Descripción',
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
            $transaction->giftCard->legacy_id ?? 'N/A',
            $transaction->giftCard->user?->name ?? 'Sin asignar',
            $this->formatType($transaction->type),
            $transaction->description ?? '-',
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
        return 'Corte de Lote';
    }

    public function styles($sheet)
    {
        return [];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 18,  // Fecha/Hora
            'B' => 15,  // QR Empleado
            'C' => 25,  // Empleado
            'D' => 12,  // Tipo
            'E' => 35,  // Descripción
            'F' => 15,  // Cargo
            'G' => 15,  // Abono
            'H' => 15,  // Saldo
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
                $sheet->getStyle("A{$headerRow}:H{$headerRow}")
                    ->getFont()
                    ->setBold(true)
                    ->setSize(11)
                    ->getColor()
                    ->setRGB('FFFFFF');

                $sheet->getStyle("A{$headerRow}:H{$headerRow}")
                    ->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()
                    ->setRGB('4F46E5');

                $sheet->getStyle("A{$headerRow}:H{$headerRow}")
                    ->getBorders()
                    ->getAllBorders()
                    ->setBorderStyle(Border::BORDER_THIN);

                $sheet->getStyle("A{$headerRow}:H{$headerRow}")
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER);

                // Align currency columns to the right and format data rows
                if ($lastRow >= $dataStartRow) {
                    $sheet->getStyle("F{$dataStartRow}:H{$lastRow}")
                        ->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                    // Add thousand separators to currency
                    $sheet->getStyle("F{$dataStartRow}:H{$lastRow}")
                        ->getNumberFormat()
                        ->setFormatCode('#,##0.00');

                    // Add borders to data
                    $sheet->getStyle("A{$dataStartRow}:H{$lastRow}")
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
        $sheet->setCellValue("A{$row}", 'CORTE DE LOTE - SUCURSAL');
        $sheet->mergeCells("A{$row}:H{$row}");
        $sheet->getStyle("A{$row}")->getFont()->setBold(true)->setSize(18);
        $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("A{$row}")
            ->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()
            ->setRGB('4F46E5');
        $sheet->getStyle("A{$row}")->getFont()->getColor()->setRGB('FFFFFF');
        $sheet->getStyle("A{$row}")->getBorders()->getOutline()->setBorderStyle(Border::BORDER_THICK);
        $row++;

        // Empty row
        $row++;

        // Section: INFORMACIÓN DE LA SUCURSAL
        $sheet->setCellValue("A{$row}", "INFORMACIÓN DE LA SUCURSAL");
        $sheet->mergeCells("A{$row}:H{$row}");
        $sheet->getStyle("A{$row}")->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle("A{$row}")
            ->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()
            ->setRGB('E0E7FF');
        $sheet->getStyle("A{$row}")->getBorders()->getOutline()->setBorderStyle(Border::BORDER_THIN);
        $row++;

        // Sucursal
        $sheet->setCellValue("A{$row}", "Sucursal:");
        $sheet->setCellValue("B{$row}", $this->branch->name);
        $sheet->mergeCells("B{$row}:D{$row}");
        $sheet->getStyle("A{$row}")->getFont()->setBold(true);
        $row++;

        // Fecha
        $date = $this->filters['date'] ?? now()->format('Y-m-d');
        $sheet->setCellValue("A{$row}", "Fecha:");
        $sheet->setCellValue("B{$row}", \Carbon\Carbon::parse($date)->format('d/m/Y'));
        $sheet->getStyle("A{$row}")->getFont()->setBold(true);
        $row++;

        // Horario
        $timeFrom = $this->filters['time_from'] ?? '00:00';
        $timeTo = $this->filters['time_to'] ?? '23:59';
        $sheet->setCellValue("A{$row}", "Horario:");
        $sheet->setCellValue("B{$row}", "{$timeFrom} - {$timeTo}");
        $sheet->getStyle("A{$row}")->getFont()->setBold(true);
        $row++;

        // Generado por
        $sheet->setCellValue("A{$row}", "Generado por:");
        $sheet->setCellValue("B{$row}", auth()->user()->name ?? 'Sistema');
        $sheet->mergeCells("B{$row}:D{$row}");
        $sheet->getStyle("A{$row}")->getFont()->setBold(true);
        $row++;

        // Fecha de generación
        $sheet->setCellValue("A{$row}", "Fecha de generación:");
        $sheet->setCellValue("B{$row}", now()->format('d/m/Y H:i'));
        $sheet->getStyle("A{$row}")->getFont()->setBold(true);
        $row++;

        // Empty row
        $row++;

        // Section: RESUMEN DEL PERÍODO
        $sheet->setCellValue("A{$row}", "RESUMEN DEL PERÍODO");
        $sheet->mergeCells("A{$row}:H{$row}");
        $sheet->getStyle("A{$row}")->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle("A{$row}")
            ->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()
            ->setRGB('E0E7FF');
        $sheet->getStyle("A{$row}")->getBorders()->getOutline()->setBorderStyle(Border::BORDER_THIN);
        $row++;

        // Total de Transacciones
        $sheet->setCellValue("A{$row}", "Total de Transacciones:");
        $sheet->setCellValue("B{$row}", $this->stats['total_transactions']);
        $sheet->getStyle("A{$row}")->getFont()->setBold(true);
        $row++;

        // QR Únicos Operados
        $sheet->setCellValue("A{$row}", "QR Únicos Operados:");
        $sheet->setCellValue("B{$row}", $this->stats['unique_qrs']);
        $sheet->getStyle("A{$row}")->getFont()->setBold(true);
        $row++;

        // Total Cargos (Débitos)
        $sheet->setCellValue("A{$row}", "Total Cargos (Débitos):");
        $sheet->setCellValue("B{$row}", '$' . number_format($this->stats['total_debits'], 2));
        $sheet->getStyle("A{$row}")->getFont()->setBold(true);
        $sheet->getStyle("B{$row}")->getFont()->getColor()->setRGB('DC2626');
        $row++;

        // Total Abonos (Créditos)
        $sheet->setCellValue("A{$row}", "Total Abonos (Créditos):");
        $sheet->setCellValue("B{$row}", '$' . number_format($this->stats['total_credits'], 2));
        $sheet->getStyle("A{$row}")->getFont()->setBold(true);
        $sheet->getStyle("B{$row}")->getFont()->getColor()->setRGB('16A34A');
        $row++;

        // Diferencia Neta
        $sheet->setCellValue("A{$row}", "Diferencia Neta:");
        $sheet->setCellValue("B{$row}", '$' . number_format($this->stats['net_difference'], 2));
        $sheet->getStyle("A{$row}")->getFont()->setBold(true);
        $sheet->getStyle("B{$row}")->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle("B{$row}")->getFont()->getColor()->setRGB($this->stats['net_difference'] >= 0 ? '16A34A' : 'DC2626');
        $row++;

        // Empty row
        $row++;

        // Por Tipo de Transacción
        $sheet->setCellValue("A{$row}", "Por Tipo de Transacción:");
        $sheet->getStyle("A{$row}")->getFont()->setBold(true)->setItalic(true);
        $row++;

        // Cargas
        $sheet->setCellValue("A{$row}", "  • Cargas:");
        $sheet->setCellValue("B{$row}", "{$this->stats['by_type']['credit']['count']} transacciones - \$" . number_format($this->stats['by_type']['credit']['amount'], 2));
        $sheet->mergeCells("B{$row}:D{$row}");
        $sheet->getStyle("B{$row}")->getFont()->getColor()->setRGB('16A34A');
        $row++;

        // Descuentos
        $sheet->setCellValue("A{$row}", "  • Descuentos:");
        $sheet->setCellValue("B{$row}", "{$this->stats['by_type']['debit']['count']} transacciones - \$" . number_format($this->stats['by_type']['debit']['amount'], 2));
        $sheet->mergeCells("B{$row}:D{$row}");
        $sheet->getStyle("B{$row}")->getFont()->getColor()->setRGB('DC2626');
        $row++;

        // Ajustes
        $sheet->setCellValue("A{$row}", "  • Ajustes:");
        $sheet->setCellValue("B{$row}", "{$this->stats['by_type']['adjustment']['count']} transacciones - \$" . number_format($this->stats['by_type']['adjustment']['amount'], 2));
        $sheet->mergeCells("B{$row}:D{$row}");
        $sheet->getStyle("B{$row}")->getFont()->getColor()->setRGB('F59E0B');
        $row++;

        // Empty row
        $row++;

        // Section: DETALLE DE TRANSACCIONES
        $sheet->setCellValue("A{$row}", "DETALLE DE TRANSACCIONES");
        $sheet->mergeCells("A{$row}:H{$row}");
        $sheet->getStyle("A{$row}")->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle("A{$row}")
            ->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()
            ->setRGB('E0E7FF');
        $sheet->getStyle("A{$row}")->getBorders()->getOutline()->setBorderStyle(Border::BORDER_THIN);
        $row++;

        // Empty row before table headers
        $row++;
    }

    protected function addTotalsRow($sheet, int $row): void
    {
        // Empty row before totals
        $row++;

        // TOTALES FINALES section
        $sheet->setCellValue("A{$row}", "TOTALES FINALES");
        $sheet->mergeCells("A{$row}:H{$row}");
        $sheet->getStyle("A{$row}")->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle("A{$row}")
            ->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()
            ->setRGB('E0E7FF');
        $sheet->getStyle("A{$row}")->getBorders()->getOutline()->setBorderStyle(Border::BORDER_MEDIUM);
        $row++;

        // Totals row
        $sheet->setCellValue("E{$row}", "CARGO:");
        $sheet->setCellValue("F{$row}", number_format($this->stats['total_debits'], 2));
        $sheet->getStyle("E{$row}")->getFont()->setBold(true);
        $sheet->getStyle("F{$row}")->getFont()->setBold(true)->getColor()->setRGB('DC2626');
        $row++;

        $sheet->setCellValue("E{$row}", "ABONO:");
        $sheet->setCellValue("G{$row}", number_format($this->stats['total_credits'], 2));
        $sheet->getStyle("E{$row}")->getFont()->setBold(true);
        $sheet->getStyle("G{$row}")->getFont()->setBold(true)->getColor()->setRGB('16A34A');
        $row++;

        $sheet->setCellValue("E{$row}", "NETO:");
        $sheet->setCellValue("H{$row}", number_format($this->stats['net_difference'], 2));
        $sheet->getStyle("E{$row}")->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle("H{$row}")->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle("H{$row}")->getFont()->getColor()->setRGB($this->stats['net_difference'] >= 0 ? '16A34A' : 'DC2626');

        // Style totals section
        $totalsSectionStart = $row - 3;
        $sheet->getStyle("A{$totalsSectionStart}:H{$row}")
            ->getBorders()
            ->getOutline()
            ->setBorderStyle(Border::BORDER_MEDIUM);

        // Align totals to right
        $sheet->getStyle("E{$row}:H{$row}")
            ->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        $sheet->getStyle("F{$row}:H{$row}")
            ->getNumberFormat()
            ->setFormatCode('#,##0.00');
    }
}
