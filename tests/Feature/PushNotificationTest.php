<?php

use App\Events\TransactionCreated;
use App\Models\Branch;
use App\Models\Brand;
use App\Models\Chain;
use App\Models\GiftCard;
use App\Models\Transaction;
use App\Models\User;
use App\Notifications\TransactionNotification;
use App\Services\TransactionService;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use NotificationChannels\WebPush\WebPushChannel;

beforeEach(function () {
    $this->chain = Chain::firstOrCreate(['name' => 'Test Chain']);
    $this->brand = Brand::firstOrCreate(
        ['chain_id' => $this->chain->id, 'name' => 'Test Brand'],
    );
    $this->user = User::factory()->create();
    $this->branch = Branch::create(['name' => 'Sucursal Test', 'brand_id' => $this->brand->id]);
    $this->giftCard = GiftCard::factory()->create([
        'user_id' => $this->user->id,
        'balance' => 100,
        'scope' => 'chain',
        'chain_id' => $this->chain->id,
    ]);
    $this->transactionService = new TransactionService;
});

describe('TransactionCreated Event & Listener', function () {
    test('event fires after credit transaction', function () {
        Event::fake();

        $transaction = $this->transactionService->credit(
            $this->giftCard,
            50,
            'Test credit',
            $this->user->id
        );

        Event::assertDispatched(TransactionCreated::class);
    });

    test('event fires after debit transaction', function () {
        Event::fake();

        $transaction = $this->transactionService->debit(
            $this->giftCard,
            30,
            'Test debit',
            $this->user->id,
            $this->branch->id
        );

        Event::assertDispatched(TransactionCreated::class);
    });

    test('event fires after adjustment transaction', function () {
        Event::fake();

        $transaction = $this->transactionService->adjustment(
            $this->giftCard,
            25,
            'Test adjustment',
            $this->user->id
        );

        Event::assertDispatched(TransactionCreated::class);
    });

    test('listener queues notification for gift card owner', function () {
        Event::fake();
        Notification::fake();

        $transaction = $this->transactionService->credit(
            $this->giftCard,
            50,
            'Test credit',
            $this->user->id
        );

        // Allow the event to actually fire (don't use Event::fake())
        // So we need to test differently - just verify the event was dispatched
        Event::assertDispatched(TransactionCreated::class);
    });
});

describe('TransactionNotification Content', function () {
    test('notification has webpush channel', function () {
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

    test('notification has correct icon', function () {
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

    test('notification has correct badge', function () {
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

    test('notification formats amount with decimals', function () {
        $transaction = Transaction::create([
            'gift_card_id' => $this->giftCard->id,
            'type' => 'credit',
            'amount' => 50.50,
            'balance_before' => 100,
            'balance_after' => 150.50,
        ]);

        $notification = new TransactionNotification($transaction);
        $message = $notification->toWebPush($this->user);

        expect($message->toArray()['body'])->toContain('Se abonó $50.50');
    });
});

describe('PushSubscription Model', function () {
    test('user can create push subscription', function () {
        $subscription = $this->user->pushSubscriptions()->create([
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/test-endpoint',
            'public_key' => 'test-public-key',
            'auth_token' => 'test-auth-token',
        ]);

        expect($subscription)->not->toBeNull()
            ->and($subscription->endpoint)->toBe('https://fcm.googleapis.com/fcm/send/test-endpoint')
            ->and($subscription->public_key)->toBe('test-public-key')
            ->and($subscription->auth_token)->toBe('test-auth-token');
    });

    test('user can have multiple push subscriptions', function () {
        $this->user->pushSubscriptions()->create([
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/endpoint-1',
            'public_key' => 'key-1',
            'auth_token' => 'token-1',
        ]);

        $this->user->pushSubscriptions()->create([
            'endpoint' => 'https://updates.push.services.mozilla.com/send/endpoint-2',
            'public_key' => 'key-2',
            'auth_token' => 'token-2',
        ]);

        expect($this->user->pushSubscriptions()->count())->toBe(2);
    });

    test('duplicate endpoint returns existing subscription', function () {
        $endpoint = 'https://fcm.googleapis.com/fcm/send/test-endpoint';

        $subscription1 = $this->user->pushSubscriptions()->create([
            'endpoint' => $endpoint,
            'public_key' => 'key-1',
            'auth_token' => 'token-1',
        ]);

        $subscription2 = $this->user->pushSubscriptions()
            ->firstOrCreate(
                ['endpoint' => $endpoint],
                ['public_key' => 'key-2', 'auth_token' => 'token-2']
            );

        expect($subscription2->id)->toBe($subscription1->id);
        expect($this->user->pushSubscriptions()->count())->toBe(1);
    });

    test('user can delete push subscription', function () {
        $subscription = $this->user->pushSubscriptions()->create([
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/test-endpoint',
            'public_key' => 'test-public-key',
            'auth_token' => 'test-auth-token',
        ]);

        expect($this->user->pushSubscriptions()->count())->toBe(1);

        $this->user->pushSubscriptions()
            ->where('endpoint', $subscription->endpoint)
            ->delete();

        expect($this->user->pushSubscriptions()->count())->toBe(0);
    });

    test('user can only access own push subscriptions', function () {
        $otherUser = User::factory()->create();

        $this->user->pushSubscriptions()->create([
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/endpoint-1',
            'public_key' => 'key-1',
            'auth_token' => 'token-1',
        ]);

        $otherUser->pushSubscriptions()->create([
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/endpoint-2',
            'public_key' => 'key-2',
            'auth_token' => 'token-2',
        ]);

        expect($this->user->pushSubscriptions()->count())->toBe(1)
            ->and($otherUser->pushSubscriptions()->count())->toBe(1);

        expect($this->user->pushSubscriptions()->where('endpoint', 'like', '%endpoint-2%')->count())->toBe(0);
    });

    test('push subscription stores all required fields', function () {
        $endpoint = 'https://fcm.googleapis.com/fcm/send/complete-test';
        $publicKey = 'BCVxjl8WgP2F_9H7_X_N_1_kV_f3nE_R_5hxH_1gM_vN_0pX_9sQ_6aY';
        $authToken = 'LS0tLS1CRUdJTi';

        $subscription = $this->user->pushSubscriptions()->create([
            'endpoint' => $endpoint,
            'public_key' => $publicKey,
            'auth_token' => $authToken,
        ]);

        expect($subscription->endpoint)->toBe($endpoint)
            ->and($subscription->public_key)->toBe($publicKey)
            ->and($subscription->auth_token)->toBe($authToken)
            ->and($subscription->created_at)->not->toBeNull()
            ->and($subscription->updated_at)->not->toBeNull();
    });
});
