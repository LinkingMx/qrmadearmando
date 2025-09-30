<?php

namespace App\Services;

use App\Models\GiftCard;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class TransactionService
{
    public function credit(GiftCard $giftCard, float $amount, string $description = null, ?int $adminUserId = null, ?int $branchId = null): Transaction
    {
        if ($amount <= 0) {
            throw new InvalidArgumentException('Amount must be greater than zero.');
        }

        return DB::transaction(function () use ($giftCard, $amount, $description, $adminUserId, $branchId) {
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
                'admin_user_id' => $adminUserId,
                'branch_id' => $branchId,
            ]);
        });
    }

    public function debit(GiftCard $giftCard, float $amount, string $description = null, ?int $adminUserId = null, ?int $branchId = null): Transaction
    {
        if ($amount <= 0) {
            throw new InvalidArgumentException('Amount must be greater than zero.');
        }

        if (!$branchId) {
            throw new InvalidArgumentException('Branch is required for debit transactions.');
        }

        return DB::transaction(function () use ($giftCard, $amount, $description, $adminUserId, $branchId) {
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
                'admin_user_id' => $adminUserId,
                'branch_id' => $branchId,
            ]);
        });
    }

    public function adjustment(GiftCard $giftCard, float $amount, string $description = null, ?int $adminUserId = null, ?int $branchId = null): Transaction
    {
        // Branch is required only when reducing balance (negative amount)
        if ($amount < 0 && !$branchId) {
            throw new InvalidArgumentException('Branch is required for adjustments that reduce balance.');
        }

        return DB::transaction(function () use ($giftCard, $amount, $description, $adminUserId, $branchId) {
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
                'admin_user_id' => $adminUserId,
                'branch_id' => $branchId,
            ]);
        });
    }
}
