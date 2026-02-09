<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\GiftCard;
use App\Models\Transaction;
use App\Services\TransactionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class DebitController extends Controller
{
    use ApiResponse;

    public function __construct(
        private TransactionService $transactionService
    ) {}

    /**
     * Process a debit transaction
     * Validates balance and creates transaction record
     * Called by both online and offline clients
     */
    public function process(Request $request): JsonResponse
    {
        try {
            // Validate request
            $validated = $request->validate([
                'legacy_id' => 'required|string',
                'amount' => 'required|numeric|min:0.01',
                'description' => 'nullable|string|max:255',
            ]);

            // Find gift card by legacy_id
            $giftCard = GiftCard::where('legacy_id', $validated['legacy_id'])
                ->with('category')
                ->firstOrFail();

            // Verify card is active
            if (! $giftCard->status) {
                return $this->error(
                    'INACTIVE_CARD',
                    'Gift card está inactivo',
                    403
                );
            }

            // Verify sufficient balance
            if ($giftCard->balance < $validated['amount']) {
                return $this->error(
                    'INSUFFICIENT_BALANCE',
                    'Saldo insuficiente',
                    422,
                    [
                        'balance' => $giftCard->balance,
                        'requested' => $validated['amount'],
                    ]
                );
            }

            // Process debit using transaction service
            $transaction = $this->transactionService->debit(
                $giftCard,
                $validated['amount'],
                auth()->user(),
                $validated['description'] ?? 'Debit via API',
                auth()->user()?->branch_id
            );

            // Return transaction with updated balance
            return $this->success(
                [
                    'id' => $transaction->id,
                    'gift_card_id' => $transaction->gift_card_id,
                    'type' => $transaction->type,
                    'amount' => (float) $transaction->amount,
                    'balance_before' => (float) $transaction->balance_before,
                    'balance_after' => (float) $transaction->balance_after,
                    'description' => $transaction->description,
                    'created_at' => $transaction->created_at->timestamp,
                    'synced' => true,
                ],
                null,
                201
            )
                ->header('Cache-Control', 'no-cache, no-store, must-revalidate');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound('Gift card');
        } catch (ValidationException $e) {
            return $this->validationError($e->errors(), 'Validación fallida');
        } catch (\Exception $e) {
            \Log::error('Debit processing error:', [
                'legacy_id' => $validated['legacy_id'] ?? null,
                'error' => $e->getMessage(),
            ]);

            return $this->error(
                'PROCESSING_ERROR',
                'Error al procesar débito',
                500
            );
        }
    }
}
