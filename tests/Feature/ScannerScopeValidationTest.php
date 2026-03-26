<?php

use App\Enums\GiftCardScope;
use App\Models\Branch;
use App\Models\Brand;
use App\Models\Chain;
use App\Models\GiftCard;
use App\Models\GiftCardCategory;
use App\Models\User;
use App\Services\TransactionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');

    $this->category = GiftCardCategory::firstOrCreate(
        ['prefix' => 'EMCAD'],
        ['name' => 'Empleados', 'nature' => 'payment_method']
    );

    // Hierarchy: Chain A -> Brand A -> Branch A, Brand B -> Branch B
    $this->chainA = Chain::create(['name' => 'Chain A']);
    $this->brandA = Brand::create(['chain_id' => $this->chainA->id, 'name' => 'Brand A']);
    $this->branchA = Branch::create(['name' => 'Branch A', 'brand_id' => $this->brandA->id]);
    $this->brandB = Brand::create(['chain_id' => $this->chainA->id, 'name' => 'Brand B']);
    $this->branchB = Branch::create(['name' => 'Branch B', 'brand_id' => $this->brandB->id]);

    // Different chain
    $this->chainB = Chain::create(['name' => 'Chain B']);
    $this->brandC = Brand::create(['chain_id' => $this->chainB->id, 'name' => 'Brand C']);
    $this->branchC = Branch::create(['name' => 'Branch C', 'brand_id' => $this->brandC->id]);

    $this->transactionService = new TransactionService;
    $this->user = User::factory()->create(['is_active' => true]);
});

// ===== TransactionService scope validation tests =====

test('debit allows chain-scoped QR at branch within same chain', function () {
    $giftCard = GiftCard::create([
        'gift_card_category_id' => $this->category->id,
        'status' => true,
        'balance' => 100,
        'scope' => GiftCardScope::CHAIN,
        'chain_id' => $this->chainA->id,
    ]);

    $transaction = $this->transactionService->debit(
        $giftCard, 50, 'Test', $this->user->id, $this->branchA->id
    );

    expect($transaction->amount)->toBe('50.00')
        ->and($giftCard->fresh()->balance)->toBe('50.00');
});

test('debit rejects chain-scoped QR at branch from different chain', function () {
    $giftCard = GiftCard::create([
        'gift_card_category_id' => $this->category->id,
        'status' => true,
        'balance' => 100,
        'scope' => GiftCardScope::CHAIN,
        'chain_id' => $this->chainA->id,
    ]);

    $this->transactionService->debit(
        $giftCard, 50, 'Test', $this->user->id, $this->branchC->id
    );
})->throws(InvalidArgumentException::class, 'cadena asignada');

test('debit allows brand-scoped QR at branch of same brand', function () {
    $giftCard = GiftCard::create([
        'gift_card_category_id' => $this->category->id,
        'status' => true,
        'balance' => 100,
        'scope' => GiftCardScope::BRAND,
        'brand_id' => $this->brandA->id,
    ]);

    $transaction = $this->transactionService->debit(
        $giftCard, 50, 'Test', $this->user->id, $this->branchA->id
    );

    expect($transaction->amount)->toBe('50.00');
});

test('debit rejects brand-scoped QR at branch of different brand', function () {
    $giftCard = GiftCard::create([
        'gift_card_category_id' => $this->category->id,
        'status' => true,
        'balance' => 100,
        'scope' => GiftCardScope::BRAND,
        'brand_id' => $this->brandA->id,
    ]);

    $this->transactionService->debit(
        $giftCard, 50, 'Test', $this->user->id, $this->branchB->id
    );
})->throws(InvalidArgumentException::class, 'marca asignada');

test('debit allows branch-scoped QR at assigned branch', function () {
    $giftCard = GiftCard::create([
        'gift_card_category_id' => $this->category->id,
        'status' => true,
        'balance' => 100,
        'scope' => GiftCardScope::BRANCH,
    ]);
    $giftCard->branches()->attach($this->branchA->id);

    $transaction = $this->transactionService->debit(
        $giftCard, 50, 'Test', $this->user->id, $this->branchA->id
    );

    expect($transaction->amount)->toBe('50.00');
});

test('debit rejects branch-scoped QR at non-assigned branch', function () {
    $giftCard = GiftCard::create([
        'gift_card_category_id' => $this->category->id,
        'status' => true,
        'balance' => 100,
        'scope' => GiftCardScope::BRANCH,
    ]);
    $giftCard->branches()->attach($this->branchA->id);

    $this->transactionService->debit(
        $giftCard, 50, 'Test', $this->user->id, $this->branchB->id
    );
})->throws(InvalidArgumentException::class, 'sucursales específicas');

// ===== Scanner Controller scope validation tests =====

test('scanner rejects brand-scoped QR used at branch of different brand', function () {
    $terminalUser = User::factory()->create([
        'branch_id' => $this->branchB->id,
        'is_active' => true,
    ]);
    $terminalUser->assignRole(
        Role::firstOrCreate(['name' => 'BranchTerminal', 'guard_name' => 'web'])
    );

    $giftCard = GiftCard::create([
        'gift_card_category_id' => $this->category->id,
        'status' => true,
        'balance' => 100,
        'scope' => GiftCardScope::BRAND,
        'brand_id' => $this->brandA->id,
    ]);

    $response = $this->actingAs($terminalUser)
        ->postJson('/api/scanner/process-debit', [
            'gift_card_id' => $giftCard->id,
            'amount' => 50,
            'reference' => 'REF-TEST',
        ]);

    $response->assertStatus(422)
        ->assertJsonPath('error', fn ($msg) => str_contains($msg, 'Marca'));
});

test('scanner accepts brand-scoped QR used at branch of same brand', function () {
    $terminalUser = User::factory()->create([
        'branch_id' => $this->branchA->id,
        'is_active' => true,
    ]);
    $terminalUser->assignRole(
        Role::firstOrCreate(['name' => 'BranchTerminal', 'guard_name' => 'web'])
    );

    $giftCard = GiftCard::create([
        'gift_card_category_id' => $this->category->id,
        'status' => true,
        'balance' => 100,
        'scope' => GiftCardScope::BRAND,
        'brand_id' => $this->brandA->id,
    ]);

    $response = $this->actingAs($terminalUser)
        ->postJson('/api/scanner/process-debit', [
            'gift_card_id' => $giftCard->id,
            'amount' => 50,
            'reference' => 'REF-TEST',
        ]);

    $response->assertStatus(200)
        ->assertJsonPath('success', true);
});

test('scanner rejects branch-scoped QR used at non-assigned branch', function () {
    $terminalUser = User::factory()->create([
        'branch_id' => $this->branchB->id,
        'is_active' => true,
    ]);
    $terminalUser->assignRole(
        Role::firstOrCreate(['name' => 'BranchTerminal', 'guard_name' => 'web'])
    );

    $giftCard = GiftCard::create([
        'gift_card_category_id' => $this->category->id,
        'status' => true,
        'balance' => 100,
        'scope' => GiftCardScope::BRANCH,
    ]);
    $giftCard->branches()->attach($this->branchA->id);

    $response = $this->actingAs($terminalUser)
        ->postJson('/api/scanner/process-debit', [
            'gift_card_id' => $giftCard->id,
            'amount' => 50,
            'reference' => 'REF-TEST',
        ]);

    $response->assertStatus(422)
        ->assertJsonPath('error', fn ($msg) => str_contains($msg, 'Sucursal'));
});

test('scanner accepts branch-scoped QR used at assigned branch', function () {
    $terminalUser = User::factory()->create([
        'branch_id' => $this->branchA->id,
        'is_active' => true,
    ]);
    $terminalUser->assignRole(
        Role::firstOrCreate(['name' => 'BranchTerminal', 'guard_name' => 'web'])
    );

    $giftCard = GiftCard::create([
        'gift_card_category_id' => $this->category->id,
        'status' => true,
        'balance' => 100,
        'scope' => GiftCardScope::BRANCH,
    ]);
    $giftCard->branches()->attach($this->branchA->id);

    $response = $this->actingAs($terminalUser)
        ->postJson('/api/scanner/process-debit', [
            'gift_card_id' => $giftCard->id,
            'amount' => 50,
            'reference' => 'REF-TEST',
        ]);

    $response->assertStatus(200)
        ->assertJsonPath('success', true);
});

test('scanner accepts chain-scoped QR at any branch within chain', function () {
    $terminalUser = User::factory()->create([
        'branch_id' => $this->branchB->id,
        'is_active' => true,
    ]);
    $terminalUser->assignRole(
        Role::firstOrCreate(['name' => 'BranchTerminal', 'guard_name' => 'web'])
    );

    $giftCard = GiftCard::create([
        'gift_card_category_id' => $this->category->id,
        'status' => true,
        'balance' => 100,
        'scope' => GiftCardScope::CHAIN,
        'chain_id' => $this->chainA->id,
    ]);

    $response = $this->actingAs($terminalUser)
        ->postJson('/api/scanner/process-debit', [
            'gift_card_id' => $giftCard->id,
            'amount' => 50,
            'reference' => 'REF-TEST',
        ]);

    $response->assertStatus(200)
        ->assertJsonPath('success', true);
});
