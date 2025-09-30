<?php

namespace App\Imports;

use App\Models\Branch;
use App\Models\GiftCard;
use App\Services\TransactionService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsErrors;

class BalanceImport implements ToCollection, WithHeadingRow, WithStartRow, WithChunkReading, SkipsOnError
{
    use SkipsErrors;

    protected TransactionService $transactionService;
    protected array $importErrors = [];
    protected array $processed = [];
    protected float $totalCredited = 0;
    protected float $totalDebited = 0;
    protected int $adminUserId;
    protected bool $allowMultiple;
    protected int $startRow;
    protected int $headingRow;

    public function __construct(int $adminUserId, bool $allowMultiple = true, int $startRow = 5, int $headingRow = 4)
    {
        $this->transactionService = new TransactionService();
        $this->adminUserId = $adminUserId;
        $this->allowMultiple = $allowMultiple;
        $this->startRow = $startRow;
        $this->headingRow = $headingRow;
    }

    public function startRow(): int
    {
        return $this->startRow;
    }

    public function headingRow(): int
    {
        return $this->headingRow;
    }

    public function collection(Collection $rows)
    {
        $processedUuids = [];

        foreach ($rows as $index => $row) {
            // Calculate row number based on start row
            $rowNumber = $index + $this->startRow;

            try {
                // Validate required fields
                if (empty($row['uuid']) || !isset($row['monto'])) {
                    $this->importErrors[] = [
                        'row' => $rowNumber,
                        'uuid' => $row['uuid'] ?? 'N/A',
                        'error' => 'UUID y monto son requeridos',
                        'data' => $row->toArray(),
                    ];
                    continue;
                }

                $uuid = trim($row['uuid']);
                $monto = trim($row['monto']);

                // Check for multiple loads to same QR if not allowed
                if (!$this->allowMultiple && in_array($uuid, $processedUuids)) {
                    $this->importErrors[] = [
                        'row' => $rowNumber,
                        'uuid' => $uuid,
                        'error' => 'UUID duplicado en el archivo (múltiples cargas no permitidas)',
                        'data' => $row->toArray(),
                    ];
                    continue;
                }

                // Find GiftCard by UUID
                $giftCard = GiftCard::where('id', $uuid)->first();

                if (!$giftCard) {
                    $this->importErrors[] = [
                        'row' => $rowNumber,
                        'uuid' => $uuid,
                        'error' => 'QR Empleado no encontrado',
                        'data' => $row->toArray(),
                    ];
                    continue;
                }

                // Check if GiftCard is active
                if (!$giftCard->status) {
                    $this->importErrors[] = [
                        'row' => $rowNumber,
                        'uuid' => $uuid,
                        'error' => "QR Empleado inactivo ({$giftCard->legacy_id})",
                        'data' => $row->toArray(),
                    ];
                    continue;
                }

                // Parse amount (remove spaces and commas)
                $amountStr = str_replace([' ', ','], ['', ''], $monto);

                // Check if explicitly marked with + or just a positive number
                $hasExplicitSign = str_starts_with($amountStr, '+') || str_starts_with($amountStr, '-');

                // Remove leading + if present (keeps -)
                $amountStr = ltrim($amountStr, '+');

                if (!is_numeric($amountStr)) {
                    $this->importErrors[] = [
                        'row' => $rowNumber,
                        'uuid' => $uuid,
                        'error' => "Monto inválido: '{$monto}'. Use números positivos para cargar (500 o +500) y negativos para descontar (-200)",
                        'data' => $row->toArray(),
                    ];
                    continue;
                }

                $amount = (float) $amountStr;

                if ($amount == 0) {
                    $this->importErrors[] = [
                        'row' => $rowNumber,
                        'uuid' => $uuid,
                        'error' => 'El monto no puede ser cero',
                        'data' => $row->toArray(),
                    ];
                    continue;
                }

                // Positive numbers are credits, negative are debits
                $isCredit = $amount > 0;
                $absoluteAmount = abs($amount);

                // Handle branch for debits
                $branchId = null;
                if (!$isCredit) {
                    if (empty($row['sucursal'])) {
                        $this->importErrors[] = [
                            'row' => $rowNumber,
                            'uuid' => $uuid,
                            'error' => 'Sucursal es requerida para descuentos (montos negativos)',
                            'data' => $row->toArray(),
                        ];
                        continue;
                    }

                    $branch = Branch::where('name', $row['sucursal'])
                        ->orWhere('id', $row['sucursal'])
                        ->first();

                    if (!$branch) {
                        $this->importErrors[] = [
                            'row' => $rowNumber,
                            'uuid' => $uuid,
                            'error' => "Sucursal '{$row['sucursal']}' no encontrada",
                            'data' => $row->toArray(),
                        ];
                        continue;
                    }

                    $branchId = $branch->id;
                }

                // Get description or generate default
                $description = !empty($row['descripcion'])
                    ? 'Carga masiva: ' . $row['descripcion']
                    : 'Carga masiva del ' . now()->format('d/m/Y');

                // Refresh gift card to get latest balance
                $giftCard->refresh();
                $balanceBefore = $giftCard->balance ?? 0;

                // Execute transaction
                try {
                    if ($isCredit) {
                        $transaction = $this->transactionService->credit(
                            $giftCard,
                            $absoluteAmount,
                            $description,
                            $this->adminUserId,
                            $branchId
                        );
                        $this->totalCredited += $absoluteAmount;
                    } else {
                        $transaction = $this->transactionService->debit(
                            $giftCard,
                            $absoluteAmount,
                            $description,
                            $this->adminUserId,
                            $branchId
                        );
                        $this->totalDebited += $absoluteAmount;
                    }

                    // Track processed
                    $this->processed[] = [
                        'row' => $rowNumber,
                        'uuid' => $uuid,
                        'legacy_id' => $giftCard->legacy_id,
                        'employee' => $giftCard->user?->name ?? 'Sin asignar',
                        'type' => $isCredit ? 'Carga' : 'Descuento',
                        'amount' => $amount,
                        'balance_before' => $balanceBefore,
                        'balance_after' => $transaction->balance_after,
                        'branch' => $branchId ? Branch::find($branchId)->name : 'N/A',
                    ];

                    $processedUuids[] = $uuid;

                } catch (\InvalidArgumentException $e) {
                    $this->importErrors[] = [
                        'row' => $rowNumber,
                        'uuid' => $uuid,
                        'error' => $e->getMessage(),
                        'data' => $row->toArray(),
                    ];
                }

            } catch (\Exception $e) {
                $this->importErrors[] = [
                    'row' => $rowNumber,
                    'uuid' => $row['uuid'] ?? 'N/A',
                    'error' => $e->getMessage(),
                    'data' => $row->toArray(),
                ];
            }
        }
    }

    public function chunkSize(): int
    {
        return 100;
    }

    public function getErrors(): array
    {
        return $this->importErrors;
    }

    public function getProcessed(): array
    {
        return $this->processed;
    }

    public function getStats(): array
    {
        return [
            'processed' => count($this->processed),
            'errors' => count($this->importErrors),
            'total_credited' => $this->totalCredited,
            'total_debited' => $this->totalDebited,
            'net_change' => $this->totalCredited - $this->totalDebited,
            'total' => count($this->processed) + count($this->importErrors),
        ];
    }
}
