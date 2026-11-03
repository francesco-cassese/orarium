<?php

declare(strict_types=1);

use App\Enums\Unit;
use App\Models\Ingredient;

it('casts unit to the Unit enum and stores its value', function () {
    $ingredient = Ingredient::factory()->create(['unit' => Unit::Kilogram]);

    $ingredient = $ingredient->fresh();

    expect($ingredient->unit)->toBe(Unit::Kilogram);
    $this->assertDatabaseHas('ingredients', ['id' => $ingredient->id, 'unit' => 'kg']);
});

it('creates ingredients with a name, a non-negative price and a Unit', function () {
    $ingredient = Ingredient::factory()->create()->fresh();

    expect($ingredient->name)->not->toBeEmpty();
    expect($ingredient->price_cents)->toBeGreaterThanOrEqual(0);
    expect($ingredient->unit)->toBeInstanceOf(Unit::class);
});
