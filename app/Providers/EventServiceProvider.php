<?php

namespace App\Providers;

use App\Events\TransactionCreated;
use App\Listeners\SendTransactionPushNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        TransactionCreated::class => [
            SendTransactionPushNotification::class,
        ],
    ];

    public function boot(): void {}

    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
