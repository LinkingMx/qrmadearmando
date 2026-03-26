<?php

namespace App\Services;

use App\Enums\GiftCardScope;
use App\Events\TransactionCreated;
use App\Models\Branch;
use App\Models\GiftCard;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class TransactionService
{
    public function credit(GiftCard $giftCard, float $amount, ?object $user = null, ?string $description = null, ?int $branchId = null, ?string $offlineId = null): Transaction
    {
        if ($amount <= 0) {
            throw new InvalidArgumentException('Amount must be greater than zero.');
        }

        $transaction = DB::transaction(function () use ($giftCard, $amount, $user, $description, $branchId, $offlineId) {
            $giftCard->refresh();
            $balanceBefore = $giftCard->balance ?? 0;
            $balanceAfter = $balanceBefore + $amount;

            $giftCard->update(['balance' => $balanceAfter]);

            return Transaction::create([
                'gift_card_id' => $giftCard->id,
                'type' => 'credit',
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'description' => $description,
                'admin_user_id' => $user?->id,
                'branch_id' => $branchId,
                'offline_id' => $offlineId,
            ]);
        });

        TransactionCreated::dispatch($transaction);

        return $transaction;
    }

    public function debit(GiftCard $giftCard, float $amount, ?object $user = null, ?string $description = null, ?int $branchId = null, ?string $offlineId = null): Transaction
    {
        if ($amount <= 0) {
            throw new InvalidArgumentException('Amount must be greater than zero.');
        }

        if (! $branchId) {
            throw new InvalidArgumentException('Branch is required for debit transactions.');
        }

        // Validate gift card scope against the branch
        $this->validateScope($giftCard, $branchId);

        $transaction = DB::transaction(function () use ($giftCard, $amount, $user, $description, $branchId, $offlineId) {
            $giftCard->refresh();
            $balanceBefore = $giftCard->balance ?? 0;
            $balanceAfter = $balanceBefore - $amount;

            if ($balanceAfter < 0) {
                throw new InvalidArgumentException('Insufficient balance. Transaction would result in negative balance.');
            }

            $giftCard->update(['balance' => $balanceAfter]);

            return Transaction::create([
                'gift_card_id' => $giftCard->id,
                'type' => 'debit',
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'description' => $description,
                'admin_user_id' => $user?->id,
                'branch_id' => $branchId,
                'offline_id' => $offlineId,
            ]);
        });

        TransactionCreated::dispatch($transaction);

        return $transaction;
    }

    public function adjustment(GiftCard $giftCard, float $amount, ?object $user = null, ?string $description = null, ?int $branchId = null, ?string $offlineId = null): Transaction
    {
        // Branch is required only when reducing balance (negative amount)
        if ($amount < 0 && ! $branchId) {
            throw new InvalidArgumentException('Branch is required for adjustments that reduce balance.');
        }

        $transaction = DB::transaction(function () use ($giftCard, $amount, $user, $description, $branchId, $offlineId) {
            $giftCard->refresh();
            $balanceBefore = $giftCard->balance ?? 0;
            $balanceAfter = $balanceBefore + $amount;

            if ($balanceAfter < 0) {
                throw new InvalidArgumentException('Adjustment would result in negative balance.');
            }

            $giftCard->update(['balance' => $balanceAfter]);

            return Transaction::create([
                'gift_card_id' => $giftCard->id,
                'type' => 'adjustment',
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'description' => $description,
                'admin_user_id' => $user?->id,
                'branch_id' => $branchId,
                'offline_id' => $offlineId,
            ]);
        });

        TransactionCreated::dispatch($transaction);

        return $transaction;
    }

    /**
     * Validate that the gift card can be used at the given branch based on its scope.
     */
    private function validateScope(GiftCard $giftCard, int $branchId): void
    {
        $branch = Branch::with('brand')->findOrFail($branchId);

        if (! $giftCard->canBeUsedAtBranch($branch)) {
            $scopeMessage = match ($giftCard->scope) {
                GiftCardScope::CHAIN => 'Este QR solo puede usarse en sucursales de la cadena asignada.',
                GiftCardScope::BRAND => 'Este QR solo puede usarse en sucursales de la marca asignada.',
                GiftCardScope::BRANCH => 'Este QR solo puede usarse en las sucursales específicas asignadas.',
                default => 'Este QR no puede usarse en esta sucursal.',
            };
            throw new InvalidArgumentException($scopeMessage);
        }
    }
}
