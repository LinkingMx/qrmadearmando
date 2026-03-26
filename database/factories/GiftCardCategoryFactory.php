<?php

namespace Database\Factories;

use App\Models\GiftCardCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GiftCardCategory>
 */
class GiftCardCategoryFactory extends Factory
{
    protected $model = GiftCardCategory::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'prefix' => strtoupper(fake()->unique()->lexify('???')),
            'nature' => fake()->randomElement(['payment_method', 'discount']),
        ];
    }

    /**
     * Category with payment_method nature.
     */
    public function paymentMethod(): static
    {
        return $this->state(fn (array $attributes) => [
            'nature' => 'payment_method',
        ]);
    }

    /**
     * Category with discount nature.
     */
    public function discount(): static
    {
        return $this->state(fn (array $attributes) => [
            'nature' => 'discount',
        ]);
    }

    /**
     * Default EMCAD category for employees.
     */
    public function emcad(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Empleados',
            'prefix' => 'EMCAD',
            'nature' => 'payment_method',
        ]);
    }
}
