<?php

use App\Enums\GiftCardNature;
use App\Enums\GiftCardScope;
use App\Models\Branch;
use App\Models\GiftCard;
use App\Models\GiftCardCategory;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create([
        'email_verified_at' => now(),
        'is_active' => true,
    ]);

    $this->branch = Branch::factory()->create();
    $this->user->branch_id = $this->branch->id;
    $this->user->save();

    $this->category = GiftCardCategory::firstOrCreate(
        ['prefix' => 'EMCAD'],
        [
            'name' => 'Empleados',
            'nature' => GiftCardNature::PAYMENT_METHOD,
        ]
    );

    $this->giftCard = GiftCard::factory()->create([
        'gift_card_category_id' => $this->category->id,
        'status' => true,
        'balance' => 1000.00,
        'scope' => GiftCardScope::CHAIN,
        'chain_id' => $this->branch->brand->chain_id,
    ]);
});

it('returns gift card by legacy_id via search endpoint', function () {
    $response = $this->getJson("/api/v1/public/gift-cards/search?legacy_id={$this->giftCard->legacy_id}");

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                'id',
                'legacy_id',
                'balance',
                'status',
                'category',
            ],
        ])
        ->assertJson([
            'data' => [
                'id' => $this->giftCard->id,
                'legacy_id' => $this->giftCard->legacy_id,
                'status' => true,
            ],
        ]);

    // Verify balance is numeric and matches expected value
    expect((float) $response->json('data.balance'))->toBe(1000.0);
});

it('returns gift card by UUID via search endpoint', function () {
    $response = $this->getJson("/api/v1/public/gift-cards/search?legacy_id={$this->giftCard->id}");

    $response->assertOk()
        ->assertJson([
            'data' => [
                'id' => $this->giftCard->id,
            ],
        ]);
});

it('returns 404 for invalid legacy_id via search endpoint', function () {
    $response = $this->getJson('/api/v1/public/gift-cards/search?legacy_id=INVALID123');

    $response->assertNotFound();
});

it('filters soft-deleted gift cards from search results', function () {
    $this->giftCard->delete(); // Soft delete

    $response = $this->getJson("/api/v1/public/gift-cards/search?legacy_id={$this->giftCard->legacy_id}");

    $response->assertNotFound();
});

it('processes debit transaction successfully via API', function () {
    Sanctum::actingAs($this->user);

    $response = $this->postJson('/api/v1/debit', [
            'legacy_id' => $this->giftCard->legacy_id,
            'amount' => 100.00,
            'description' => 'Test debit transaction',
        ]);

    $response->assertCreated()
        ->assertJsonStructure([
            'data' => [
                'id',
                'gift_card_id',
                'amount',
                'balance_before',
                'balance_after',
                'created_at',
            ],
        ]);

    // Verify amounts are floats
    expect((float) $response->json('data.amount'))->toBe(100.0);
    expect((float) $response->json('data.balance_after'))->toBe(900.0);

    // Verify transaction was created in database
    $this->assertDatabaseHas('transactions', [
        'gift_card_id' => $this->giftCard->id,
        'type' => 'debit',
        'amount' => 100.00,
    ]);
});

it('rejects debit with insufficient balance', function () {
    Sanctum::actingAs($this->user);

    $response = $this->postJson('/api/v1/debit', [
            'legacy_id' => $this->giftCard->legacy_id,
            'amount' => 2000.00, // More than available balance
            'description' => 'Test insufficient balance',
        ]);

    $response->assertStatus(422)
        ->assertJsonPath('error.code', 'INSUFFICIENT_BALANCE');
});

it('requires authentication for debit endpoint', function () {
    $response = $this->postJson('/api/v1/debit', [
        'legacy_id' => $this->giftCard->legacy_id,
        'amount' => 100.00,
        'description' => 'Test without auth',
    ]);

    $response->assertUnauthorized();
});

it('syncs offline transaction with offline_id', function () {
    Sanctum::actingAs($this->user);

    $offlineId = Str::uuid()->toString();

    $response = $this->postJson('/api/v1/sync/transactions', [
        'offline_id' => $offlineId,
        'legacy_id' => $this->giftCard->legacy_id,
        'amount' => 50.00,
        'description' => 'Offline debit transaction',
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.offline_id', $offlineId);

    // Verify transaction has offline_id
    $this->assertDatabaseHas('transactions', [
        'offline_id' => $offlineId,
        'amount' => 50.00,
    ]);
});

it('ensures sync endpoint is idempotent with same offline_id', function () {
    Sanctum::actingAs($this->user);

    $offlineId = Str::uuid()->toString();

    $payload = [
        'offline_id' => $offlineId,
        'legacy_id' => $this->giftCard->legacy_id,
        'amount' => 50.00,
        'description' => 'Offline debit transaction',
    ];

    // First sync
    $response1 = $this->postJson('/api/v1/sync/transactions', $payload);

    // Second sync with same offline_id (duplicate)
    $response2 = $this->postJson('/api/v1/sync/transactions', $payload);

    $response1->assertCreated();
    $response2->assertOk(); // Should return existing transaction

    // Should only create 1 transaction
    $count = Transaction::where('offline_id', $offlineId)->count();
    expect($count)->toBe(1);
});
