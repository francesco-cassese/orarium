<?php

declare(strict_types=1);

use App\Models\Ingredient;
use Database\Seeders\IngredientSeeder;

// Il seeder crea il numero di ingredienti richiesto: con 20, in tabella ci sono 20 righe.
it('inserts the requested number of ingredients', function () {
    app(IngredientSeeder::class)->run(20);

    expect(Ingredient::count())->toBe(20);
});

// Due esecuzioni su una tabella vuota producono gli stessi dati (senza id e date, che cambiano).
it('produces the same data when run twice on an empty table', function () {
    app(IngredientSeeder::class)->run(20);
    $first = Ingredient::orderBy('id')->get(['name', 'unit', 'price_cents'])->toArray();
    expect($first)->toHaveCount(20);

    Ingredient::query()->delete();
    app(IngredientSeeder::class)->run(20);
    $second = Ingredient::orderBy('id')->get(['name', 'unit', 'price_cents'])->toArray();

    expect($second)->toBe($first);
});

// In produzione il seeder si rifiuta di girare: lancia un'eccezione e non inserisce nulla.
it('refuses to run in production and inserts nothing', function () {
    app()->detectEnvironment(fn () => 'production');

    expect(fn () => app(IngredientSeeder::class)->run(20))->toThrow(RuntimeException::class);
    expect(Ingredient::count())->toBe(0);
});

// 25 righe inserite a blocchi da 10: due blocchi da 10 e uno finale da 5. Tutte e 25 devono arrivare in tabella.
// Se l'ultimo blocco più piccolo andasse perso, la tabella avrebbe 20 righe.
it('inserts every row when the last chunk is smaller than the others', function () {
    app(IngredientSeeder::class)->run(25, 10);

    expect(Ingredient::count())->toBe(25);
});
