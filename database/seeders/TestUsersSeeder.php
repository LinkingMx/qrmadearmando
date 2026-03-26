<?php

namespace Database\Seeders;

use App\Enums\GiftCardNature;
use App\Enums\GiftCardScope;
use App\Models\Branch;
use App\Models\Brand;
use App\Models\Chain;
use App\Models\GiftCard;
use App\Models\GiftCardCategory;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class TestUsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Creating test users for scanner testing...');

        // Create chain and brand first (required for branches)
        $chain = Chain::firstOrCreate(['name' => 'Cadenas Don Carlos']);
        $brand = Brand::firstOrCreate(
            ['name' => 'Tigre'],
            ['chain_id' => $chain->id]
        );

        // Create Tigre Masaryk branch
        $branch = Branch::firstOrCreate(
            ['name' => 'Tigre Masaryk'],
            ['brand_id' => $brand->id]
        );

        $this->command->info("✓ Branch created: {$branch->name}");

        // Create BranchTerminal user
        $terminalUser = User::firstOrCreate(
            ['email' => 'tigre.masaryk@grupocosteno.com'],
            [
                'name' => 'Tigre Masaryk Terminal',
                'password' => Hash::make('12345678'),
                'branch_id' => $branch->id,
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        // Assign BranchTerminal role if it exists
        if (class_exists(Role::class)) {
            $role = Role::firstOrCreate(['name' => 'BranchTerminal']);
            if (! $terminalUser->hasRole('BranchTerminal')) {
                $terminalUser->assignRole($role);
            }
        }

        $this->command->info("✓ Terminal user created: {$terminalUser->email}");

        // Create gift card owner
        $cardOwner = User::firstOrCreate(
            ['email' => 'ismael.briones@grupocosteno.com'],
            [
                'name' => 'Ismael Briones',
                'password' => Hash::make('12345678'),
                'branch_id' => $branch->id,
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        $this->command->info("✓ Card owner created: {$cardOwner->email}");

        // Get or create EMCAD category
        $category = GiftCardCategory::firstOrCreate(
            ['prefix' => 'EMCAD'],
            [
                'name' => 'Empleados',
                'nature' => GiftCardNature::PAYMENT_METHOD,
            ]
        );

        $this->command->info("✓ Category found: {$category->name} ({$category->prefix})");

        // Create test gift card
        $giftCard = GiftCard::firstOrCreate(
            [
                'user_id' => $cardOwner->id,
                'gift_card_category_id' => $category->id,
            ],
            [
                'status' => true,
                'balance' => 1000.00,
                'scope' => GiftCardScope::BRANCH,
            ]
        );

        // Attach the gift card to the branch (required for BRANCH scope)
        if (! $giftCard->branches()->where('branch_id', $branch->id)->exists()) {
            $giftCard->branches()->attach($branch->id);
        }

        $this->command->info("✓ Gift card created: {$giftCard->legacy_id}");
        $this->command->newLine();
        $this->command->info('🎉 Test users seeded successfully!');
        $this->command->newLine();
        $this->command->table(
            ['Type', 'Email', 'Password', 'Branch'],
            [
                ['Terminal User', $terminalUser->email, '12345678', $branch->name],
                ['Card Owner', $cardOwner->email, '12345678', $branch->name],
            ]
        );
        $this->command->newLine();
        $this->command->info("Gift Card: {$giftCard->legacy_id} (Balance: \${$giftCard->balance})");
    }
}
