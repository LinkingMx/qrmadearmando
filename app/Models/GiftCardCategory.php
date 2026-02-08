<?php

namespace App\Models;

use App\Enums\GiftCardNature;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GiftCardCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'prefix',
        'nature',
    ];

    protected function casts(): array
    {
        return [
            'nature' => GiftCardNature::class,
        ];
    }

    protected static function booted(): void
    {
        // Prevent deletion if gift cards exist in this category
        static::deleting(function ($category) {
            if ($category->giftCards()->exists()) {
                throw new \Exception(
                    "No se puede eliminar la categoría '{$category->name}' porque tiene {$category->giftCards()->count()} QR code(s) asignados."
                );
            }
        });
    }

    /**
     * Gift cards belonging to this category.
     */
    public function giftCards(): HasMany
    {
        return $this->hasMany(GiftCard::class);
    }

    /**
     * Generate the next sequential legacy_id for this category.
     * Format: {prefix}{000001} with independent counter per category.
     */
    public function generateNextLegacyId(): string
    {
        $prefixLength = strlen($this->prefix);

        // Find the last legacy_id for this category (including soft-deleted cards)
        $lastLegacyId = GiftCard::withTrashed()
            ->where('gift_card_category_id', $this->id)
            ->whereNotNull('legacy_id')
            ->where('legacy_id', 'LIKE', $this->prefix . '%')
            ->orderByRaw('CAST(SUBSTRING(legacy_id, ' . ($prefixLength + 1) . ') AS UNSIGNED) DESC')
            ->value('legacy_id');

        // Extract numeric part and increment
        if ($lastLegacyId) {
            $lastNumber = (int) substr($lastLegacyId, $prefixLength);
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }

        // Generate with 6-digit zero-padding
        $newLegacyId = $this->prefix . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);

        // Collision detection safety loop
        $attempts = 0;
        while (GiftCard::withTrashed()->where('legacy_id', $newLegacyId)->exists() && $attempts < 100) {
            $nextNumber++;
            $newLegacyId = $this->prefix . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
            $attempts++;
        }

        return $newLegacyId;
    }
}
