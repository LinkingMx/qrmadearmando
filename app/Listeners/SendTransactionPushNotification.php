<?php

namespace App\Listeners;

use App\Events\TransactionCreated;
use App\Notifications\TransactionNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;

class SendTransactionPushNotification implements ShouldQueue
{
    public function handle(TransactionCreated $event): void
    {
        $transaction = $event->transaction;
        $user = $transaction->giftCard->user;

        if ($user) {
            Notification::send($user, new TransactionNotification($transaction));
        }
    }
}
