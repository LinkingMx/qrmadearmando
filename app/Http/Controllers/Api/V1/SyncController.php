<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\GiftCard;
use App\Models\Transaction;
use App\Services\TransactionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SyncController extends Controller
{
    public function __construct(
        private TransactionService $transactionService
    ) {}

    /**
     * Sync pending transactions from offline clients
     * Each transaction is processed and recorded
     */
    public function syncTransactions(Request $request): JsonResponse
    {
        try {
            // Validate offline transaction data
            $validated = $request->validate([
                'offline_id' => 'required|string|uuid',
                'legacy_id' => 'required|string',
                'amount' => 'required|numeric|min:0.01',
                'description' => 'nullable|string|max:255',
            ]);

            // Check if transaction was already processed
            $existingTransaction = Transaction::where('offline_id', $validated['offline_id'])
                ->first();

            if ($existingTransaction) {
                // Already processed - return the existing transaction
                return response()->json([
                    'data' => [
                        'id' => $existingTransaction->id,
                        'gift_card_id' => $existingTransaction->gift_card_id,
                        'type' => $existingTransaction->type,
                        'amount' => $existingTransaction->amount,
                        'balance_before' => $existingTransaction->balance_before,
                        'balance_after' => $existingTransaction->balance_after,
                        'created_at' => $existingTransaction->created_at->timestamp,
                        'synced' => true,
                    ],
                    'message' => 'Transacción ya sincronizada',
                ], 200);
            }

            // Find gift card
            $giftCard = GiftCard::where('legacy_id', $validated['legacy_id'])->firstOrFail();

            // Verify card is active
            if ($giftCard->status === 'inactive') {
                return response()->json([
                    'error' => 'Gift card está inactivo',
                ], 403);
            }

            // Verify sufficient balance (for debits)
            if ($giftCard->balance < $validated['amount']) {
                return response()->json([
                    'error' => 'Saldo insuficiente',
                    'balance' => $giftCard->balance,
                    'requested' => $validated['amount'],
                ], 422);
            }

            // Process debit
            $transaction = $this->transactionService->debit(
                $giftCard,
                $validated['amount'],
                auth()->user(),
                $validated['description'] ?? 'Debit via offline sync',
                auth()->user()?->branch_id,
                $validated['offline_id'] // Store offline_id for idempotency
            );

            return response()->json([
                'data' => [
                    'id' => $transaction->id,
                    'gift_card_id' => $transaction->gift_card_id,
                    'type' => $transaction->type,
                    'amount' => $transaction->amount,
                    'balance_before' => $transaction->balance_before,
                    'balance_after' => $transaction->balance_after,
                    'created_at' => $transaction->created_at->timestamp,
                    'synced' => true,
                ],
                'message' => 'Transacción sincronizada exitosamente',
            ], 201)
                ->header('Cache-Control', 'no-cache, no-store, must-revalidate');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'error' => 'Gift card no encontrado',
            ], 404);
        } catch (\Exception $e) {
            \Log::error('Sync transaction error:', [
                'offline_id' => $validated['offline_id'] ?? null,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Error al sincronizar transacción',
            ], 500);
        }
    }

    /**
     * Get current sync status and pending transactions count
     */
    public function status(Request $request): JsonResponse
    {
        try {
            // Count unsynced transactions for the user
            $pendingCount = Transaction::where('synced', false)
                ->where('user_id', auth()->id())
                ->count();

            // Get total gift cards count
            $totalCards = GiftCard::where('status', 'active')->count();

            return response()->json([
                'data' => [
                    'pending_transactions' => $pendingCount,
                    'total_cards' => $totalCards,
                    'last_sync' => now()->timestamp,
                    'is_synced' => $pendingCount === 0,
                ],
            ])
                ->header('Cache-Control', 'no-cache, no-store, must-revalidate');
        } catch (\Exception $e) {
            \Log::error('Sync status error:', ['error' => $e->getMessage()]);

            return response()->json([
                'error' => 'Error al obtener estado de sincronización',
            ], 500);
        }
    }
}
