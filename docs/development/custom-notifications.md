# Developer Guide - Adding Custom Notification Types

**Version**: 1.0
**Last Updated**: 2026-02-08
**Status**: Production Ready

## Table of Contents

1. [Quick Start](#quick-start)
2. [Step-by-Step Walkthrough](#step-by-step-walkthrough)
3. [Spanish Terminology](#spanish-terminology)
4. [Testing](#testing)
5. [Common Patterns](#common-patterns)
6. [Deployment Checklist](#deployment-checklist)

---

## Quick Start

To add a new notification type, follow this 4-step pattern:

```
Step 1: Create Event (defines what happened)
        └─> app/Events/YourEventName.php

Step 2: Create Listener (reacts to event)
        └─> app/Listeners/SendYourNotification.php

Step 3: Create Notification (formats message)
        └─> app/Notifications/YourNotification.php

Step 4: Register Listener (wire it all together)
        └─> bootstrap/app.php or EventServiceProvider
```

---

## Step-by-Step Walkthrough

### Example: "Low Balance" Notification

Suppose you want to notify users when their balance drops below $10.

#### Step 1: Create the Event

**File**: `app/Events/LowBalanceThreshold.php`

```php
<?php

namespace App\Events;

use App\Models\GiftCard;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LowBalanceThreshold
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param GiftCard $giftCard The gift card with low balance
     * @param float $previousBalance Balance before debit
     */
    public function __construct(
        public GiftCard $giftCard,
        public float $previousBalance
    ) {}
}
```

**Key Points**:
- Event carries **all data** needed by listeners
- Use `SerializesModels` if passing Eloquent models (for queued jobs)
- Document constructor parameters
- Keep events simple - just data containers

**When to dispatch**:

```php
// In GiftCard model or TransactionService
public function debit(float $amount): void
{
    $previousBalance = $this->balance;

    $this->update(['balance' => $this->balance - $amount]);

    // Dispatch ONLY if threshold crossed
    if ($this->balance < 10 && $previousBalance >= 10) {
        LowBalanceThreshold::dispatch($this, $previousBalance);
    }
}
```

#### Step 2: Create the Listener

**File**: `app/Listeners/SendLowBalanceNotification.php`

```php
<?php

namespace App\Listeners;

use App\Events\LowBalanceThreshold;
use App\Notifications\LowBalanceNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class SendLowBalanceNotification implements ShouldQueue
{
    /**
     * Handle the event and queue notification
     */
    public function handle(LowBalanceThreshold $event): void
    {
        $giftCard = $event->giftCard;
        $user = $giftCard->user;

        // Defensive: ensure user exists
        if (!$user) {
            Log::warning('Low balance threshold event has no user', [
                'gift_card_id' => $giftCard->id,
            ]);
            return;
        }

        // Queue the notification
        try {
            Notification::send($user, new LowBalanceNotification($giftCard));

            Log::info('Low balance notification queued', [
                'user_id' => $user->id,
                'gift_card_id' => $giftCard->id,
                'balance' => $giftCard->balance,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to queue low balance notification', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
```

**Key Points**:
- Implement `ShouldQueue` for async processing
- Always check that required relationships exist
- Log successes and failures for monitoring
- Use try-catch to prevent listener from crashing

**Listener Flow**:

```
Event fires
    ↓
Listener.handle() called
    ↓
Get user from relationship
    ↓
Check user exists (defensive)
    ↓
Notification::send()
    ↓
Job queued to "jobs" table
    ↓
Queue worker picks up job
    ↓
Notification sent to WebPushChannel
    ↓
Push service endpoint called
```

#### Step 3: Create the Notification

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
    /**
     * Create a new notification instance.
     */
    public function __construct(
        private GiftCard $giftCard
    ) {}

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return [WebPushChannel::class];
    }

    /**
     * Format the notification for Web Push.
     */
    public function toWebPush(object $notifiable): WebPushMessage
    {
        $balance = $this->giftCard->balance;

        return WebPushMessage::create()
            ->title('Tu tarjeta de regalo')
            ->body("⚠️ Tu saldo es bajo: \${$balance}. ¡Recarga ahora!")
            ->icon('/icons/icon-192x192.png')
            ->badge('/favicon.svg')
            ->action('Recargar', '/dashboard?section=balance');
    }
}
```

**Key Points**:
- `via()` returns which channels to use (only WebPushChannel for push notifications)
- `toWebPush()` formats the message
- Return `WebPushMessage` object with:
  - `title()` - Short notification title
  - `body()` - Main message (max ~100 chars)
  - `icon()` - Large icon (192x192 PNG)
  - `badge()` - Small badge (SVG)
  - `action()` - Button with label and action URL

**Message Structure**:

```
Title: "Tu tarjeta de regalo"
Body:  "⚠️ Tu saldo es bajo: $5.00. ¡Recarga ahora!"
Icon:  /icons/icon-192x192.png (large image)
Badge: /favicon.svg (small badge)
Click → Opens /dashboard?section=balance
```

#### Step 4: Register the Listener

**File**: `bootstrap/app.php` (Laravel 12)

```php
use App\Events\LowBalanceThreshold;
use App\Listeners\SendLowBalanceNotification;

return Application::configure(basePath: dirname(__DIR__))
    // ... other config ...
    ->withEvents(
        discover: 'app/Listeners',  // Auto-discover in Listeners dir
        register: [
            LowBalanceThreshold::class => [
                SendLowBalanceNotification::class,
            ],
        ],
    )
    // ...
    ->create();
```

**Or in EventServiceProvider** (`app/Providers/EventServiceProvider.php`):

```php
<?php

namespace App\Providers;

use App\Events\LowBalanceThreshold;
use App\Events\TransactionCreated;
use App\Listeners\SendLowBalanceNotification;
use App\Listeners\SendTransactionPushNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        TransactionCreated::class => [
            SendTransactionPushNotification::class,
        ],

        LowBalanceThreshold::class => [
            SendLowBalanceNotification::class,
        ],
    ];
}
```

---

## Spanish Terminology

When creating notification messages, use consistent Spanish terminology:

### Balance/Money Terms

| English | Spanish | Context |
|---------|---------|---------|
| Balance | Saldo | Current balance amount |
| Charge | Cargo | Money deducted |
| Credit | Abono | Money added |
| Adjustment | Ajuste | Manual adjustment |
| Amount | Monto | Transaction amount |
| Currency | Divisa | $ (dollar) |

### Common Phrases

| English | Spanish |
|---------|---------|
| Your gift card | Tu tarjeta de regalo |
| Balance is low | Tu saldo es bajo |
| Reload now | Recarga ahora |
| Payment made | Pago realizado |
| Updated balance | Saldo actualizado |
| Transaction completed | Transacción completada |
| Please verify | Por favor verifica |
| Action required | Acción requerida |

### Example Messages

```php
// Charge
"Se realizó un cargo de \${$amount}. Saldo: \${$balance}"

// Credit
"Se abonó \${$amount} a tu tarjeta. Saldo: \${$balance}"

// Adjustment
"Se realizó un ajuste de \${$amount}. Saldo: \${$balance}"

// Low balance
"⚠️ Tu saldo es bajo: \${$balance}. ¡Recarga ahora!"

// Card expiration
"⏰ Tu tarjeta caduca el {$expiryDate}. Renuévala pronto."

// Transaction failed
"❌ La transacción falló. Por favor intenta de nuevo."
```

### Emoji Usage

Use minimal emoji for visual impact:

- ⚠️ Warnings
- ✅ Confirmations
- ❌ Errors
- ⏰ Time-related
- 📊 Balance/amount
- 🔔 Notifications
- 💳 Cards

---

## Testing

### Unit Test: Notification Content

```php
<?php

namespace Tests\Unit;

use App\Models\GiftCard;
use App\Notifications\LowBalanceNotification;
use Illuminate\Notifications\Messages\WebPushMessage;
use Tests\TestCase;

class LowBalanceNotificationTest extends TestCase
{
    public function test_notification_content()
    {
        $giftCard = GiftCard::factory()->create(['balance' => 5.00]);
        $notification = new LowBalanceNotification($giftCard);

        // Get the notifiable (user)
        $notifiable = $giftCard->user;

        // Generate WebPush message
        $message = $notification->toWebPush($notifiable);

        // Assertions
        $this->assertInstanceOf(WebPushMessage::class, $message);
        $this->assertStringContainsString('saldo es bajo', $message->body);
        $this->assertStringContainsString('5.00', $message->body);
        $this->assertStringContainsString('Recargar', $message->body);
    }

    public function test_via_returns_webpush_channel()
    {
        $giftCard = GiftCard::factory()->create();
        $notification = new LowBalanceNotification($giftCard);

        $channels = $notification->via($giftCard->user);

        $this->assertContains(
            'NotificationChannels\WebPush\Channels\WebPushChannel',
            $channels
        );
    }
}
```

### Feature Test: Event and Listener

```php
<?php

namespace Tests\Feature;

use App\Events\LowBalanceThreshold;
use App\Models\GiftCard;
use App\Models\User;
use App\Notifications\LowBalanceNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class LowBalanceNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_event_triggers_low_balance_notification()
    {
        Notification::fake();

        $user = User::factory()->create();
        $giftCard = GiftCard::factory()->create(['user_id' => $user->id]);

        // Dispatch the event
        LowBalanceThreshold::dispatch($giftCard, 15.00);

        // Verify notification was queued to correct user
        Notification::assertSentTo($user, LowBalanceNotification::class);
    }

    public function test_notification_not_sent_without_event()
    {
        Notification::fake();

        $user = User::factory()->create();
        $giftCard = GiftCard::factory()->create(['user_id' => $user->id]);

        // Don't dispatch event

        // Verify notification wasn't sent
        Notification::assertNotSentTo($user, LowBalanceNotification::class);
    }

    public function test_event_not_fired_if_threshold_not_crossed()
    {
        // If balance goes from $15 to $12, threshold not crossed yet
        // Event shouldn't fire
    }
}
```

### Run Tests

```bash
# Run all notification tests
vendor/bin/pest tests/Unit/LowBalanceNotificationTest.php
vendor/bin/pest tests/Feature/LowBalanceNotificationTest.php

# Run with coverage
vendor/bin/pest --coverage tests/Unit/LowBalanceNotificationTest.php

# Watch mode
vendor/bin/pest --watch tests/Unit/LowBalanceNotificationTest.php
```

---

## Common Patterns

### Pattern 1: Time-Based Notifications

**Example**: Notify user if card expires in 30 days

```php
// Event
class CardExpiryThreshold extends Event
{
    public function __construct(
        public GiftCard $giftCard,
        public Carbon $expiryDate
    ) {}
}

// In model: When to dispatch?
public function checkExpiryThreshold(): void
{
    $daysUntilExpiry = now()->diffInDays($this->expiry_date);

    // Fire exactly once when entering 30-day window
    if ($daysUntilExpiry === 30 && !$this->expiry_notified) {
        CardExpiryThreshold::dispatch($this, $this->expiry_date);
        $this->update(['expiry_notified' => true]);
    }
}

// Listener
public function handle(CardExpiryThreshold $event): void
{
    $daysUntilExpiry = now()->diffInDays($event->expiryDate);

    Notification::send(
        $event->giftCard->user,
        new CardExpiryNotification($event->giftCard, $daysUntilExpiry)
    );
}

// Notification
public function toWebPush(object $notifiable): WebPushMessage
{
    return WebPushMessage::create()
        ->title('Tu tarjeta de regalo')
        ->body("⏰ Tu tarjeta caduca el {$this->expiryDate}. Renuévala pronto.")
        ->action('Renovar', '/dashboard?section=renewal');
}
```

### Pattern 2: Threshold-Based Notifications

**Example**: Notify user if they've spent more than $100 this week

```php
// Event
class SpendingLimitExceeded extends Event
{
    public function __construct(
        public GiftCard $giftCard,
        public float $weeklySpent,
        public float $limit
    ) {}
}

// In TransactionService: Check after each debit
public function debit(GiftCard $giftCard, float $amount): void
{
    // ... create transaction ...

    $weeklySpent = $giftCard->transactions()
        ->where('type', 'debit')
        ->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])
        ->sum('amount');

    if ($weeklySpent > 100) {
        SpendingLimitExceeded::dispatch($giftCard, $weeklySpent, 100);
    }
}

// Notification
public function toWebPush(object $notifiable): WebPushMessage
{
    $overspent = $this->weeklySpent - $this->limit;

    return WebPushMessage::create()
        ->title('Límite de gastos')
        ->body("Has gastado \${$this->weeklySpent} esta semana. Límite: \${$this->limit}")
        ->icon('/icons/icon-192x192.png');
}
```

### Pattern 3: Action-Based Notifications

**Example**: Notify when suspicious activity detected

```php
// Event
class SuspiciousActivityDetected extends Event
{
    public function __construct(
        public GiftCard $giftCard,
        public string $description
    ) {}
}

// In SuspiciousActivityDetector service
public function detect(GiftCard $giftCard): void
{
    // Check for rapid debits (e.g., 5 debits in 1 minute)
    $recentDebits = $giftCard->transactions()
        ->where('type', 'debit')
        ->where('created_at', '>', now()->subMinute())
        ->count();

    if ($recentDebits > 5) {
        SuspiciousActivityDetected::dispatch(
            $giftCard,
            'Se detectaron múltiples cargos rápidos en tu tarjeta'
        );
    }
}

// Notification
public function toWebPush(object $notifiable): WebPushMessage
{
    return WebPushMessage::create()
        ->title('⚠️ Actividad inusual')
        ->body($this->description)
        ->icon('/icons/icon-192x192.png')
        ->action('Revisar', '/dashboard?section=security');
}
```

---

## Deployment Checklist

Before shipping a new notification type:

- [ ] **Event created and properly documented**
- [ ] **Listener created with error handling**
- [ ] **Notification created with Spanish messages**
- [ ] **Listener registered in EventServiceProvider or bootstrap/app.php**
- [ ] **Unit tests written for notification content**
- [ ] **Feature tests written for event dispatch**
- [ ] **All tests passing**: `vendor/bin/pest`
- [ ] **Code formatted**: `./vendor/bin/pint`
- [ ] **Manual testing on staging**:
  - [ ] Trigger the event manually
  - [ ] Verify notification queued
  - [ ] Verify notification appears on device
  - [ ] Verify action link works
- [ ] **Monitoring set up** (check logs for errors)
- [ ] **Documentation updated** (add to user guide if needed)
- [ ] **Rollback plan ready** (in case something breaks)

### Pre-Deployment Testing

```bash
# 1. Trigger event manually
php artisan tinker
>>> Event::dispatch(new App\Events\LowBalanceThreshold(GiftCard::first(), 20.00))

# 2. Check queue
>>> DB::table('jobs')->where('payload', 'like', '%LowBalance%')->first()

# 3. Process queue
php artisan queue:work --once

# 4. Check logs
>>> Activity::latest()->take(5)->get()

# 5. Verify on device
# Open app, check notification bell is green
# Create test scenario to trigger notification
# Verify notification appears on device
```

### Post-Deployment Monitoring

```bash
# Monitor new notification type
tail -f storage/logs/laravel.log | grep "LowBalance"

# Count successful deliveries
grep -c "Low balance notification queued" storage/logs/laravel.log

# Count failures
grep -c "Failed to queue low balance" storage/logs/laravel.log

# Set up alert
# Alert if failure rate > 5%
```

---

**Document Version**: 1.0
**Last Updated**: 2026-02-08
**Author**: Documentation Specialist
**Next**: See [Security & Compliance Documentation](../security/push-notifications.md)
