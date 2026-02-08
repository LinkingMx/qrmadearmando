<?php

use App\Enums\GiftCardScope;
use App\Models\Branch;
use App\Models\Brand;
use App\Models\Chain;
use App\Models\GiftCard;
use App\Models\GiftCardCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');

    $this->category = GiftCardCategory::firstOrCreate(
        ['prefix' => 'EMCAD'],
        ['name' => 'Empleados', 'nature' => 'payment_method']
    );

    // Chain A with Brand A and Branch A
    $this->chainA = Chain::create(['name' => 'Chain A']);
    $this->brandA = Brand::create(['chain_id' => $this->chainA->id, 'name' => 'Brand A']);
    $this->branchA = Branch::create(['name' => 'Branch A', 'brand_id' => $this->brandA->id]);

    // Chain A with Brand B and Branch B
    $this->brandB = Brand::create(['chain_id' => $this->chainA->id, 'name' => 'Brand B']);
    $this->branchB = Branch::create(['name' => 'Branch B', 'brand_id' => $this->brandB->id]);

    // Chain B (different chain) with Brand C and Branch C
    $this->chainB = Chain::create(['name' => 'Chain B']);
    $this->brandC = Brand::create(['chain_id' => $this->chainB->id, 'name' => 'Brand C']);
    $this->branchC = Branch::create(['name' => 'Branch C', 'brand_id' => $this->brandC->id]);
});

// ===== Chain scope tests =====

test('chain scope allows usage at branch within same chain', function () {
    $giftCard = GiftCard::create([
        'gift_card_category_id' => $this->category->id,
        'status' => true,
        'scope' => GiftCardScope::CHAIN,
        'chain_id' => $this->chainA->id,
    ]);

    // Branch A belongs to Brand A which belongs to Chain A
    expect($giftCard->canBeUsedAtBranch($this->branchA))->toBeTrue();
});

test('chain scope allows usage at any branch within same chain', function () {
    $giftCard = GiftCard::create([
        'gift_card_category_id' => $this->category->id,
        'status' => true,
        'scope' => GiftCardScope::CHAIN,
        'chain_id' => $this->chainA->id,
    ]);

    // Branch B also belongs to Chain A (via Brand B)
    expect($giftCard->canBeUsedAtBranch($this->branchB))->toBeTrue();
});

test('chain scope rejects usage at branch from different chain', function () {
    $giftCard = GiftCard::create([
        'gift_card_category_id' => $this->category->id,
        'status' => true,
        'scope' => GiftCardScope::CHAIN,
        'chain_id' => $this->chainA->id,
    ]);

    // Branch C belongs to Chain B
    expect($giftCard->canBeUsedAtBranch($this->branchC))->toBeFalse();
});

// ===== Brand scope tests =====

test('brand scope allows usage at branch of same brand', function () {
    $giftCard = GiftCard::create([
        'gift_card_category_id' => $this->category->id,
        'status' => true,
        'scope' => GiftCardScope::BRAND,
        'brand_id' => $this->brandA->id,
    ]);

    // Branch A belongs to Brand A
    expect($giftCard->canBeUsedAtBranch($this->branchA))->toBeTrue();
});

test('brand scope allows usage at all branches of same brand', function () {
    // Add another branch to Brand A
    $branchA2 = Branch::create(['name' => 'Branch A2', 'brand_id' => $this->brandA->id]);

    $giftCard = GiftCard::create([
        'gift_card_category_id' => $this->category->id,
        'status' => true,
        'scope' => GiftCardScope::BRAND,
        'brand_id' => $this->brandA->id,
    ]);

    expect($giftCard->canBeUsedAtBranch($this->branchA))->toBeTrue()
        ->and($giftCard->canBeUsedAtBranch($branchA2))->toBeTrue();
});

test('brand scope rejects usage at branch of different brand', function () {
    $giftCard = GiftCard::create([
        'gift_card_category_id' => $this->category->id,
        'status' => true,
        'scope' => GiftCardScope::BRAND,
        'brand_id' => $this->brandA->id,
    ]);

    // Branch B belongs to Brand B (same chain but different brand)
    expect($giftCard->canBeUsedAtBranch($this->branchB))->toBeFalse();
});

test('brand scope rejects usage at branch of brand from different chain', function () {
    $giftCard = GiftCard::create([
        'gift_card_category_id' => $this->category->id,
        'status' => true,
        'scope' => GiftCardScope::BRAND,
        'brand_id' => $this->brandA->id,
    ]);

    // Branch C belongs to Brand C in Chain B
    expect($giftCard->canBeUsedAtBranch($this->branchC))->toBeFalse();
});

// ===== Branch scope tests =====

test('branch scope allows usage at assigned branch', function () {
    $giftCard = GiftCard::create([
        'gift_card_category_id' => $this->category->id,
        'status' => true,
        'scope' => GiftCardScope::BRANCH,
    ]);

    // Attach branch A to the gift card
    $giftCard->branches()->attach($this->branchA->id);

    expect($giftCard->canBeUsedAtBranch($this->branchA))->toBeTrue();
});

test('branch scope allows usage at multiple assigned branches', function () {
    $giftCard = GiftCard::create([
        'gift_card_category_id' => $this->category->id,
        'status' => true,
        'scope' => GiftCardScope::BRANCH,
    ]);

    // Attach both branch A and B
    $giftCard->branches()->attach([$this->branchA->id, $this->branchB->id]);

    expect($giftCard->canBeUsedAtBranch($this->branchA))->toBeTrue()
        ->and($giftCard->canBeUsedAtBranch($this->branchB))->toBeTrue();
});

test('branch scope rejects usage at non-assigned branch', function () {
    $giftCard = GiftCard::create([
        'gift_card_category_id' => $this->category->id,
        'status' => true,
        'scope' => GiftCardScope::BRANCH,
    ]);

    // Only attach branch A
    $giftCard->branches()->attach($this->branchA->id);

    // Branch B is NOT attached
    expect($giftCard->canBeUsedAtBranch($this->branchB))->toBeFalse();
});

test('branch scope rejects usage when no branches assigned', function () {
    $giftCard = GiftCard::create([
        'gift_card_category_id' => $this->category->id,
        'status' => true,
        'scope' => GiftCardScope::BRANCH,
    ]);

    // No branches attached
    expect($giftCard->canBeUsedAtBranch($this->branchA))->toBeFalse();
});

// ===== GiftCardScope enum tests =====

test('gift card scope enum has correct values', function () {
    expect(GiftCardScope::CHAIN->value)->toBe('chain')
        ->and(GiftCardScope::BRAND->value)->toBe('brand')
        ->and(GiftCardScope::BRANCH->value)->toBe('branch');
});

test('gift card scope enum has labels', function () {
    expect(GiftCardScope::CHAIN->label())->toContain('Cadena')
        ->and(GiftCardScope::BRAND->label())->toContain('Marca')
        ->and(GiftCardScope::BRANCH->label())->toContain('Sucursal');
});

test('gift card scope enum options returns all values', function () {
    $options = GiftCardScope::options();

    expect($options)->toHaveCount(3)
        ->and($options)->toHaveKeys(['chain', 'brand', 'branch']);
});

test('gift card scope is cast correctly', function () {
    $giftCard = GiftCard::create([
        'gift_card_category_id' => $this->category->id,
        'status' => true,
        'scope' => 'chain',
        'chain_id' => $this->chainA->id,
    ]);

    expect($giftCard->scope)->toBe(GiftCardScope::CHAIN)
        ->and($giftCard->scope)->toBeInstanceOf(GiftCardScope::class);
});
