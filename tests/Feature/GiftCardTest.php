<?php

use App\Models\GiftCard;
use App\Models\User;
use App\Models\Branch;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');
});

test('can create a gift card with basic information', function () {
    $giftCard = GiftCard::create([
        'legacy_id' => 'EMCAD20001',
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
        'status' => true,
    ]);

    expect(fn () => GiftCard::create([
        'legacy_id' => 'EMCAD20009', // Duplicado
        'status' => true,
    ]))->toThrow(\Illuminate\Database\QueryException::class);
});

test('can soft delete gift card', function () {
    $giftCard = GiftCard::create([
        'legacy_id' => 'EMCAD20010',
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
        'user_id' => null,
        'status' => true,
    ]);

    expect($giftCard->user_id)->toBeNull()
        ->and($giftCard->user)->toBeNull();
});

test('can handle null expiry_date', function () {
    $giftCard = GiftCard::create([
        'legacy_id' => 'EMCAD20014',
        'expiry_date' => null,
        'status' => true,
    ]);

    expect($giftCard->expiry_date)->toBeNull();
});

test('status field works as boolean', function () {
    $activeGiftCard = GiftCard::create([
        'legacy_id' => 'EMCAD20015',
        'status' => true,
    ]);

    $inactiveGiftCard = GiftCard::create([
        'legacy_id' => 'EMCAD20016',
        'status' => false,
    ]);

    expect($activeGiftCard->status)->toBeTrue()
        ->and($inactiveGiftCard->status)->toBeFalse();
});

test('QR code files are generated with correct names', function () {
    $giftCard = GiftCard::create([
        'legacy_id' => 'EMCAD20017',
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
        'user_id' => $user->id,
        'status' => true,
    ]);

    // Verificar relación User -> GiftCards
    expect($user->giftCards->count())->toBe(1)
        ->and($user->giftCards->first()->id)->toBe($giftCard->id);

    // Verificar relación GiftCard -> User
    expect($giftCard->user->id)->toBe($user->id);
});