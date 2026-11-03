<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\Unit;
use App\Models\Ingredient;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Ingredient>
 */
class IngredientFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->randomElement(['farina', 'burro', 'zucchero', 'uova', 'latte', 'sale', 'lievito', 'cioccolato']),
            'unit' => fake()->randomElement(Unit::cases()),
            'price_cents' => fake()->numberBetween(50, 5000),
        ];
    }
}
