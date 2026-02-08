# Backend Push Notification Integration Guide

**Version**: 1.0
**Last Updated**: 2026-02-08
**Status**: Production Ready

## Table of Contents

1. [Architecture Overview](#architecture-overview)
2. [Key Components](#key-components)
3. [Integration Points](#integration-points)
4. [Configuration](#configuration)
5. [Testing](#testing)
6. [Extending for Custom Notifications](#extending-for-custom-notifications)
7. [Troubleshooting](#troubleshooting)

---

## Architecture Overview

### Event Flow Diagram

```
┌──────────────────────────────────────────────────────────┐
│                    USER ACTION                            │
│          (Scanner debit, Admin credit, etc.)             │
└────────────────────────┬─────────────────────────────────┘
                         │
                         ▼
┌──────────────────────────────────────────────────────────┐
│              TransactionService                          │
│         (Wraps in DB::transaction())                    │
│         Creates Transaction model                        │
└────────────────────────┬─────────────────────────────────┘
                         │
                         ▼ (AFTER DB commit)
┌──────────────────────────────────────────────────────────┐
│          TransactionCreated::dispatch()                  │
│      (Event fires outside DB transaction)               │
└────────────────────────┬─────────────────────────────────┘
                         │
                         ▼
┌──────────────────────────────────────────────────────────┐
│    SendTransactionPushNotification (Listener)            │
│  Receives TransactionCreated event                       │
│  Gets gift card owner from transaction                   │
└────────────────────────┬─────────────────────────────────┘
                         │
                         ▼
┌──────────────────────────────────────────────────────────┐
│   Notification::send($user, new TransactionNotification)│
│   Queues notification for delivery                       │
└────────────────────────┬─────────────────────────────────┘
                         │
                         ▼
┌──────────────────────────────────────────────────────────┐
│         Queue Job: TransactionNotification               │
│   (Database queue: jobs table)                           │
│   Worker processes: php artisan queue:work              │
└────────────────────────┬─────────────────────────────────┘
                         │
                         ▼
┌──────────────────────────────────────────────────────────┐
│    WebPushChannel (Laravel Notification Channel)         │
│  Formats notification payload                            │
│  Signs with VAPID keys                                   │
│  Sends HTTP POST to push service endpoint               │
└────────────────────────┬─────────────────────────────────┘
                         │
                         ▼
┌──────────────────────────────────────────────────────────┐
│        Push Service (FCM/Mozilla/APNs)                   │
│   Relays notification to device                          │
│   Routes to correct Service Worker                       │
└────────────────────────┬─────────────────────────────────┘
                         │
                         ▼
┌──────────────────────────────────────────────────────────┐
│         Service Worker (Browser/Device)                  │
│   Receives 'push' event                                   │
│   Shows notification to user                             │
└──────────────────────────────────────────────────────────┘
```

### Component Responsibilities

| Component | Responsibility | File |
|-----------|-----------------|------|
| **TransactionService** | Create transaction, dispatch event | `app/Services/TransactionService.php` |
| **TransactionCreated** | Event class, carries transaction | `app/Events/TransactionCreated.php` |
| **SendTransactionPushNotification** | Listener, queues notification | `app/Listeners/SendTransactionPushNotification.php` |
| **TransactionNotification** | Notification content, WebPush channel | `app/Notifications/TransactionNotification.php` |
| **PushSubscriptionController** | API endpoints for subscribe/unsubscribe | `app/Http/Controllers/PushSubscriptionController.php` |
| **Database** | Stores push_subscriptions | `database/migrations/...push_subscriptions.php` |

---

## Key Components

### 1. Event: TransactionCreated

**Location**: `app/Events/TransactionCreated.php`

**Purpose**: Fired after a transaction is successfully created and committed to the database.

**Code**:

```php
<?php

namespace App\Events;

use App\Models\Transaction;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TransactionCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Transaction $transaction
    ) {}
}
```

**Key Design Decisions**:

- **Event is Serializable**: Uses `SerializesModels` so transaction can be passed through queue jobs
- **Fired Outside Transaction**: CRITICAL - Dispatch happens AFTER `DB::transaction()` completes to avoid holding database locks
- **Carries Transaction Object**: Full transaction model with relationships available to listeners

**When Fired**:

```php
// In TransactionService.php or ScannerController
DB::transaction(function () {
    $transaction = Transaction::create([...]);
    // DB operations, balance checks, etc.
});

// OUTSIDE the DB transaction block
TransactionCreated::dispatch($transaction);
```

### 2. Listener: SendTransactionPushNotification

**Location**: `app/Listeners/SendTransactionPushNotification.php`

**Purpose**: Listens for `TransactionCreated` events and queues push notifications.

**Code**:

```php
<?php

namespace App\Listeners;

use App\Events\TransactionCreated;
use App\Models\User;
use App\Notifications\TransactionNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class SendTransactionPushNotification implements ShouldQueue
{
    /**
     * Queue the notification for asynchronous delivery
     */
    public function handle(TransactionCreated $event): void
    {
        $transaction = $event->transaction;

        // Get the gift card owner
        $user = $transaction->giftCard?->user;

        if (!$user) {
            Log::warning('Transaction has no gift card owner', [
                'transaction_id' => $transaction->id,
            ]);
            return;
        }

        // Queue the push notification
        try {
            Notification::send($user, new TransactionNotification($transaction));

            Log::info('Push notification queued', [
                'user_id' => $user->id,
                'transaction_id' => $transaction->id,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to queue push notification', [
                'user_id' => $user->id,
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
```

**Key Design Decisions**:

- **Implements ShouldQueue**: Handles asynchronously to avoid blocking transaction processing
- **Checks User Exists**: Gift cards should belong to users, but defensive coding is safe
- **Logs Errors**: Helps with debugging and monitoring

**Error Handling**:

- If user has no push subscriptions, notification silently succeeds (no error)
- If WebPush fails, queue job retries (default: 3 attempts with exponential backoff)
- Logged for monitoring and debugging

### 3. Notification: TransactionNotification

**Location**: `app/Notifications/TransactionNotification.php`

**Purpose**: Defines notification content and channels.

**Code**:

```php
<?php

namespace App\Notifications;

use App\Models\Transaction;
use Illuminate\Notifications\Messages\WebPushMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\Channels\WebPushChannel;

class TransactionNotification extends Notification
{
    public function __construct(
        private Transaction $transaction
    ) {}

    /**
     * Determine which channels the notification should be sent on
     */
    public function via(object $notifiable): array
    {
        return [WebPushChannel::class];
    }

    /**
     * Define the web push representation
     */
    public function toWebPush(object $notifiable): WebPushMessage
    {
        $type = $this->transaction->type;
        $amount = $this->transaction->amount;
        $balance = $this->transaction->balance_after;

        // Transaction-specific message formatting
        $bodyMap = [
            'debit' => "Se realizó un cargo de \${$amount}. Saldo: \${$balance}",
            'credit' => "Se abonó \${$amount} a tu tarjeta. Saldo: \${$balance}",
            'adjustment' => "Se realizó un ajuste de \${$amount}. Saldo: \${$balance}",
        ];

        return WebPushMessage::create()
            ->title('Tu tarjeta de regalo')
            ->body($bodyMap[$type] ?? "Cambio en tu saldo: \${$balance}")
            ->icon('/icons/icon-192x192.png')
            ->badge('/favicon.svg')
            ->action('Ver', '/dashboard');
    }
}
```

**Key Design Decisions**:

- **Via WebPushChannel**: Only delivers via web push, not email or SMS
- **Spanish Content**: Fully localized Spanish messages
- **Fallback Messages**: Default message if transaction type is unknown
- **Consistent Formatting**: All messages follow: "Action of $Amount. Balance: $NewBalance"

**Message Types**:

| Type | Message | Icon |
|------|---------|------|
| **debit** | "Se realizó un cargo de $50.00. Saldo: $250.00" | `/icons/icon-192x192.png` |
| **credit** | "Se abonó $50.00 a tu tarjeta. Saldo: $250.00" | `/icons/icon-192x192.png` |
| **adjustment** | "Se realizó un ajuste de -$10.00. Saldo: $240.00" | `/icons/icon-192x192.png` |

### 4. Controller: PushSubscriptionController

**Location**: `app/Http/Controllers/PushSubscriptionController.php`

**Endpoints**:

#### Store (Subscribe)

```php
public function store(Request $request): JsonResponse
{
    $validated = $request->validate([
        'endpoint' => [
            'required',
            'string',
            'url',
            'regex:/^https:\/\/', // HTTPS only
            'max:2048',
            function ($attribute, $value, $fail) {
                // Whitelist known push services
                $allowedDomains = [
                    'fcm.googleapis.com',              // Google FCM
                    'updates.push.services.mozilla.com', // Mozilla
                    'api.push.apple.com',              // Apple
                ];

                $parsed = parse_url($value);
                $host = $parsed['host'] ?? '';

                $isValid = collect($allowedDomains)->some(
                    fn ($domain) => str_ends_with($host, $domain)
                );

                if (!$isValid) {
                    $fail('El endpoint debe ser de un servicio push conocido');
                }
            },
        ],
        'publicKey' => 'required|string|max:500',
        'authToken' => 'required|string|max:500',
    ]);

    // Store or update subscription
    $subscription = $request->user()->pushSubscriptions()
        ->firstOrCreate(
            ['endpoint' => $validated['endpoint']],
            [
                'public_key' => $validated['publicKey'],
                'auth_token' => $validated['authToken'],
            ]
        );

    // Log activity for audit trail
    activity()
        ->causedBy($request->user())
        ->withProperties([
            'service' => parse_url($validated['endpoint'], PHP_URL_HOST),
            'subscription_id' => $subscription->id,
        ])
        ->log('Push notification subscribed');

    return response()->json([
        'data' => [
            'id' => $subscription->id,
            'user_id' => $subscription->user_id,
            'endpoint' => $subscription->endpoint,
            'created_at' => $subscription->created_at,
        ],
        'message' => 'Notificaciones activadas',
    ], 201);
}
```

#### Destroy (Unsubscribe)

```php
public function destroy(Request $request): JsonResponse
{
    $validated = $request->validate([
        'endpoint' => 'required|string|url',
    ]);

    $subscription = $request->user()->pushSubscriptions()
        ->where('endpoint', $validated['endpoint'])
        ->first();

    if (!$subscription) {
        return response()->json([
            'message' => 'Suscripción no encontrada',
        ], 404);
    }

    // Double-check authorization
    if ($subscription->user_id !== $request->user()->id) {
        return response()->json([
            'message' => 'No autorizado',
        ], 403);
    }

    // Log activity for audit trail
    activity()
        ->causedBy($request->user())
        ->withProperties([
            'service' => parse_url($subscription->endpoint, PHP_URL_HOST),
            'subscription_id' => $subscription->id,
        ])
        ->log('Push notification unsubscribed');

    $subscription->delete();

    return response()->json([
        'message' => 'Notificaciones desactivadas',
    ]);
}
```

**Security Features**:

- HTTPS-only endpoints (production requirement)
- Known push service whitelist (prevents phishing)
- Authorization check (users can't delete others' subscriptions)
- Activity logging (audit trail of all changes)

---

## Integration Points

### 1. Triggering from TransactionService

**File**: `app/Services/TransactionService.php`

```php
use App\Events\TransactionCreated;

class TransactionService
{
    public function debit(GiftCard $giftCard, float $amount, Branch $branch): Transaction
    {
        $transaction = DB::transaction(function () use ($giftCard, $amount, $branch) {
            // Validate balance
            if ($giftCard->balance < $amount) {
                throw new InsufficientBalanceException();
            }

            // Create transaction record
            $transaction = Transaction::create([
                'gift_card_id' => $giftCard->id,
                'type' => 'debit',
                'amount' => $amount,
                'balance_before' => $giftCard->balance,
                'balance_after' => $giftCard->balance - $amount,
                'branch_id' => $branch->id,
                'admin_user_id' => auth()->id(),
            ]);

            // Update gift card balance
            $giftCard->update(['balance' => $transaction->balance_after]);

            return $transaction;
        });

        // IMPORTANT: Dispatch OUTSIDE transaction to avoid holding locks
        TransactionCreated::dispatch($transaction);

        return $transaction;
    }

    public function credit(GiftCard $giftCard, float $amount): Transaction
    {
        // Same pattern for credits and adjustments
    }
}
```

**Critical Pattern**: Event is dispatched OUTSIDE the `DB::transaction()` block. This prevents:
- Database locks from being held during notification processing
- Transaction rollbacks if notification delivery fails
- Deadlocks in high-concurrency scenarios

### 2. Registering the Listener

**File**: `app/Providers/EventServiceProvider.php` (or `bootstrap/app.php` in Laravel 12)

```php
use App\Events\TransactionCreated;
use App\Listeners\SendTransactionPushNotification;

use function Illuminate\Events\listen;

// In bootstrap/app.php or EventServiceProvider
listen(TransactionCreated::class, SendTransactionPushNotification::class);

// Or in EventServiceProvider:
protected $listen = [
    TransactionCreated::class => [
        SendTransactionPushNotification::class,
    ],
];
```

### 3. Routes Configuration

**File**: `routes/web.php`

```php
Route::middleware(['auth', 'verified'])->group(function () {
    // Push subscription endpoints
    Route::post('api/push-subscriptions',
        [PushSubscriptionController::class, 'store']);
    Route::delete('api/push-subscriptions',
        [PushSubscriptionController::class, 'destroy']);
});
```

**Middleware Requirements**:

- `auth`: User must be authenticated
- `verified`: User must have verified email (2FA requirement)

### 4. User Model Trait

**File**: `app/Models/User.php`

The `User` model uses the `Notifiable` trait from `laravel-notification-channels/webpush`:

```php
use NotificationChannels\WebPush\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    // Provides:
    // - $user->pushSubscriptions() relationship
    // - $user->notify() method for queueing notifications
}
```

---

## Configuration

### 1. Environment Variables

**File**: `.env`

```env
# VAPID keys for WebPush
# Generate with: php artisan webpush:vapid
VAPID_PUBLIC_KEY=BFp4+SJq7ythmV...
VAPID_PRIVATE_KEY=I7TKRj3l5xQ...
VAPID_SUBJECT=mailto:admin@selatravel.com

# Queue configuration
QUEUE_CONNECTION=database
QUEUE_MAX_ATTEMPTS=3
QUEUE_TIMEOUT=90
```

### 2. Queue Configuration

**File**: `config/queue.php`

```php
return [
    'default' => env('QUEUE_CONNECTION', 'database'),

    'connections' => [
        'database' => [
            'driver' => 'database',
            'table' => 'jobs',
            'queue' => 'default',
            'retry_after' => 90,
            'after_commit' => false,
        ],
    ],

    'failed' => [
        'database' => 'sqlite',
        'table' => 'failed_jobs',
    ],
];
```

### 3. WebPush Configuration

**File**: `config/webpush.php` (if you create it)

```php
return [
    'vapid' => [
        'subject' => env('VAPID_SUBJECT'),
        'public_key' => env('VAPID_PUBLIC_KEY'),
        'private_key' => env('VAPID_PRIVATE_KEY'),
    ],

    // Optional: HTTP client configuration
    'http' => [
        'timeout' => 10,
        'connect_timeout' => 5,
    ],
];
```

### 4. Generate VAPID Keys

**One-time setup**:

```bash
php artisan webpush:vapid

# Output:
# VAPID_PUBLIC_KEY=BFp4+SJq7ythmV...
# VAPID_PRIVATE_KEY=I7TKRj3l5xQ...
```

Keys are automatically added to `.env`.

---

## Testing

### 1. Unit Test: Notification Content

```php
<?php

namespace Tests\Unit;

use App\Models\Transaction;
use App\Models\GiftCard;
use App\Notifications\TransactionNotification;
use Illuminate\Notifications\Messages\WebPushMessage;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class TransactionNotificationTest extends TestCase
{
    public function test_debit_notification_content()
    {
        $transaction = Transaction::factory()->create([
            'type' => 'debit',
            'amount' => 50.00,
            'balance_after' => 250.00,
        ]);

        $notification = new TransactionNotification($transaction);
        $message = $notification->toWebPush($transaction->giftCard->user);

        $this->assertInstanceOf(WebPushMessage::class, $message);
        $this->assertStringContainsString('cargo', $message->body);
        $this->assertStringContainsString('50.00', $message->body);
        $this->assertStringContainsString('250.00', $message->body);
    }

    public function test_credit_notification_content()
    {
        $transaction = Transaction::factory()->create([
            'type' => 'credit',
            'amount' => 100.00,
            'balance_after' => 350.00,
        ]);

        $notification = new TransactionNotification($transaction);
        $message = $notification->toWebPush($transaction->giftCard->user);

        $this->assertStringContainsString('abonó', $message->body);
    }
}
```

### 2. Feature Test: Push Subscription Controller

```php
<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PushSubscriptionControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_subscribe()
    {
        $user = User::factory()->verified()->create();

        $response = $this->actingAs($user)
            ->post('/api/push-subscriptions', [
                'endpoint' => 'https://fcm.googleapis.com/fcm/send/abc123',
                'publicKey' => 'BIp4+SJq7ythmV...',
                'authToken' => 'k7xb8vfg3h...',
            ]);

        $response->assertStatus(201);
        $response->assertJsonPath('message', 'Notificaciones activadas');

        $this->assertDatabaseHas('push_subscriptions', [
            'user_id' => $user->id,
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/abc123',
        ]);
    }

    public function test_user_can_unsubscribe()
    {
        $user = User::factory()->verified()->create();
        $subscription = $user->pushSubscriptions()->create([
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/abc123',
            'public_key' => 'BIp4...',
            'auth_token' => 'k7xb...',
        ]);

        $response = $this->actingAs($user)
            ->delete('/api/push-subscriptions', [
                'endpoint' => 'https://fcm.googleapis.com/fcm/send/abc123',
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseMissing('push_subscriptions', ['id' => $subscription->id]);
    }

    public function test_requires_verified_email()
    {
        $user = User::factory()->create(); // Not verified

        $response = $this->actingAs($user)
            ->post('/api/push-subscriptions', [
                'endpoint' => 'https://fcm.googleapis.com/fcm/send/abc123',
                'publicKey' => 'BIp4+SJq7ythmV...',
                'authToken' => 'k7xb8vfg3h...',
            ]);

        $response->assertStatus(403);
    }

    public function test_invalid_push_service_rejected()
    {
        $user = User::factory()->verified()->create();

        $response = $this->actingAs($user)
            ->post('/api/push-subscriptions', [
                'endpoint' => 'https://evil.com/send/abc123',
                'publicKey' => 'BIp4...',
                'authToken' => 'k7xb...',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('endpoint');
    }
}
```

### 3. Feature Test: Event Dispatch

```php
<?php

namespace Tests\Feature;

use App\Events\TransactionCreated;
use App\Listeners\SendTransactionPushNotification;
use App\Models\GiftCard;
use App\Models\Transaction;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class TransactionCreatedEventTest extends TestCase
{
    public function test_transaction_created_event_is_dispatched()
    {
        Event::fake();

        $giftCard = GiftCard::factory()->create();

        // Create a transaction (simulate scanner debit)
        $transaction = Transaction::factory()->create([
            'gift_card_id' => $giftCard->id,
            'type' => 'debit',
        ]);

        TransactionCreated::dispatch($transaction);

        Event::assertDispatched(TransactionCreated::class);
    }

    public function test_listener_receives_event()
    {
        $user = User::factory()->verified()->create();
        $giftCard = GiftCard::factory()->create(['user_id' => $user->id]);

        Notification::fake();

        $transaction = Transaction::factory()->create([
            'gift_card_id' => $giftCard->id,
        ]);

        // Dispatch event
        TransactionCreated::dispatch($transaction);

        // Listener should queue notification
        Notification::assertSentTo($user, TransactionNotification::class);
    }
}
```

### 4. Running Tests

```bash
# Run all tests
composer test

# Run specific test file
vendor/bin/pest tests/Feature/PushSubscriptionControllerTest.php

# Run specific test
vendor/bin/pest tests/Feature/PushSubscriptionControllerTest.php --filter "user_can_subscribe"

# Watch mode
vendor/bin/pest --watch
```

---

## Extending for Custom Notifications

### Adding a New Notification Type

Suppose you want to notify users when their balance is low. Follow this pattern:

#### 1. Create New Event

**File**: `app/Events/LowBalanceThreshold.php`

```php
<?php

namespace App\Events;

use App\Models\GiftCard;
use Illuminate\Foundation\Events\Dispatchable;

class LowBalanceThreshold
{
    use Dispatchable;

    public function __construct(
        public GiftCard $giftCard,
        public float $previousBalance
    ) {}
}
```

#### 2. Create Listener

**File**: `app/Listeners/SendLowBalanceNotification.php`

```php
<?php

namespace App\Listeners;

use App\Events\LowBalanceThreshold;
use App\Notifications\LowBalanceNotification;
use Illuminate\Notifications\Notification;

class SendLowBalanceNotification
{
    public function handle(LowBalanceThreshold $event): void
    {
        $user = $event->giftCard->user;

        if ($user && $event->giftCard->balance < 10) {
            Notification::send($user, new LowBalanceNotification($event->giftCard));
        }
    }
}
```

#### 3. Create Notification

**File**: `app/Notifications/LowBalanceNotification.php`

```php
<?php

namespace App\Notifications;

use App\Models\GiftCard;
use Illuminate\Notifications\Messages\WebPushMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\Channels\WebPushChannel;

class LowBalanceNotification extends Notification
{
    public function __construct(private GiftCard $giftCard) {}

    public function via(object $notifiable): array
    {
        return [WebPushChannel::class];
    }

    public function toWebPush(object $notifiable): WebPushMessage
    {
        return WebPushMessage::create()
            ->title('Tu tarjeta de regalo')
            ->body("Tu saldo es bajo: \${$this->giftCard->balance}. ¡Recarga ahora!")
            ->icon('/icons/icon-192x192.png')
            ->badge('/favicon.svg')
            ->action('Recargar', '/dashboard?section=reload');
    }
}
```

#### 4. Register Listener

**File**: `bootstrap/app.php` or `EventServiceProvider.php`

```php
use App\Events\LowBalanceThreshold;
use App\Listeners\SendLowBalanceNotification;

listen(LowBalanceThreshold::class, SendLowBalanceNotification::class);
```

#### 5. Dispatch Event

```php
// In GiftCard model or wherever balance is updated
$previousBalance = $this->balance;
$this->update(['balance' => $newBalance]);

if ($newBalance < 10 && $previousBalance >= 10) {
    LowBalanceThreshold::dispatch($this, $previousBalance);
}
```

---

## Troubleshooting

### Queue Worker Not Running

```bash
# Check if worker is running
ps aux | grep "queue:work"

# Start worker
php artisan queue:work

# Or for production (supervisor)
sudo supervisorctl restart laravel-worker
```

**Symptom**: Notifications not being delivered even after subscription.

### WebPush Channel Error

```
Error: Unable to send the notification
InvalidPayloadException: Payload size must be less than 4096 bytes
```

**Solution**: Reduce notification body text or simplify message.

### Invalid VAPID Keys

```
Error: Invalid VAPID keys
```

**Solution**:

```bash
# Regenerate keys
php artisan webpush:vapid --force

# Verify .env has keys
grep VAPID .env
```

### Listener Not Firing

Check if listener is registered:

```php
// In bootstrap/app.php, verify the listen() call exists
// Or in EventServiceProvider, verify $listen array

// Test with:
Event::listen(TransactionCreated::class, function ($event) {
    \Log::info('Event received', ['transaction_id' => $event->transaction->id]);
});
```

### Push Subscription Not Saved

**Check**:

```bash
# Is user authenticated in the request?
# Is email verified?

# Query subscriptions
php artisan tinker
>>> User::find(5)->pushSubscriptions

# Should show subscription records
```

---

**Document Version**: 1.0
**Last Updated**: 2026-02-08
**Author**: Documentation Specialist
**Next**: See [Deployment Guide](../deployment/pwa-push-notifications.md)
