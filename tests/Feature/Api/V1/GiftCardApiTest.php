<?php

use App\Models\GiftCard;
use App\Models\GiftCardCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Create a test category
    $this->category = GiftCardCategory::firstOrCreate(
        ['prefix' => 'APITST'],
        [
            'name' => 'API Test',
            'nature' => \App\Enums\GiftCardNature::PAYMENT_METHOD,
        ]
    );

    // Create test gift cards
    $this->activeCard = GiftCard::create([
        'id' => \Illuminate\Support\Str::uuid(),
        'gift_card_category_id' => $this->category->id,
        'legacy_id' => 'APITST000001',
        'balance' => 1000.00,
        'status' => 'active',
    ]);

    $this->inactiveCard = GiftCard::create([
        'id' => \Illuminate\Support\Str::uuid(),
        'gift_card_category_id' => $this->category->id,
        'legacy_id' => 'APITST000002',
        'balance' => 500.00,
        'status' => 'inactive',
    ]);

    // Create test user
    $this->user = User::factory()->create();
});

describe('Gift Card API', function () {
    it('can search gift card by legacy_id without authentication', function () {
        $response = $this->getJson('/api/v1/public/gift-cards/search?legacy_id=APITST000001');

        expect($response->status())->toBe(200)
            ->and($response->json('data.legacy_id'))->toBe('APITST000001')
            ->and($response->json('data.balance'))->toBe(1000.00)
            ->and($response->json('data.status'))->toBe('active');
    });

    it('returns 404 for non-existent gift card', function () {
        $response = $this->getJson('/api/v1/public/gift-cards/search?legacy_id=NOTFOUND');

        expect($response->status())->toBe(404);
    });

    it('returns 403 for inactive gift card', function () {
        $response = $this->getJson('/api/v1/public/gift-cards/search?legacy_id=APITST000002');

        expect($response->status())->toBe(403)
            ->and($response->json('error'))->toContain('inactivo');
    });

    it('returns 400 when legacy_id is missing', function () {
        $response = $this->getJson('/api/v1/public/gift-cards/search');

        expect($response->status())->toBe(400)
            ->and($response->json('error'))->toContain('requiere');
    });

    it('lists gift cards when authenticated', function () {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/gift-cards');

        expect($response->status())->toBe(200)
            ->and($response->json('data'))->toBeArray()
            ->and($response->json('meta.total'))->toBeGreaterThanOrEqual(0);
    });

    it('requires authentication for gift card list', function () {
        $response = $this->getJson('/api/v1/gift-cards');

        expect($response->status())->toBe(401);
    });
});
