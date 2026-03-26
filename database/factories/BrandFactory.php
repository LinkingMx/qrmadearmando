<?php

namespace Database\Factories;

use App\Models\Brand;
use App\Models\Chain;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Brand>
 */
class BrandFactory extends Factory
{
    protected $model = Brand::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'chain_id' => Chain::factory(),
            'name' => fake()->unique()->company().' Brand',
        ];
    }

    /**
     * Create brand for a specific chain.
     */
    public function forChain(Chain $chain): static
    {
        return $this->state(fn (array $attributes) => [
            'chain_id' => $chain->id,
        ]);
    }
}
