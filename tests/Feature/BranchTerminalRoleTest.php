<?php

use App\Models\Branch;
use App\Models\Brand;
use App\Models\Chain;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->chain = Chain::firstOrCreate(['name' => 'Test Chain']);
    $this->brand = Brand::firstOrCreate(
        ['chain_id' => $this->chain->id, 'name' => 'Test Brand'],
    );
    $this->branch = Branch::create(['name' => 'Test Branch', 'brand_id' => $this->brand->id]);

    Role::firstOrCreate(['name' => 'BranchTerminal', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'Employee', 'guard_name' => 'web']);
});

test('user with BranchTerminal role can access scanner', function () {
    $user = User::factory()->create(['branch_id' => $this->branch->id, 'is_active' => true]);
    $user->assignRole('BranchTerminal');

    $response = $this->actingAs($user)->get('/scanner');

    $response->assertStatus(200);
});

test('user with Employee role cannot access scanner', function () {
    $user = User::factory()->create(['branch_id' => $this->branch->id, 'is_active' => true]);
    $user->assignRole('Employee');

    $response = $this->actingAs($user)->get('/scanner');

    $response->assertRedirect(route('dashboard'));
});

test('user without any role cannot access scanner', function () {
    $user = User::factory()->create(['branch_id' => $this->branch->id, 'is_active' => true]);

    $response = $this->actingAs($user)->get('/scanner');

    $response->assertRedirect(route('dashboard'));
});

test('user without branch cannot access scanner even with BranchTerminal role', function () {
    $user = User::factory()->create(['branch_id' => null, 'is_active' => true]);
    $user->assignRole('BranchTerminal');

    $response = $this->actingAs($user)->get('/scanner');

    $response->assertRedirect(route('dashboard'));
});

test('unauthenticated user cannot access scanner', function () {
    $response = $this->get('/scanner');

    $response->assertRedirect(route('login'));
});
