<?php

namespace App\Models;

use App\Services\QrCodeService;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
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
    ];

    protected function casts(): array
    {
        return [
            'status' => 'boolean',
            'expiry_date' => 'date',
            'balance' => 'decimal:2',
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

    public function category()
    {
        return $this->belongsTo(GiftCardCategory::class, 'gift_card_category_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function generateQrCodes(): void
    {
        $qrService = new QrCodeService;

        // Eliminar QR codes anteriores si existen
        if ($this->qr_image_path) {
            $qrService->deleteQrCodes($this->qr_image_path);
        }

        // Generar nuevos QR codes
        $qrImagePath = $qrService->generateQrCodes($this->id, $this->legacy_id);

        // Actualizar el campo sin disparar eventos
        $this->updateQuietly(['qr_image_path' => $qrImagePath]);
    }

    public function getQrCodeUrls(): array
    {
        $qrService = new QrCodeService;

        return $qrService->getQrCodeUrls($this->qr_image_path ?? '');
    }
}
