<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Brand extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['chain_id', 'name'];

    protected static function booted(): void
    {
        // Prevent deletion if branches or gift cards exist
        static::deleting(function ($brand) {
            if ($brand->branches()->exists()) {
                throw new \Exception(
                    "No se puede eliminar la marca '{$brand->name}' porque tiene {$brand->branches()->count()} sucursal(es) asignadas."
                );
            }
            if ($brand->giftCards()->exists()) {
                throw new \Exception(
                    "No se puede eliminar la marca '{$brand->name}' porque tiene {$brand->giftCards()->count()} QR code(s) asignados."
                );
            }
        });
    }

    /**
     * Chain this brand belongs to.
     */
    public function chain(): BelongsTo
    {
        return $this->belongsTo(Chain::class);
    }

    /**
     * Branches belonging to this brand.
     */
    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class);
    }

    /**
     * Gift cards scoped to this brand.
     */
    public function giftCards(): HasMany
    {
        return $this->hasMany(GiftCard::class);
    }
}
