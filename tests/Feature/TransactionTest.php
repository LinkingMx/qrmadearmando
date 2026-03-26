<?php

use App\Models\Branch;
use App\Models\Brand;
use App\Models\Chain;
use App\Models\GiftCard;
use App\Models\Transaction;
use App\Models\User;
use App\Services\TransactionService;

beforeEach(function () {
    $this->chain = Chain::firstOrCreate(['name' => 'Test Chain']);
    $this->brand = Brand::firstOrCreate(
        ['chain_id' => $this->chain->id, 'name' => 'Test Brand'],
    );
    $this->user = User::factory()->create();
    $this->branch = Branch::create(['name' => 'Sucursal Test', 'brand_id' => $this->brand->id]);
    $this->giftCard = GiftCard::factory()->create([
        'balance' => 100,
        'scope' => 'chain',
        'chain_id' => $this->chain->id,
    ]);
    $this->transactionService = new TransactionService;
});

test('can credit balance to gift card', function () {
    $transaction = $this->transactionService->credit(
        $this->giftCard,
        50,
        $this->user,
        'Test credit'
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
        $this->user,
        'Test debit',
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
        $this->user,
        'Test debit without branch',
        null
    );
})->throws(InvalidArgumentException::class, 'Branch is required');

test('cannot debit more than available balance', function () {
    $this->transactionService->debit(
        $this->giftCard,
        150,
        $this->user,
        'Test overdraft',
        $this->branch->id
    );
})->throws(InvalidArgumentException::class, 'Insufficient balance');

test('can make positive adjustment', function () {
    $transaction = $this->transactionService->adjustment(
        $this->giftCard,
        25,
        $this->user,
        'Test positive adjustment'
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
        $this->user,
        'Test negative adjustment',
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
        $this->user,
        'Test negative adjustment without branch',
        null
    );
})->throws(InvalidArgumentException::class, 'Branch is required');

test('positive adjustment does not require branch', function () {
    $transaction = $this->transactionService->adjustment(
        $this->giftCard,
        50,
        $this->user,
        'Test positive adjustment without branch',
        null
    );

    expect($transaction)->toBeInstanceOf(Transaction::class)
        ->and($transaction->branch_id)->toBeNull();
});

test('cannot make adjustment that results in negative balance', function () {
    $this->transactionService->adjustment(
        $this->giftCard,
        -150,
        $this->user,
        'Test overdraft adjustment',
        $this->branch->id
    );
})->throws(InvalidArgumentException::class, 'negative balance');

test('cannot credit with zero or negative amount', function () {
    $this->transactionService->credit(
        $this->giftCard,
        0,
        $this->user,
        'Test zero credit'
    );
})->throws(InvalidArgumentException::class, 'greater than zero');

test('cannot debit with zero or negative amount', function () {
    $this->transactionService->debit(
        $this->giftCard,
        -10,
        $this->user,
        'Test negative debit'
    );
})->throws(InvalidArgumentException::class, 'greater than zero');

test('transaction stores admin user id', function () {
    $transaction = $this->transactionService->credit(
        $this->giftCard,
        50,
        $this->user,
        'Test admin tracking'
    );

    expect($transaction->admin_user_id)->toBe($this->user->id)
        ->and($transaction->admin)->toBeInstanceOf(User::class)
        ->and($transaction->admin->id)->toBe($this->user->id);
});

test('transaction can be created without admin user', function () {
    $transaction = $this->transactionService->credit(
        $this->giftCard,
        50,
        null,
        'Test no admin'
    );

    expect($transaction->admin_user_id)->toBeNull();
});

test('transaction belongs to gift card', function () {
    $transaction = $this->transactionService->credit(
        $this->giftCard,
        50,
        $this->user,
        'Test relationship'
    );

    expect($transaction->giftCard)->toBeInstanceOf(GiftCard::class)
        ->and($transaction->giftCard->id)->toBe($this->giftCard->id);
});

test('gift card has many transactions', function () {
    $this->transactionService->credit($this->giftCard, 50, $this->user, 'Credit 1');
    $this->transactionService->debit($this->giftCard, 20, $this->user, 'Debit 1', $this->branch->id);
    $this->transactionService->adjustment($this->giftCard, 10, $this->user, 'Adjustment 1');

    expect($this->giftCard->transactions()->count())->toBe(3)
        ->and($this->giftCard->transactions)->toHaveCount(3);
});

test('transaction belongs to branch', function () {
    $transaction = $this->transactionService->debit(
        $this->giftCard,
        30,
        $this->user,
        'Test relationship',
        $this->branch->id
    );

    expect($transaction->branch)->toBeInstanceOf(Branch::class)
        ->and($transaction->branch->id)->toBe($this->branch->id);
});

test('transaction has soft deletes', function () {
    $transaction = $this->transactionService->credit(
        $this->giftCard,
        50,
        $this->user,
        'Test soft delete'
    );

    $transaction->delete();

    expect($transaction->trashed())->toBeTrue()
        ->and(Transaction::withTrashed()->find($transaction->id))->not->toBeNull();
});
