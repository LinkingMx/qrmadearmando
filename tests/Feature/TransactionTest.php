<?php

use App\Models\Branch;
use App\Models\GiftCard;
use App\Models\Transaction;
use App\Models\User;
use App\Services\TransactionService;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->branch = Branch::create(['name' => 'Sucursal Test']);
    $this->giftCard = GiftCard::factory()->create(['balance' => 100]);
    $this->transactionService = new TransactionService();
});

test('can credit balance to gift card', function () {
    $transaction = $this->transactionService->credit(
        $this->giftCard,
        50,
        'Test credit',
        $this->user->id
    );

    expect($transaction)->toBeInstanceOf(Transaction::class)
        ->and($transaction->type)->toBe('credit')
        ->and($transaction->amount)->toBe('50.00')
        ->and($transaction->balance_before)->toBe('100.00')
        ->and($transaction->balance_after)->toBe('150.00')
        ->and($this->giftCard->fresh()->balance)->toBe('150.00');
});

test('can debit balance from gift card', function () {
    $transaction = $this->transactionService->debit(
        $this->giftCard,
        30,
        'Test debit',
        $this->user->id,
        $this->branch->id
    );

    expect($transaction)->toBeInstanceOf(Transaction::class)
        ->and($transaction->type)->toBe('debit')
        ->and($transaction->amount)->toBe('30.00')
        ->and($transaction->balance_before)->toBe('100.00')
        ->and($transaction->balance_after)->toBe('70.00')
        ->and($transaction->branch_id)->toBe($this->branch->id)
        ->and($this->giftCard->fresh()->balance)->toBe('70.00');
});

test('debit requires branch', function () {
    $this->transactionService->debit(
        $this->giftCard,
        30,
        'Test debit without branch',
        $this->user->id,
        null
    );
})->throws(InvalidArgumentException::class, 'Branch is required');

test('cannot debit more than available balance', function () {
    $this->transactionService->debit(
        $this->giftCard,
        150,
        'Test overdraft',
        $this->user->id,
        $this->branch->id
    );
})->throws(InvalidArgumentException::class, 'Insufficient balance');

test('can make positive adjustment', function () {
    $transaction = $this->transactionService->adjustment(
        $this->giftCard,
        25,
        'Test positive adjustment',
        $this->user->id
    );

    expect($transaction)->toBeInstanceOf(Transaction::class)
        ->and($transaction->type)->toBe('adjustment')
        ->and($transaction->amount)->toBe('25.00')
        ->and($transaction->balance_before)->toBe('100.00')
        ->and($transaction->balance_after)->toBe('125.00')
        ->and($this->giftCard->fresh()->balance)->toBe('125.00');
});

test('can make negative adjustment', function () {
    $transaction = $this->transactionService->adjustment(
        $this->giftCard,
        -40,
        'Test negative adjustment',
        $this->user->id,
        $this->branch->id
    );

    expect($transaction)->toBeInstanceOf(Transaction::class)
        ->and($transaction->type)->toBe('adjustment')
        ->and($transaction->amount)->toBe('-40.00')
        ->and($transaction->balance_before)->toBe('100.00')
        ->and($transaction->balance_after)->toBe('60.00')
        ->and($transaction->branch_id)->toBe($this->branch->id)
        ->and($this->giftCard->fresh()->balance)->toBe('60.00');
});

test('negative adjustment requires branch', function () {
    $this->transactionService->adjustment(
        $this->giftCard,
        -40,
        'Test negative adjustment without branch',
        $this->user->id,
        null
    );
})->throws(InvalidArgumentException::class, 'Branch is required');

test('positive adjustment does not require branch', function () {
    $transaction = $this->transactionService->adjustment(
        $this->giftCard,
        50,
        'Test positive adjustment without branch',
        $this->user->id,
        null
    );

    expect($transaction)->toBeInstanceOf(Transaction::class)
        ->and($transaction->branch_id)->toBeNull();
});

test('cannot make adjustment that results in negative balance', function () {
    $this->transactionService->adjustment(
        $this->giftCard,
        -150,
        'Test overdraft adjustment',
        $this->user->id,
        $this->branch->id
    );
})->throws(InvalidArgumentException::class, 'negative balance');

test('cannot credit with zero or negative amount', function () {
    $this->transactionService->credit(
        $this->giftCard,
        0,
        'Test zero credit',
        $this->user->id
    );
})->throws(InvalidArgumentException::class, 'greater than zero');

test('cannot debit with zero or negative amount', function () {
    $this->transactionService->debit(
        $this->giftCard,
        -10,
        'Test negative debit',
        $this->user->id
    );
})->throws(InvalidArgumentException::class, 'greater than zero');

test('transaction stores admin user id', function () {
    $transaction = $this->transactionService->credit(
        $this->giftCard,
        50,
        'Test admin tracking',
        $this->user->id
    );

    expect($transaction->admin_user_id)->toBe($this->user->id)
        ->and($transaction->admin)->toBeInstanceOf(User::class)
        ->and($transaction->admin->id)->toBe($this->user->id);
});

test('transaction can be created without admin user', function () {
    $transaction = $this->transactionService->credit(
        $this->giftCard,
        50,
        'Test no admin',
        null
    );

    expect($transaction->admin_user_id)->toBeNull();
});

test('transaction belongs to gift card', function () {
    $transaction = $this->transactionService->credit(
        $this->giftCard,
        50,
        'Test relationship',
        $this->user->id
    );

    expect($transaction->giftCard)->toBeInstanceOf(GiftCard::class)
        ->and($transaction->giftCard->id)->toBe($this->giftCard->id);
});

test('gift card has many transactions', function () {
    $this->transactionService->credit($this->giftCard, 50, 'Credit 1', $this->user->id);
    $this->transactionService->debit($this->giftCard, 20, 'Debit 1', $this->user->id, $this->branch->id);
    $this->transactionService->adjustment($this->giftCard, 10, 'Adjustment 1', $this->user->id);

    expect($this->giftCard->transactions()->count())->toBe(3)
        ->and($this->giftCard->transactions)->toHaveCount(3);
});

test('transaction belongs to branch', function () {
    $transaction = $this->transactionService->debit(
        $this->giftCard,
        30,
        'Test relationship',
        $this->user->id,
        $this->branch->id
    );

    expect($transaction->branch)->toBeInstanceOf(Branch::class)
        ->and($transaction->branch->id)->toBe($this->branch->id);
});

test('transaction has soft deletes', function () {
    $transaction = $this->transactionService->credit(
        $this->giftCard,
        50,
        'Test soft delete',
        $this->user->id
    );

    $transaction->delete();

    expect($transaction->trashed())->toBeTrue()
        ->and(Transaction::withTrashed()->find($transaction->id))->not->toBeNull();
});
