<?php

namespace App\Enums;

enum GiftCardNature: string
{
    case PAYMENT_METHOD = 'payment_method';
    case DISCOUNT = 'discount';

    /**
     * Get the human-readable label in Spanish.
     */
    public function label(): string
    {
        return match ($this) {
            self::PAYMENT_METHOD => 'Método de pago',
            self::DISCOUNT => 'Descuento',
        };
    }

    /**
     * Get all options as an associative array for Filament Select fields.
     */
    public static function options(): array
    {
        return [
            self::PAYMENT_METHOD->value => self::PAYMENT_METHOD->label(),
            self::DISCOUNT->value => self::DISCOUNT->label(),
        ];
    }
}
