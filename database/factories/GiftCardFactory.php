<?php

namespace Database\Factories;

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

        return [
            'gift_card_category_id' => $category->id,
            // legacy_id auto-generates based on category prefix
            'user_id' => \App\Models\User::factory(),
            'status' => true,
            'expiry_date' => fake()->dateTimeBetween('now', '+1 year'),
            'balance' => fake()->randomFloat(2, 0, 1000),
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
}
