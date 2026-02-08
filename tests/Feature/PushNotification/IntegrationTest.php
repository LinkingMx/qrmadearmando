<?php

use App\Models\Branch;
use App\Models\Brand;
use App\Models\Chain;
use App\Models\GiftCard;
use App\Models\Transaction;
use App\Models\User;
use App\Notifications\TransactionNotification;
use App\Services\TransactionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use NotificationChannels\WebPush\WebPushChannel;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->chain = Chain::firstOrCreate(['name' => 'Test Chain']);
    $this->brand = Brand::firstOrCreate(
        ['chain_id' => $this->chain->id, 'name' => 'Test Brand'],
    );
    $this->user = User::factory()->create(['is_active' => true]);
    $this->branch = Branch::create(['name' => 'Test Branch', 'brand_id' => $this->brand->id]);
    $this->giftCard = GiftCard::factory()->create([
        'user_id' => $this->user->id,
        'balance' => 100,
        'scope' => 'chain',
        'chain_id' => $this->chain->id,
    ]);
    $this->transactionService = new TransactionService;

    // Create a push subscription for the user
    $this->user->pushSubscriptions()->create([
        'endpoint' => 'https://fcm.googleapis.com/fcm/send/test-token',
        'public_key' => 'test-public-key',
        'auth_token' => 'test-auth-token',
    ]);
});

describe('Push Notification Transaction Integration', function () {
    beforeEach(function () {
        // Disable transaction event listener to prevent WebPush from trying to send notifications
        // VAPID keys are not configured in test environment
        \Illuminate\Support\Facades\Event::fake(\App\Events\TransactionCreated::class);
    });

    test('credit transaction creates transaction with correct data', function () {
        $initialBalance = (float) $this->giftCard->balance;

        $transaction = $this->transactionService->credit(
            $this->giftCard,
            50,
            'Test credit',
            $this->user->id
        );

        expect($transaction)->not->toBeNull();
        expect($transaction->type)->toBe('credit');
        expect((float) $transaction->amount)->toBe(50.00);
        expect((float) $transaction->balance_before)->toBe($initialBalance);
        expect((float) $transaction->balance_after)->toBe($initialBalance + 50);
    });

    test('debit transaction creates transaction with correct data', function () {
        $initialBalance = (float) $this->giftCard->balance;

        $transaction = $this->transactionService->debit(
            $this->giftCard,
            30,
            'Test debit',
            $this->user->id,
            $this->branch->id
        );

        expect($transaction)->not->toBeNull();
        expect($transaction->type)->toBe('debit');
        expect((float) $transaction->amount)->toBe(30.00);
        expect((float) $transaction->balance_before)->toBe($initialBalance);
        expect((float) $transaction->balance_after)->toBe($initialBalance - 30);
    });

    test('adjustment transaction creates transaction with correct data', function () {
        $initialBalance = (float) $this->giftCard->balance;

        $transaction = $this->transactionService->adjustment(
            $this->giftCard,
            25,
            'Test adjustment',
            $this->user->id
        );

        expect($transaction)->not->toBeNull();
        expect($transaction->type)->toBe('adjustment');
        expect((float) $transaction->amount)->toBe(25.00);
        expect((float) $transaction->balance_before)->toBe($initialBalance);
        expect((float) $transaction->balance_after)->toBe($initialBalance + 25);
    });

    test('multiple transactions update balance correctly', function () {
        $initialBalance = (float) $this->giftCard->balance;

        $this->transactionService->credit($this->giftCard, 50, 'Credit 1', $this->user->id);
        $this->transactionService->debit($this->giftCard, 30, 'Debit 1', $this->user->id, $this->branch->id);
        $this->transactionService->adjustment($this->giftCard, 10, 'Adjustment 1', $this->user->id);

        $expectedBalance = $initialBalance + 50 - 30 + 10;
        expect((float) $this->giftCard->fresh()->balance)->toBe($expectedBalance);
    });

    test('user with no push subscriptions can still receive transactions', function () {
        $userWithoutSubscription = User::factory()->create(['is_active' => true]);
        $giftCard = GiftCard::factory()->create([
            'user_id' => $userWithoutSubscription->id,
            'balance' => 100,
            'scope' => 'chain',
            'chain_id' => $this->chain->id,
        ]);

        $transaction = $this->transactionService->credit(
            $giftCard,
            50,
            'Test credit',
            $userWithoutSubscription->id
        );

        expect($transaction)->not->toBeNull();
        expect((float) $giftCard->fresh()->balance)->toBe(150.00);
    });

    test('transaction stores correct admin user and branch information', function () {
        $transaction = $this->transactionService->debit(
            $this->giftCard,
            30,
            'Test debit',
            $this->user->id,
            $this->branch->id
        );

        expect($transaction->admin_user_id)->toBe($this->user->id);
        expect($transaction->branch_id)->toBe($this->branch->id);
    });

    test('subscription persists across multiple transactions', function () {
        expect($this->user->pushSubscriptions()->count())->toBe(1);

        for ($i = 0; $i < 5; $i++) {
            $this->transactionService->credit(
                $this->giftCard,
                10,
                "Credit $i",
                $this->user->id
            );
        }

        // Subscription should still exist
        expect($this->user->pushSubscriptions()->count())->toBe(1);
        expect($this->giftCard->transactions()->count())->toBe(5);
    });
});

describe('TransactionNotification Content', function () {
    test('transaction notification has webpush channel', function () {
        $transaction = Transaction::create([
            'gift_card_id' => $this->giftCard->id,
            'type' => 'credit',
            'amount' => 50,
            'balance_before' => 100,
            'balance_after' => 150,
        ]);

        $notification = new TransactionNotification($transaction);
        expect($notification->via($this->user))->toContain(WebPushChannel::class);
    });

    test('debit notification has correct content', function () {
        $transaction = Transaction::create([
            'gift_card_id' => $this->giftCard->id,
            'type' => 'debit',
            'amount' => 30,
            'balance_before' => 100,
            'balance_after' => 70,
        ]);

        $notification = new TransactionNotification($transaction);
        $message = $notification->toWebPush($this->user);

        expect($message->toArray()['body'])->toContain('Se realizó un cargo de $30.00')
            ->and($message->toArray()['body'])->toContain('Saldo: $70.00');
    });

    test('credit notification has correct content', function () {
        $transaction = Transaction::create([
            'gift_card_id' => $this->giftCard->id,
            'type' => 'credit',
            'amount' => 50,
            'balance_before' => 100,
            'balance_after' => 150,
        ]);

        $notification = new TransactionNotification($transaction);
        $message = $notification->toWebPush($this->user);

        expect($message->toArray()['body'])->toContain('Se abonó $50.00')
            ->and($message->toArray()['body'])->toContain('Saldo: $150.00');
    });

    test('adjustment notification has correct content', function () {
        $transaction = Transaction::create([
            'gift_card_id' => $this->giftCard->id,
            'type' => 'adjustment',
            'amount' => 25,
            'balance_before' => 100,
            'balance_after' => 125,
        ]);

        $notification = new TransactionNotification($transaction);
        $message = $notification->toWebPush($this->user);

        expect($message->toArray()['body'])->toContain('Se realizó un ajuste de $25.00')
            ->and($message->toArray()['body'])->toContain('Saldo: $125.00');
    });

    test('notification includes correct icon URL', function () {
        $transaction = Transaction::create([
            'gift_card_id' => $this->giftCard->id,
            'type' => 'credit',
            'amount' => 50,
            'balance_before' => 100,
            'balance_after' => 150,
        ]);

        $notification = new TransactionNotification($transaction);
        $message = $notification->toWebPush($this->user);

        expect($message->toArray()['icon'])->toBe('/icons/icon-192x192.png');
    });

    test('notification includes correct badge URL', function () {
        $transaction = Transaction::create([
            'gift_card_id' => $this->giftCard->id,
            'type' => 'credit',
            'amount' => 50,
            'balance_before' => 100,
            'balance_after' => 150,
        ]);

        $notification = new TransactionNotification($transaction);
        $message = $notification->toWebPush($this->user);

        expect($message->toArray()['badge'])->toBe('/favicon.svg');
    });

    test('notification has correct title', function () {
        $transaction = Transaction::create([
            'gift_card_id' => $this->giftCard->id,
            'type' => 'credit',
            'amount' => 50,
            'balance_before' => 100,
            'balance_after' => 150,
        ]);

        $notification = new TransactionNotification($transaction);
        $message = $notification->toWebPush($this->user);

        expect($message->toArray()['title'])->toBe('Tu tarjeta de regalo');
    });

    test('notification has tag for deduplication', function () {
        $transaction = Transaction::create([
            'gift_card_id' => $this->giftCard->id,
            'type' => 'credit',
            'amount' => 50,
            'balance_before' => 100,
            'balance_after' => 150,
        ]);

        $notification = new TransactionNotification($transaction);
        $message = $notification->toWebPush($this->user);

        expect($message->toArray()['tag'])->toBe('transaction-notification');
    });

    test('notification formats decimal amounts correctly', function () {
        $transaction = Transaction::create([
            'gift_card_id' => $this->giftCard->id,
            'type' => 'credit',
            'amount' => 50.75,
            'balance_before' => 100.25,
            'balance_after' => 151.00,
        ]);

        $notification = new TransactionNotification($transaction);
        $message = $notification->toWebPush($this->user);

        expect($message->toArray()['body'])->toContain('Se abonó $50.75');
    });
});
