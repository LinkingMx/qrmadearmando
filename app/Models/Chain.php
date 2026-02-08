<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Chain extends Model
{
    use HasFactory;

    protected $fillable = ['name'];

    protected static function booted(): void
    {
        // Prevent deletion if brands exist in this chain
        static::deleting(function ($chain) {
            if ($chain->brands()->exists()) {
                throw new \Exception(
                    "No se puede eliminar la cadena '{$chain->name}' porque tiene {$chain->brands()->count()} marca(s) asignadas."
                );
            }
        });
    }

    /**
     * Brands belonging to this chain.
     */
    public function brands(): HasMany
    {
        return $this->hasMany(Brand::class);
    }

    /**
     * Gift cards scoped to this chain.
     */
    public function giftCards(): HasMany
    {
        return $this->hasMany(GiftCard::class);
    }
}
