<?php

namespace Database\Factories;

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
        return [
            'legacy_id' => 'EMCAD' . fake()->unique()->numberBetween(10000, 99999),
            'user_id' => \App\Models\User::factory(),
            'status' => true,
            'expiry_date' => fake()->dateTimeBetween('now', '+1 year'),
            'balance' => fake()->randomFloat(2, 0, 1000),
        ];
    }
}
