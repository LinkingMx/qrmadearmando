<?php

use App\Models\Branch;
use App\Models\Brand;
use App\Models\Chain;
use App\Models\GiftCard;
use App\Models\GiftCardCategory;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');
});

// ===== Chain tests =====

test('can create a chain', function () {
    $chain = Chain::create(['name' => 'Mi Empresa']);

    expect($chain)->toBeInstanceOf(Chain::class)
        ->and($chain->name)->toBe('Mi Empresa')
        ->and($chain->id)->toBeInt();
});

test('chain name must be unique', function () {
    Chain::create(['name' => 'Duplicada']);

    expect(fn () => Chain::create(['name' => 'Duplicada']))
        ->toThrow(QueryException::class);
});

test('chain has many brands', function () {
    $chain = Chain::create(['name' => 'Mi Cadena']);
    Brand::create(['chain_id' => $chain->id, 'name' => 'Marca 1']);
    Brand::create(['chain_id' => $chain->id, 'name' => 'Marca 2']);

    expect($chain->brands)->toHaveCount(2);
});

test('cannot delete chain with brands', function () {
    $chain = Chain::create(['name' => 'Cadena con Marcas']);
    Brand::create(['chain_id' => $chain->id, 'name' => 'Marca']);

    expect(fn () => $chain->delete())
        ->toThrow(Exception::class, 'No se puede eliminar');
});

test('can delete chain without brands', function () {
    $chain = Chain::create(['name' => 'Cadena Vacía']);

    $chain->delete();

    expect(Chain::find($chain->id))->toBeNull();
});

// ===== Brand tests =====

test('can create a brand under a chain', function () {
    $chain = Chain::create(['name' => 'Mi Cadena']);
    $brand = Brand::create(['chain_id' => $chain->id, 'name' => 'Mi Marca']);

    expect($brand)->toBeInstanceOf(Brand::class)
        ->and($brand->name)->toBe('Mi Marca')
        ->and($brand->chain_id)->toBe($chain->id);
});

test('brand belongs to chain', function () {
    $chain = Chain::create(['name' => 'Mi Cadena']);
    $brand = Brand::create(['chain_id' => $chain->id, 'name' => 'Mi Marca']);

    expect($brand->chain)->toBeInstanceOf(Chain::class)
        ->and($brand->chain->id)->toBe($chain->id);
});

test('brand name must be unique within same chain', function () {
    $chain = Chain::create(['name' => 'Mi Cadena']);
    Brand::create(['chain_id' => $chain->id, 'name' => 'Duplicada']);

    expect(fn () => Brand::create(['chain_id' => $chain->id, 'name' => 'Duplicada']))
        ->toThrow(QueryException::class);
});

test('brand name can be repeated across different chains', function () {
    $chain1 = Chain::create(['name' => 'Cadena 1']);
    $chain2 = Chain::create(['name' => 'Cadena 2']);

    $brand1 = Brand::create(['chain_id' => $chain1->id, 'name' => 'Mismo Nombre']);
    $brand2 = Brand::create(['chain_id' => $chain2->id, 'name' => 'Mismo Nombre']);

    expect($brand1->id)->not->toBe($brand2->id)
        ->and($brand1->name)->toBe($brand2->name);
});

test('brand has many branches', function () {
    $chain = Chain::create(['name' => 'Mi Cadena']);
    $brand = Brand::create(['chain_id' => $chain->id, 'name' => 'Mi Marca']);
    Branch::create(['name' => 'Sucursal 1', 'brand_id' => $brand->id]);
    Branch::create(['name' => 'Sucursal 2', 'brand_id' => $brand->id]);

    expect($brand->branches)->toHaveCount(2);
});

test('cannot delete brand with branches', function () {
    $chain = Chain::create(['name' => 'Mi Cadena']);
    $brand = Brand::create(['chain_id' => $chain->id, 'name' => 'Marca con Sucursales']);
    Branch::create(['name' => 'Sucursal', 'brand_id' => $brand->id]);

    expect(fn () => $brand->delete())
        ->toThrow(Exception::class, 'No se puede eliminar');
});

test('cannot delete brand with gift cards', function () {
    $chain = Chain::create(['name' => 'Mi Cadena']);
    $brand = Brand::create(['chain_id' => $chain->id, 'name' => 'Marca con QRs']);

    $category = GiftCardCategory::firstOrCreate(
        ['prefix' => 'EMCAD'],
        ['name' => 'Empleados', 'nature' => 'payment_method']
    );

    GiftCard::create([
        'gift_card_category_id' => $category->id,
        'status' => true,
        'scope' => 'brand',
        'brand_id' => $brand->id,
    ]);

    expect(fn () => $brand->delete())
        ->toThrow(Exception::class, 'No se puede eliminar');
});

test('can delete brand without branches or gift cards', function () {
    $chain = Chain::create(['name' => 'Mi Cadena']);
    $brand = Brand::create(['chain_id' => $chain->id, 'name' => 'Marca Vacía']);

    $brand->delete();

    expect(Brand::find($brand->id))->toBeNull();
});

// ===== Branch with Brand tests =====

test('branch belongs to brand', function () {
    $chain = Chain::create(['name' => 'Mi Cadena']);
    $brand = Brand::create(['chain_id' => $chain->id, 'name' => 'Mi Marca']);
    $branch = Branch::create(['name' => 'Mi Sucursal', 'brand_id' => $brand->id]);

    expect($branch->brand)->toBeInstanceOf(Brand::class)
        ->and($branch->brand->id)->toBe($brand->id);
});

test('branch can access chain through brand', function () {
    $chain = Chain::create(['name' => 'Mi Cadena']);
    $brand = Brand::create(['chain_id' => $chain->id, 'name' => 'Mi Marca']);
    $branch = Branch::create(['name' => 'Mi Sucursal', 'brand_id' => $brand->id]);

    $branch->load('brand.chain');

    expect($branch->brand->chain)->toBeInstanceOf(Chain::class)
        ->and($branch->brand->chain->id)->toBe($chain->id);
});

// ===== Hierarchy tests =====

test('full hierarchy chain to brand to branch works', function () {
    $chain = Chain::create(['name' => 'Grupo Empresarial Test']);

    $mochomos = Brand::create(['chain_id' => $chain->id, 'name' => 'Mochomos']);
    $donCarlos = Brand::create(['chain_id' => $chain->id, 'name' => 'Don Carlos']);

    $mochMty = Branch::create(['name' => 'Mochomos Monterrey', 'brand_id' => $mochomos->id]);
    $mochCdmx = Branch::create(['name' => 'Mochomos CDMX', 'brand_id' => $mochomos->id]);
    $dcCentro = Branch::create(['name' => 'Don Carlos Centro', 'brand_id' => $donCarlos->id]);

    // Verify chain has 2 brands
    expect($chain->brands)->toHaveCount(2);

    // Verify Mochomos has 2 branches
    expect($mochomos->branches)->toHaveCount(2);

    // Verify Don Carlos has 1 branch
    expect($donCarlos->branches)->toHaveCount(1);

    // Verify branch -> brand -> chain navigation
    $mochMty->load('brand.chain');
    expect($mochMty->brand->chain->name)->toBe('Grupo Empresarial Test');
});
