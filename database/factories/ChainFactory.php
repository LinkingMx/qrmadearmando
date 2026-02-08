<?php

namespace Database\Factories;

use App\Models\Chain;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Chain>
 */
class ChainFactory extends Factory
{
    protected $model = Chain::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->company().' Chain',
        ];
    }
}
