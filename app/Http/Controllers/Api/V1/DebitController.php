<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\GiftCard;
use App\Models\Transaction;
use App\Services\TransactionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class DebitController extends Controller
{
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
                return response()->json([
                    'error' => 'Gift card está inactivo',
                ], 403);
            }

            // Verify sufficient balance
            if ($giftCard->balance < $validated['amount']) {
                return response()->json([
                    'error' => 'Saldo insuficiente',
                    'balance' => $giftCard->balance,
                    'requested' => $validated['amount'],
                ], 422);
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
            return response()->json([
                'data' => [
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
            ], 201)
                ->header('Cache-Control', 'no-cache, no-store, must-revalidate');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'error' => 'Gift card no encontrado',
            ], 404);
        } catch (ValidationException $e) {
            return response()->json([
                'error' => 'Validación fallida',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Debit processing error:', [
                'legacy_id' => $validated['legacy_id'] ?? null,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Error al procesar débito',
            ], 500);
        }
    }
}
