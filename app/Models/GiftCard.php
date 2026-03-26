<?php

namespace App\Models;

use App\Enums\GiftCardScope;
use App\Services\QrCodeService;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class GiftCard extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'legacy_id',
        'gift_card_category_id',
        'user_id',
        'status',
        'expiry_date',
        'qr_image_path',
        'balance',
        'scope',
        'chain_id',
        'brand_id',
    ];

    protected function casts(): array
    {
        return [
            'status' => 'boolean',
            'expiry_date' => 'date',
            'balance' => 'decimal:2',
            'scope' => GiftCardScope::class,
        ];
    }

    protected static function booted()
    {
        static::creating(function ($giftCard) {
            // Category is required for legacy_id generation
            if (empty($giftCard->gift_card_category_id)) {
                throw new \InvalidArgumentException(
                    'gift_card_category_id is required when creating a gift card.'
                );
            }

            // Auto-generate legacy_id based on category prefix if not provided
            if (empty($giftCard->legacy_id)) {
                $category = GiftCardCategory::findOrFail($giftCard->gift_card_category_id);
                $giftCard->legacy_id = $category->generateNextLegacyId();
            }
        });

        static::created(function ($giftCard) {
            $giftCard->generateQrCodes();
        });

        static::updating(function ($giftCard) {
            if ($giftCard->isDirty('legacy_id')) {
                $giftCard->generateQrCodes();
            }
        });

        static::forceDeleted(function ($giftCard) {
            $qrService = new QrCodeService;
            $qrService->deleteQrCodes($giftCard->qr_image_path);
        });
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(GiftCardCategory::class, 'gift_card_category_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Chain this gift card is scoped to (when scope is 'chain').
     */
    public function chain(): BelongsTo
    {
        return $this->belongsTo(Chain::class);
    }

    /**
     * Brand this gift card is scoped to (when scope is 'brand').
     */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    /**
     * Specific branches this gift card can be used at (when scope is 'branch').
     */
    public function branches(): BelongsToMany
    {
        return $this->belongsToMany(Branch::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Check if this gift card can be used at the given branch based on its scope.
     */
    public function canBeUsedAtBranch(Branch $branch): bool
    {
        $branch->loadMissing('brand');

        return match ($this->scope) {
            GiftCardScope::CHAIN => $this->chain_id === $branch->brand->chain_id,
            GiftCardScope::BRAND => $this->brand_id === $branch->brand_id,
            GiftCardScope::BRANCH => $this->branches()->where('branches.id', $branch->id)->exists(),
        };
    }

    public function generateQrCodes(): void
    {
        $qrService = new QrCodeService;

        // Delete previous QR codes if they exist
        if ($this->qr_image_path) {
            $qrService->deleteQrCodes($this->qr_image_path);
        }

        // Generate new QR codes
        $qrImagePath = $qrService->generateQrCodes($this->id, $this->legacy_id);

        // Update field without triggering events
        $this->updateQuietly(['qr_image_path' => $qrImagePath]);
    }

    public function getQrCodeUrls(): array
    {
        $qrService = new QrCodeService;

        return $qrService->getQrCodeUrls($this->qr_image_path ?? '');
    }

    /**
     * Scope to find a gift card by legacy_id or UUID.
     */
    public function scopeFindByIdentifier($query, string $identifier)
    {
        $query->where('legacy_id', $identifier);

        if (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $identifier)) {
            $query->orWhere('id', $identifier);
        }

        return $query;
    }

    /**
     * Scope to find a gift card by legacy_id only.
     */
    public function scopeByLegacyId($query, string $legacyId)
    {
        return $query->where('legacy_id', $legacyId);
    }
}
