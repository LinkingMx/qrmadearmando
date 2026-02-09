<?php

use App\Models\Branch;
use App\Models\GiftCard;
use App\Models\GiftCardCategory;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Create branch with brand and chain
    $this->branch = Branch::factory()->create();

    // Create category
    $this->category = GiftCardCategory::firstOrCreate(
        ['prefix' => 'DEBIT'],
        [
            'name' => 'Debit Test',
            'nature' => \App\Enums\GiftCardNature::PAYMENT_METHOD,
        ]
    );

    // Create gift card with chain scope (simplest for testing)
    $this->giftCard = GiftCard::create([
        'id' => \Illuminate\Support\Str::uuid(),
        'gift_card_category_id' => $this->category->id,
        'legacy_id' => 'DEBIT000001',
        'balance' => 1000.00,
        'status' => true,
        'scope' => \App\Enums\GiftCardScope::CHAIN,
        'chain_id' => $this->branch->brand->chain_id,
    ]);

    // Create user with branch
    $this->user = User::factory()->create();
    $this->user->branch_id = $this->branch->id;
    $this->user->save();
});

describe('Debit API', function () {
    it('requires authentication to process debit', function () {
        $response = $this->postJson('/api/v1/debit', [
            'legacy_id' => 'DEBIT000001',
            'amount' => 100.00,
        ]);

        expect($response->status())->toBe(401);
    });

    it('can process debit when authenticated', function () {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v1/debit', [
            'legacy_id' => 'DEBIT000001',
            'amount' => 100.00,
            'description' => 'Test debit',
        ]);

        expect($response->status())->toBe(201)
            ->and((float) $response->json('data.amount'))->toBe(100.0)
            ->and((float) $response->json('data.balance_before'))->toBe(1000.0)
            ->and((float) $response->json('data.balance_after'))->toBe(900.0);

        // Verify gift card balance was updated
        $this->giftCard->refresh();
        expect((float) $this->giftCard->balance)->toBe(900.00);
    });

    it('returns 422 for insufficient balance', function () {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v1/debit', [
            'legacy_id' => 'DEBIT000001',
            'amount' => 2000.00, // More than balance
        ]);

        expect($response->status())->toBe(422)
            ->and($response->json('error'))->toContain('Saldo insuficiente');
    });

    it('returns 404 for non-existent gift card', function () {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v1/debit', [
            'legacy_id' => 'NOTFOUND',
            'amount' => 100.00,
        ]);

        expect($response->status())->toBe(404);
    });

    it('validates required fields', function () {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v1/debit', []);

        expect($response->status())->toBe(422)
            ->and($response->json('errors'))->toHaveKeys(['legacy_id', 'amount']);
    });
});

describe('Sync API', function () {
    it('requires authentication to sync transactions', function () {
        $response = $this->postJson('/api/v1/sync/transactions', [
            'offline_id' => \Illuminate\Support\Str::uuid(),
            'legacy_id' => 'DEBIT000001',
            'amount' => 100.00,
        ]);

        expect($response->status())->toBe(401);
    });

    it('can sync offline transaction', function () {
        Sanctum::actingAs($this->user);
        $offlineId = (string) \Illuminate\Support\Str::uuid();

        $response = $this->postJson('/api/v1/sync/transactions', [
            'offline_id' => $offlineId,
            'legacy_id' => 'DEBIT000001',
            'amount' => 50.00,
            'description' => 'Offline debit',
        ]);

        expect($response->status())->toBe(201)
            ->and($response->json('data.offline_id'))->toBe($offlineId)
            ->and((float) $response->json('data.amount'))->toBe(50.0)
            ->and($response->json('message'))->toContain('exitosamente');

        // Verify transaction was recorded
        $transaction = Transaction::where('offline_id', $offlineId)->first();
        expect($transaction)->not->toBeNull()
            ->and((float) $transaction->amount)->toBe(50.0);
    });

    it('prevents duplicate offline transactions', function () {
        Sanctum::actingAs($this->user);
        $offlineId = (string) \Illuminate\Support\Str::uuid();

        // First sync
        $this->postJson('/api/v1/sync/transactions', [
            'offline_id' => $offlineId,
            'legacy_id' => 'DEBIT000001',
            'amount' => 50.00,
        ]);

        // Try to sync again with same offline_id
        $response = $this->postJson('/api/v1/sync/transactions', [
            'offline_id' => $offlineId,
            'legacy_id' => 'DEBIT000001',
            'amount' => 50.00,
        ]);

        expect($response->status())->toBe(200)
            ->and($response->json('data.offline_id'))->toBe($offlineId)
            ->and((float) $response->json('data.amount'))->toBe(50.0)
            ->and($response->json('message'))->toContain('ya sincronizada');

        // Verify only one transaction was created
        $transactions = Transaction::where('offline_id', $offlineId)->count();
        expect($transactions)->toBe(1);
    });

    it('can get sync status', function () {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/sync/status');

        expect($response->status())->toBe(200)
            ->and($response->json('data'))->toHaveKeys(['pending_transactions', 'total_cards', 'last_sync', 'is_synced']);
    });
});
