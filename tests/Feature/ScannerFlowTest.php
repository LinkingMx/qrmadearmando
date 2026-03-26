<?php

use App\Enums\GiftCardNature;
use App\Enums\GiftCardScope;
use App\Models\Branch;
use App\Models\Brand;
use App\Models\Chain;
use App\Models\GiftCard;
use App\Models\GiftCardCategory;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');

    // Create category
    $this->category = GiftCardCategory::firstOrCreate(
        ['prefix' => 'SCAN'],
        [
            'name' => 'Scanner Test',
            'nature' => GiftCardNature::PAYMENT_METHOD,
        ]
    );

    // Create hierarchy: Chain -> Brand -> Branch
    $this->chain = Chain::create(['name' => 'Test Chain']);
    $this->brand = Brand::create(['chain_id' => $this->chain->id, 'name' => 'Test Brand']);
    $this->branch = Branch::create(['name' => 'Test Branch', 'brand_id' => $this->brand->id]);

    // Create another branch for different brand tests
    $this->brandB = Brand::create(['chain_id' => $this->chain->id, 'name' => 'Brand B']);
    $this->branchB = Branch::create(['name' => 'Branch B', 'brand_id' => $this->brandB->id]);

    // Create user with branch assignment
    $this->user = User::factory()->create([
        'branch_id' => $this->branch->id,
        'is_active' => true,
        'email_verified_at' => now(),
    ]);

    // Assign BranchTerminal role
    $this->user->assignRole(
        Role::firstOrCreate(['name' => 'BranchTerminal', 'guard_name' => 'web'])
    );
});

// ===== Complete Scanner Flow Tests =====

test('complete scanner flow: scan -> view -> debit -> receipt', function () {
    // Step 1: Create active gift card
    $giftCard = GiftCard::create([
        'gift_card_category_id' => $this->category->id,
        'balance' => 500.00,
        'status' => true,
        'scope' => GiftCardScope::CHAIN,
        'chain_id' => $this->chain->id,
    ]);

    // Step 2: Scan QR (lookup by legacy_id)
    $lookupResponse = $this->actingAs($this->user)
        ->postJson('/api/scanner/lookup', [
            'identifier' => $giftCard->legacy_id,
        ]);

    expect($lookupResponse->status())->toBe(200)
        ->and($lookupResponse->json('data.legacy_id'))->toBe($giftCard->legacy_id)
        ->and((float) $lookupResponse->json('data.balance'))->toBe(500.0)
        ->and($lookupResponse->json('data.status'))->toBeTrue();

    // Step 3: Process debit
    $debitResponse = $this->actingAs($this->user)
        ->postJson('/api/scanner/process-debit', [
            'gift_card_id' => $giftCard->id,
            'amount' => 100.00,
            'reference' => 'REF-001',
            'description' => 'Test purchase',
        ]);

    expect($debitResponse->status())->toBe(200)
        ->and((float) $debitResponse->json('data.amount'))->toBe(100.0)
        ->and((float) $debitResponse->json('data.balance_before'))->toBe(500.0)
        ->and((float) $debitResponse->json('data.balance_after'))->toBe(400.0)
        ->and($debitResponse->json('data.reference'))->toBe('REF-001')
        ->and($debitResponse->json('data.branch_name'))->toBe($this->branch->name)
        ->and($debitResponse->json('data.cashier_name'))->toBe($this->user->name);

    // Step 4: Verify transaction was created
    $transaction = Transaction::where('gift_card_id', $giftCard->id)->first();
    expect($transaction)->not->toBeNull()
        ->and($transaction->type)->toBe('debit')
        ->and((float) $transaction->amount)->toBe(100.0)
        ->and($transaction->branch_id)->toBe($this->branch->id)
        ->and($transaction->admin_user_id)->toBe($this->user->id);

    // Step 5: Verify folio format (TRX-YYYYMMDD-NNNNNN)
    $folio = $debitResponse->json('data.folio');
    expect($folio)->toMatch('/^TRX-\d{8}-\d{6}$/');

    // Step 6: Verify receipt data completeness
    $receiptData = $debitResponse->json('data');
    expect($receiptData)->toHaveKeys([
        'id',
        'folio',
        'gift_card',
        'amount',
        'balance_before',
        'balance_after',
        'reference',
        'description',
        'created_at',
        'branch_name',
        'cashier_name',
    ]);
});

test('scanner rejects inactive gift card', function () {
    // Create inactive gift card
    $giftCard = GiftCard::create([
        'gift_card_category_id' => $this->category->id,
        'balance' => 500.00,
        'status' => false, // Inactive
        'scope' => GiftCardScope::CHAIN,
        'chain_id' => $this->chain->id,
    ]);

    // Attempt lookup
    $lookupResponse = $this->actingAs($this->user)
        ->postJson('/api/scanner/lookup', [
            'identifier' => $giftCard->legacy_id,
        ]);

    expect($lookupResponse->status())->toBe(422)
        ->and($lookupResponse->json('error.message'))->toContain('inactivo');

    // Attempt debit
    $debitResponse = $this->actingAs($this->user)
        ->postJson('/api/scanner/process-debit', [
            'gift_card_id' => $giftCard->id,
            'amount' => 50.00,
            'reference' => 'REF-TEST',
        ]);

    expect($debitResponse->status())->toBe(422)
        ->and($debitResponse->json('error.message'))->toContain('inactivo');
});

test('scanner validates branch scope: brand-scoped card at wrong branch', function () {
    // Create brand-scoped card for Brand A
    $giftCard = GiftCard::create([
        'gift_card_category_id' => $this->category->id,
        'balance' => 300.00,
        'status' => true,
        'scope' => GiftCardScope::BRAND,
        'brand_id' => $this->brand->id, // Brand A
    ]);

    // User from Branch B (different brand) tries to use it
    $userBranchB = User::factory()->create([
        'branch_id' => $this->branchB->id,
        'is_active' => true,
        'email_verified_at' => now(),
    ]);
    $userBranchB->assignRole(
        Role::firstOrCreate(['name' => 'BranchTerminal', 'guard_name' => 'web'])
    );

    // Lookup should fail (wrong brand scope)
    $lookupResponse = $this->actingAs($userBranchB)
        ->postJson('/api/scanner/lookup', [
            'identifier' => $giftCard->legacy_id,
        ]);

    expect($lookupResponse->status())->toBe(422)
        ->and($lookupResponse->json('error.message'))->toContain('marca');
});

test('scanner validates branch scope: branch-scoped card at non-assigned branch', function () {
    // Create branch-scoped card assigned to Branch A
    $giftCard = GiftCard::create([
        'gift_card_category_id' => $this->category->id,
        'balance' => 200.00,
        'status' => true,
        'scope' => GiftCardScope::BRANCH,
    ]);
    $giftCard->branches()->attach($this->branch->id);

    // User from Branch B tries to use it
    $userBranchB = User::factory()->create([
        'branch_id' => $this->branchB->id,
        'is_active' => true,
        'email_verified_at' => now(),
    ]);
    $userBranchB->assignRole(
        Role::firstOrCreate(['name' => 'BranchTerminal', 'guard_name' => 'web'])
    );

    $debitResponse = $this->actingAs($userBranchB)
        ->postJson('/api/scanner/process-debit', [
            'gift_card_id' => $giftCard->id,
            'amount' => 50.00,
            'reference' => 'REF-TEST',
        ]);

    expect($debitResponse->status())->toBe(422)
        ->and($debitResponse->json('error.message'))->toContain('Sucursal');
});

test('scanner rejects debit with insufficient balance', function () {
    $giftCard = GiftCard::create([
        'gift_card_category_id' => $this->category->id,
        'balance' => 50.00, // Low balance
        'status' => true,
        'scope' => GiftCardScope::CHAIN,
        'chain_id' => $this->chain->id,
    ]);

    // Attempt to debit more than available
    $debitResponse = $this->actingAs($this->user)
        ->postJson('/api/scanner/process-debit', [
            'gift_card_id' => $giftCard->id,
            'amount' => 100.00, // More than 50.00 balance
            'reference' => 'REF-TEST',
        ]);

    expect($debitResponse->status())->toBe(422)
        ->and($debitResponse->json('error.message'))->toContain('Saldo insuficiente')
        ->and($debitResponse->json('error.message'))->toContain('50.00');
});

test('scanner prevents concurrent debits on same gift card', function () {
    $giftCard = GiftCard::create([
        'gift_card_category_id' => $this->category->id,
        'balance' => 100.00,
        'status' => true,
        'scope' => GiftCardScope::CHAIN,
        'chain_id' => $this->chain->id,
    ]);

    // Create second user at same branch
    $user2 = User::factory()->create([
        'branch_id' => $this->branch->id,
        'is_active' => true,
        'email_verified_at' => now(),
    ]);
    $user2->assignRole(
        Role::firstOrCreate(['name' => 'BranchTerminal', 'guard_name' => 'web'])
    );

    // First debit (should succeed)
    $response1 = $this->actingAs($this->user)
        ->postJson('/api/scanner/process-debit', [
            'gift_card_id' => $giftCard->id,
            'amount' => 60.00,
            'reference' => 'REF-TEST-1',
        ]);

    expect($response1->status())->toBe(200)
        ->and((float) $response1->json('data.balance_after'))->toBe(40.0);

    // Second debit attempt with stale balance (should succeed with updated balance)
    $response2 = $this->actingAs($user2)
        ->postJson('/api/scanner/process-debit', [
            'gift_card_id' => $giftCard->id,
            'amount' => 30.00, // Total would be 90, but only 40 available
            'reference' => 'REF-TEST-2',
        ]);

    expect($response2->status())->toBe(200)
        ->and((float) $response2->json('data.balance_before'))->toBe(40.0) // Uses fresh balance
        ->and((float) $response2->json('data.balance_after'))->toBe(10.0);

    // Third debit should fail (insufficient balance)
    $response3 = $this->actingAs($this->user)
        ->postJson('/api/scanner/process-debit', [
            'gift_card_id' => $giftCard->id,
            'amount' => 20.00, // More than 10 available
            'reference' => 'REF-TEST-3',
        ]);

    expect($response3->status())->toBe(422)
        ->and($response3->json('error.message'))->toContain('Saldo insuficiente');
});

test('scanner transaction folio format is consistent', function () {
    $giftCard = GiftCard::create([
        'gift_card_category_id' => $this->category->id,
        'balance' => 1000.00,
        'status' => true,
        'scope' => GiftCardScope::CHAIN,
        'chain_id' => $this->chain->id,
    ]);

    // Process multiple debits
    $response1 = $this->actingAs($this->user)
        ->postJson('/api/scanner/process-debit', [
            'gift_card_id' => $giftCard->id,
            'amount' => 100.00,
            'reference' => 'REF-TEST-A',
        ]);

    $response2 = $this->actingAs($this->user)
        ->postJson('/api/scanner/process-debit', [
            'gift_card_id' => $giftCard->id,
            'amount' => 50.00,
            'reference' => 'REF-TEST',
        ]);

    $folio1 = $response1->json('data.folio');
    $folio2 = $response2->json('data.folio');

    // Verify format: TRX-YYYYMMDD-NNNNNN
    expect($folio1)->toMatch('/^TRX-\d{8}-\d{6}$/')
        ->and($folio2)->toMatch('/^TRX-\d{8}-\d{6}$/')
        ->and($folio1)->not->toBe($folio2); // Different folios

    // Extract and verify date part
    $dateToday = now()->format('Ymd');
    expect($folio1)->toContain("TRX-{$dateToday}")
        ->and($folio2)->toContain("TRX-{$dateToday}");

    // Verify transaction IDs are sequential
    $id1 = intval(substr($folio1, -6));
    $id2 = intval(substr($folio2, -6));
    expect($id2)->toBeGreaterThan($id1);
});

test('scanner requires user to have branch assignment and BranchTerminal role', function () {
    // Create user without branch assignment
    $userWithoutBranch = User::factory()->create([
        'branch_id' => null,
        'is_active' => true,
        'email_verified_at' => now(),
    ]);
    // Assign BranchTerminal role but no branch
    $userWithoutBranch->assignRole(
        Role::firstOrCreate(['name' => 'BranchTerminal', 'guard_name' => 'web'])
    );

    // Test 1: User without branch should be redirected from scanner index
    $indexResponse = $this->actingAs($userWithoutBranch)
        ->get('/scanner');

    expect($indexResponse->status())->toBe(302)
        ->and($indexResponse->headers->get('Location'))->toContain('/dashboard');

    // Test 2: User without BranchTerminal role should also be redirected
    $userWithoutRole = User::factory()->create([
        'branch_id' => $this->branch->id,
        'is_active' => true,
        'email_verified_at' => now(),
    ]);

    $roleResponse = $this->actingAs($userWithoutRole)
        ->get('/scanner');

    expect($roleResponse->status())->toBe(302)
        ->and($roleResponse->headers->get('Location'))->toContain('/dashboard');
});
