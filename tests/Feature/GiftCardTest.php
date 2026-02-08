<?php

use App\Models\GiftCard;
use App\Models\GiftCardCategory;
use App\Models\User;
use App\Models\Branch;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');

    // Get or create default EMCAD category for all tests
    $this->category = GiftCardCategory::firstOrCreate(
        ['prefix' => 'EMCAD'],
        [
            'name' => 'Empleados',
            'nature' => 'payment_method',
        ]
    );
});

test('can create a gift card with basic information', function () {
    $giftCard = GiftCard::create([
        'legacy_id' => 'EMCAD20001',
        'gift_card_category_id' => $this->category->id,
        'status' => true,
        'expiry_date' => now()->addYear(),
    ]);

    expect($giftCard)->toBeInstanceOf(GiftCard::class)
        ->and($giftCard->legacy_id)->toBe('EMCAD20001')
        ->and($giftCard->status)->toBeTrue()
        ->and($giftCard->id)->toBeString() // UUID
        ->and($giftCard->qr_image_path)->toBeString();
});

test('automatically generates QR codes when gift card is created', function () {
    $giftCard = GiftCard::create([
        'legacy_id' => 'EMCAD20002',
        'gift_card_category_id' => $this->category->id,
        'status' => true,
    ]);

    // Verificar que se generó el path de QR
    expect($giftCard->qr_image_path)->not->toBeNull();

    // Verificar que los archivos QR se crearon
    $uuid = $giftCard->id;
    Storage::disk('public')->assertExists("qr-codes/{$uuid}_uuid.svg");
    Storage::disk('public')->assertExists("qr-codes/{$uuid}_legacy.svg");
});

test('can assign user to gift card', function () {
    $branch = Branch::create(['name' => 'Sucursal Test']);
    $user = User::factory()->create(['branch_id' => $branch->id]);

    $giftCard = GiftCard::create([
        'legacy_id' => 'EMCAD20003',
        'gift_card_category_id' => $this->category->id,
        'user_id' => $user->id,
        'status' => true,
    ]);

    expect($giftCard->user)->toBeInstanceOf(User::class)
        ->and($giftCard->user->id)->toBe($user->id)
        ->and($user->fresh()->giftCards->count())->toBe(1);
});

test('regenerates QR codes when legacy_id is updated', function () {
    $giftCard = GiftCard::create([
        'legacy_id' => 'EMCAD20004',
        'gift_card_category_id' => $this->category->id,
        'status' => true,
    ]);

    $originalPath = $giftCard->qr_image_path;
    $uuid = $giftCard->id;

    // Verificar archivos originales
    Storage::disk('public')->assertExists("qr-codes/{$uuid}_uuid.svg");
    Storage::disk('public')->assertExists("qr-codes/{$uuid}_legacy.svg");

    // Actualizar legacy_id
    $giftCard->update(['legacy_id' => 'EMCAD20005']);

    // Verificar que se regeneraron los QR codes
    expect($giftCard->fresh()->qr_image_path)->toBe($originalPath);
    Storage::disk('public')->assertExists("qr-codes/{$uuid}_uuid.svg");
    Storage::disk('public')->assertExists("qr-codes/{$uuid}_legacy.svg");
});

test('does not regenerate QR codes when other fields are updated', function () {
    $giftCard = GiftCard::create([
        'legacy_id' => 'EMCAD20006',
        'gift_card_category_id' => $this->category->id,
        'status' => true,
    ]);

    $originalPath = $giftCard->qr_image_path;

    // Actualizar status (no debería regenerar QR)
    $giftCard->update(['status' => false]);

    expect($giftCard->fresh()->qr_image_path)->toBe($originalPath);
});

test('getQrCodeUrls returns correct URLs', function () {
    $giftCard = GiftCard::create([
        'legacy_id' => 'EMCAD20007',
        'gift_card_category_id' => $this->category->id,
        'status' => true,
    ]);

    $urls = $giftCard->getQrCodeUrls();
    $uuid = $giftCard->id;

    expect($urls)->toBeArray()
        ->and($urls['uuid'])->toContain("qr-codes/{$uuid}_uuid.svg")
        ->and($urls['legacy'])->toContain("qr-codes/{$uuid}_legacy.svg");
});

test('getQrCodeUrls returns nulls when no QR path exists', function () {
    $giftCard = new GiftCard([
        'legacy_id' => 'EMCAD20008',
        'gift_card_category_id' => $this->category->id,
        'status' => true,
    ]);

    // Sin guardar para que no se generen QR codes automáticamente
    $urls = $giftCard->getQrCodeUrls();

    expect($urls)->toBeArray()
        ->and($urls['uuid'])->toBeNull()
        ->and($urls['legacy'])->toBeNull();
});

test('legacy_id must be unique', function () {
    GiftCard::create([
        'legacy_id' => 'EMCAD20009',
        'gift_card_category_id' => $this->category->id,
        'status' => true,
    ]);

    expect(fn () => GiftCard::create([
        'legacy_id' => 'EMCAD20009', // Duplicado
        'gift_card_category_id' => $this->category->id,
        'status' => true,
    ]))->toThrow(\Illuminate\Database\QueryException::class);
});

test('can soft delete gift card', function () {
    $giftCard = GiftCard::create([
        'legacy_id' => 'EMCAD20010',
        'gift_card_category_id' => $this->category->id,
        'status' => true,
    ]);

    $uuid = $giftCard->id;

    // Verificar que los archivos QR existen
    Storage::disk('public')->assertExists("qr-codes/{$uuid}_uuid.svg");
    Storage::disk('public')->assertExists("qr-codes/{$uuid}_legacy.svg");

    // Soft delete
    $giftCard->delete();

    // Verificar que está soft deleted
    expect(GiftCard::find($giftCard->id))->toBeNull() // No aparece en consultas normales
        ->and(GiftCard::withTrashed()->find($giftCard->id))->not->toBeNull() // Existe en withTrashed
        ->and(GiftCard::withTrashed()->find($giftCard->id)->deleted_at)->not->toBeNull();

    // Los archivos QR NO deben eliminarse en soft delete
    Storage::disk('public')->assertExists("qr-codes/{$uuid}_uuid.svg");
    Storage::disk('public')->assertExists("qr-codes/{$uuid}_legacy.svg");
});

test('can restore soft deleted gift card', function () {
    $giftCard = GiftCard::create([
        'legacy_id' => 'EMCAD20011',
        'gift_card_category_id' => $this->category->id,
        'status' => true,
    ]);

    // Soft delete
    $giftCard->delete();
    expect(GiftCard::find($giftCard->id))->toBeNull();

    // Restore
    $trashedGiftCard = GiftCard::withTrashed()->find($giftCard->id);
    $trashedGiftCard->restore();

    // Verificar que está restaurado
    expect(GiftCard::find($giftCard->id))->not->toBeNull()
        ->and(GiftCard::find($giftCard->id)->deleted_at)->toBeNull();
});

test('force delete removes QR files', function () {
    $giftCard = GiftCard::create([
        'legacy_id' => 'EMCAD20012',
        'gift_card_category_id' => $this->category->id,
        'status' => true,
    ]);

    $uuid = $giftCard->id;

    // Verificar que los archivos QR existen
    Storage::disk('public')->assertExists("qr-codes/{$uuid}_uuid.svg");
    Storage::disk('public')->assertExists("qr-codes/{$uuid}_legacy.svg");

    // Force delete
    $giftCard->forceDelete();

    // Verificar que el registro fue eliminado permanentemente
    expect(GiftCard::withTrashed()->find($giftCard->id))->toBeNull();

    // Los archivos QR deben eliminarse en force delete
    Storage::disk('public')->assertMissing("qr-codes/{$uuid}_uuid.svg");
    Storage::disk('public')->assertMissing("qr-codes/{$uuid}_legacy.svg");
});

test('can handle null user_id', function () {
    $giftCard = GiftCard::create([
        'legacy_id' => 'EMCAD20013',
        'gift_card_category_id' => $this->category->id,
        'user_id' => null,
        'status' => true,
    ]);

    expect($giftCard->user_id)->toBeNull()
        ->and($giftCard->user)->toBeNull();
});

test('can handle null expiry_date', function () {
    $giftCard = GiftCard::create([
        'legacy_id' => 'EMCAD20014',
        'gift_card_category_id' => $this->category->id,
        'expiry_date' => null,
        'status' => true,
    ]);

    expect($giftCard->expiry_date)->toBeNull();
});

test('status field works as boolean', function () {
    $activeGiftCard = GiftCard::create([
        'legacy_id' => 'EMCAD20015',
        'gift_card_category_id' => $this->category->id,
        'status' => true,
    ]);

    $inactiveGiftCard = GiftCard::create([
        'legacy_id' => 'EMCAD20016',
        'gift_card_category_id' => $this->category->id,
        'status' => false,
    ]);

    expect($activeGiftCard->status)->toBeTrue()
        ->and($inactiveGiftCard->status)->toBeFalse();
});

test('QR code files are generated with correct names', function () {
    $giftCard = GiftCard::create([
        'legacy_id' => 'EMCAD20017',
        'gift_card_category_id' => $this->category->id,
        'status' => true,
    ]);

    $uuid = $giftCard->id;

    // Verificar que los archivos QR se crearon con los nombres correctos
    Storage::disk('public')->assertExists("qr-codes/{$uuid}_uuid.svg");
    Storage::disk('public')->assertExists("qr-codes/{$uuid}_legacy.svg");

    // Verificar que los archivos no están vacíos
    $uuidQrContent = Storage::disk('public')->get("qr-codes/{$uuid}_uuid.svg");
    $legacyQrContent = Storage::disk('public')->get("qr-codes/{$uuid}_legacy.svg");

    expect(strlen($uuidQrContent))->toBeGreaterThan(0)
        ->and(strlen($legacyQrContent))->toBeGreaterThan(0);
});

test('relationships work correctly', function () {
    $branch = Branch::create(['name' => 'Sucursal Test']);
    $user = User::factory()->create(['branch_id' => $branch->id]);

    $giftCard = GiftCard::create([
        'legacy_id' => 'EMCAD20018',
        'gift_card_category_id' => $this->category->id,
        'user_id' => $user->id,
        'status' => true,
    ]);

    // Verificar relación User -> GiftCards
    expect($user->giftCards->count())->toBe(1)
        ->and($user->giftCards->first()->id)->toBe($giftCard->id);

    // Verificar relación GiftCard -> User
    expect($giftCard->user->id)->toBe($user->id);
});

test('auto-generates legacy_id when not provided', function () {
    $giftCard = GiftCard::create([
        'gift_card_category_id' => $this->category->id,
        'status' => true,
    ]);

    expect($giftCard->legacy_id)->not->toBeNull()
        ->and($giftCard->legacy_id)->toStartWith('EMCAD')
        ->and(strlen($giftCard->legacy_id))->toBe(11); // EMCAD + 6 dígitos
});

test('auto-generated legacy_id has correct format', function () {
    $giftCard = GiftCard::create([
        'gift_card_category_id' => $this->category->id,
        'status' => true,
    ]);

    // Verificar formato EMCAD000001
    expect($giftCard->legacy_id)->toMatch('/^EMCAD\d{6}$/');
});

test('respects manually provided legacy_id', function () {
    $customLegacyId = 'EMCAD99999';

    $giftCard = GiftCard::create([
        'legacy_id' => $customLegacyId,
        'gift_card_category_id' => $this->category->id,
        'status' => true,
    ]);

    expect($giftCard->legacy_id)->toBe($customLegacyId);
});

test('auto-generated legacy_id is sequential', function () {
    // Crear primer gift card con legacy_id conocido
    $first = GiftCard::create([
        'legacy_id' => 'EMCAD000100',
        'gift_card_category_id' => $this->category->id,
        'status' => true,
    ]);

    // Crear segundo gift card sin legacy_id (debe auto-generarse)
    $second = GiftCard::create([
        'gift_card_category_id' => $this->category->id,
        'status' => true,
    ]);

    expect($second->legacy_id)->toBe('EMCAD000101');
});

test('auto-generated legacy_id handles gaps in sequence', function () {
    // Crear gift cards con saltos en la secuencia
    GiftCard::create(['legacy_id' => 'EMCAD000050', 'gift_card_category_id' => $this->category->id, 'status' => true]);
    GiftCard::create(['legacy_id' => 'EMCAD000055', 'gift_card_category_id' => $this->category->id, 'status' => true]);

    // El siguiente auto-generado debe ser 56 (siguiente al más alto)
    $giftCard = GiftCard::create(['gift_card_category_id' => $this->category->id, 'status' => true]);

    expect($giftCard->legacy_id)->toBe('EMCAD000056');
});

test('auto-generated legacy_id works with soft deleted records', function () {
    // Crear gift card base
    $base = GiftCard::create([
        'legacy_id' => 'EMCAD000200',
        'gift_card_category_id' => $this->category->id,
        'status' => true,
    ]);

    // Crear y soft delete otro gift card
    $deleted = GiftCard::create([
        'legacy_id' => 'EMCAD000250',
        'gift_card_category_id' => $this->category->id,
        'status' => true,
    ]);
    $deleted->delete(); // Soft delete

    // El siguiente auto-generado debe ser 251 (considera soft deleted)
    $giftCard = GiftCard::create(['gift_card_category_id' => $this->category->id, 'status' => true]);

    expect($giftCard->legacy_id)->toBe('EMCAD000251');
});

test('auto-generated legacy_id starts from 1 when database is empty', function () {
    // Base de datos vacía (RefreshDatabase) - only category exists
    $giftCard = GiftCard::create(['gift_card_category_id' => $this->category->id, 'status' => true]);

    expect($giftCard->legacy_id)->toBe('EMCAD000001');
});

test('auto-generation generates QR codes correctly', function () {
    $giftCard = GiftCard::create([
        'gift_card_category_id' => $this->category->id,
        'status' => true,
        // Sin legacy_id - debe auto-generarse
    ]);

    $uuid = $giftCard->id;

    // Verificar que se auto-generó el legacy_id
    expect($giftCard->legacy_id)->toMatch('/^EMCAD\d{6}$/');

    // Verificar que se generaron los QR codes
    Storage::disk('public')->assertExists("qr-codes/{$uuid}_uuid.svg");
    Storage::disk('public')->assertExists("qr-codes/{$uuid}_legacy.svg");

    // Verificar que los archivos QR no están vacíos
    $uuidQrContent = Storage::disk('public')->get("qr-codes/{$uuid}_uuid.svg");
    $legacyQrContent = Storage::disk('public')->get("qr-codes/{$uuid}_legacy.svg");

    expect(strlen($uuidQrContent))->toBeGreaterThan(0)
        ->and(strlen($legacyQrContent))->toBeGreaterThan(0)
        ->and($giftCard->qr_image_path)->toContain($uuid);
});

// ===== Category-specific tests =====

test('gift card requires category when creating', function () {
    expect(fn () => GiftCard::create([
        'legacy_id' => 'TEST000001',
        'status' => true,
    ]))->toThrow(\InvalidArgumentException::class, 'gift_card_category_id is required');
});

test('gift card belongs to correct category', function () {
    $giftCard = GiftCard::create([
        'gift_card_category_id' => $this->category->id,
        'status' => true,
    ]);

    $giftCard->load('category');

    expect($giftCard->category)->toBeInstanceOf(GiftCardCategory::class)
        ->and($giftCard->category->id)->toBe($this->category->id)
        ->and($giftCard->category->prefix)->toBe('EMCAD');
});

test('different categories have independent counters', function () {
    $categoryB = GiftCardCategory::create([
        'name' => 'Relaciones Públicas',
        'prefix' => 'RPCAD',
        'nature' => 'discount',
    ]);

    // Create cards in category A (EMCAD)
    $cardA1 = GiftCard::create(['gift_card_category_id' => $this->category->id, 'status' => true]);
    $cardA2 = GiftCard::create(['gift_card_category_id' => $this->category->id, 'status' => true]);

    // Create cards in category B (RPCAD)
    $cardB1 = GiftCard::create(['gift_card_category_id' => $categoryB->id, 'status' => true]);
    $cardB2 = GiftCard::create(['gift_card_category_id' => $categoryB->id, 'status' => true]);

    // Each category should start from 000001
    expect($cardA1->legacy_id)->toBe('EMCAD000001')
        ->and($cardA2->legacy_id)->toBe('EMCAD000002')
        ->and($cardB1->legacy_id)->toBe('RPCAD000001')
        ->and($cardB2->legacy_id)->toBe('RPCAD000002');
});

test('category with short prefix generates correct legacy_ids', function () {
    $shortCategory = GiftCardCategory::create([
        'name' => 'Convenios',
        'prefix' => 'CON',
        'nature' => 'discount',
    ]);

    $card = GiftCard::create(['gift_card_category_id' => $shortCategory->id, 'status' => true]);

    expect($card->legacy_id)->toBe('CON000001')
        ->and(strlen($card->legacy_id))->toBe(9); // CON + 6 dígitos
});
