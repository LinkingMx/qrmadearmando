<?php

namespace App\Notifications;

use App\Models\Transaction;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

class TransactionNotification extends Notification
{
    public function __construct(
        private Transaction $transaction
    ) {}

    public function via($notifiable): array
    {
        return [WebPushChannel::class];
    }

    public function toWebPush($notifiable): WebPushMessage
    {
        $type = $this->transaction->type;
        $amount = number_format($this->transaction->amount, 2);
        $balance = number_format($this->transaction->balance_after, 2);

        $bodyMap = [
            'debit' => "Se realizó un cargo de \$$amount en tu tarjeta. Saldo: \$$balance",
            'credit' => "Se abonó \$$amount a tu tarjeta. Saldo: \$$balance",
            'adjustment' => "Se realizó un ajuste de \$$amount en tu tarjeta. Saldo: \$$balance",
        ];

        return (new WebPushMessage)
            ->title('Tu tarjeta de regalo')
            ->body($bodyMap[$type] ?? 'Transacción realizada')
            ->icon('/icons/icon-192x192.png')
            ->badge('/favicon.svg')
            ->action('Ver', '/dashboard')
            ->tag('transaction-notification');
    }
}
