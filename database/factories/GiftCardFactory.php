<?php

namespace Database\Factories;

use App\Enums\GiftCardScope;
use App\Models\Brand;
use App\Models\Chain;
use App\Models\GiftCardCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\GiftCard>
 */
class GiftCardFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $category = GiftCardCategory::firstOrCreate(
            ['prefix' => 'EMCAD'],
            [
                'name' => 'Empleados',
                'nature' => 'payment_method',
            ]
        );

        $chain = Chain::first() ?? Chain::factory()->create();

        return [
            'gift_card_category_id' => $category->id,
            'user_id' => \App\Models\User::factory(),
            'status' => true,
            'expiry_date' => fake()->dateTimeBetween('now', '+1 year'),
            'balance' => fake()->randomFloat(2, 0, 1000),
            'scope' => GiftCardScope::CHAIN,
            'chain_id' => $chain->id,
            'brand_id' => null,
        ];
    }

    /**
     * Create gift card for a specific category.
     */
    public function forCategory(GiftCardCategory $category): static
    {
        return $this->state(fn (array $attributes) => [
            'gift_card_category_id' => $category->id,
        ]);
    }

    /**
     * Set scope to chain.
     */
    public function chainScope(?Chain $chain = null): static
    {
        $chain = $chain ?? Chain::first() ?? Chain::factory()->create();

        return $this->state(fn (array $attributes) => [
            'scope' => GiftCardScope::CHAIN,
            'chain_id' => $chain->id,
            'brand_id' => null,
        ]);
    }

    /**
     * Set scope to brand.
     */
    public function brandScope(?Brand $brand = null): static
    {
        $brand = $brand ?? Brand::first() ?? Brand::factory()->create();

        return $this->state(fn (array $attributes) => [
            'scope' => GiftCardScope::BRAND,
            'chain_id' => null,
            'brand_id' => $brand->id,
        ]);
    }

    /**
     * Set scope to branch (requires ->attach() on branches after creation).
     */
    public function branchScope(): static
    {
        return $this->state(fn (array $attributes) => [
            'scope' => GiftCardScope::BRANCH,
            'chain_id' => null,
            'brand_id' => null,
        ]);
    }
}
