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
            // Auto-generar legacy_id si no se proporciona
            if (empty($giftCard->legacy_id)) {
                $giftCard->legacy_id = static::generateLegacyId();
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
            $qrService = new QrCodeService();
            $qrService->deleteQrCodes($giftCard->qr_image_path);
        });
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
        $qrService = new QrCodeService();

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
        $qrService = new QrCodeService();
        return $qrService->getQrCodeUrls($this->qr_image_path ?? '');
    }

    /**
     * Genera un nuevo legacy_id único en formato EMCAD + 6 dígitos
     */
    protected static function generateLegacyId(): string
    {
        // Buscar el último legacy_id con formato EMCAD
        $lastLegacyId = static::withTrashed()
            ->whereNotNull('legacy_id')
            ->where('legacy_id', 'LIKE', 'EMCAD%')
            ->orderByRaw('CAST(SUBSTRING(legacy_id, 6) AS UNSIGNED) DESC')
            ->value('legacy_id');

        // Extraer el número y calcular el siguiente
        if ($lastLegacyId) {
            $lastNumber = (int) substr($lastLegacyId, 5);
            $nextNumber = $lastNumber + 1;
        } else {
            // Si no hay ninguno, empezar desde 1
            $nextNumber = 1;
        }

        // Generar el nuevo legacy_id con padding de 6 dígitos
        $newLegacyId = 'EMCAD' . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);

        // Verificar unicidad (por si acaso hay colisiones)
        $attempts = 0;
        while (static::withTrashed()->where('legacy_id', $newLegacyId)->exists() && $attempts < 100) {
            $nextNumber++;
            $newLegacyId = 'EMCAD' . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
            $attempts++;
        }

        return $newLegacyId;
    }
}
