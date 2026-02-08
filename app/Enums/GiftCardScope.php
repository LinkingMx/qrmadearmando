<?php

namespace App\Enums;

enum GiftCardScope: string
{
    case CHAIN = 'chain';
    case BRAND = 'brand';
    case BRANCH = 'branch';

    /**
     * Get the human-readable label in Spanish.
     */
    public function label(): string
    {
        return match ($this) {
            self::CHAIN => 'Cadena (Todas las sucursales)',
            self::BRAND => 'Marca (Sucursales de la marca)',
            self::BRANCH => 'Sucursal (Específica)',
        };
    }

    /**
     * Get all options as an associative array for Filament Select fields.
     */
    public static function options(): array
    {
        return [
            self::CHAIN->value => self::CHAIN->label(),
            self::BRAND->value => self::BRAND->label(),
            self::BRANCH->value => self::BRANCH->label(),
        ];
    }
}
