<?php

use App\Models\GiftCardCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Categories are created via migrations or fixtures
    // Ensure at least one category exists
    GiftCardCategory::firstOrCreate(
        ['prefix' => 'TEST'],
        [
            'name' => 'Test Category',
            'nature' => \App\Enums\GiftCardNature::PAYMENT_METHOD,
        ]
    );
});

describe('Category API', function () {
    it('can list categories without authentication', function () {
        $response = $this->getJson('/api/v1/public/categories');

        expect($response->status())->toBe(200)
            ->and($response->json('data'))->toBeArray()
            ->and(count($response->json('data')))->toBeGreaterThanOrEqual(1);
    });

    it('returns category data in correct format', function () {
        $response = $this->getJson('/api/v1/public/categories');

        $data = $response->json('data');
        expect($data[0])->toHaveKeys(['id', 'prefix', 'name', 'nature', 'cached_at']);
    });

    it('includes cache headers for offline use', function () {
        $response = $this->getJson('/api/v1/public/categories');

        expect($response->headers->get('Cache-Control'))->toContain('public')
            ->and($response->headers->get('Cache-Control'))->toContain('max-age=86400');
    });
});
