<?php

declare(strict_types=1);

use App\Models\Ingredient;

// Dati non validi: partiamo da un ingrediente valido e cambiamo un solo campo per volta.
// Ogni caso dice quale campo deve ricevere l'errore 422.
dataset('invalid ingredient data', [
    'empty name' => [['name' => ''], 'name'],
    'name longer than 255 characters' => [['name' => str_repeat('a', 256)], 'name'],
    'unit not in the Unit enum' => [['unit' => 'lb'], 'unit'],
    'negative price' => [['price_cents' => -1], 'price_cents'],
    'price with decimals' => [['price_cents' => 12.5], 'price_cents'],
    'price not a number' => [['price_cents' => 'abc'], 'price_cents'],
    // Il massimo di price_cents è 4294967295: un intero a 32 bit senza segno (D-011).
    'price above the unsigned 32-bit maximum' => [['price_cents' => 4294967296], 'price_cents'],
]);

// Creazione: con dati validi la risposta è 201, con i campi in data, e la riga è nel database.
it('creates an ingredient with valid data', function () {
    $response = $this->postJson('/api/v1/ingredients', [
        'name' => 'Farina 00',
        'unit' => 'kg',
        'price_cents' => 120,
    ]);

    $response->assertCreated();
    $response->assertJsonPath('data.name', 'Farina 00');
    $response->assertJsonPath('data.unit', 'kg');
    $response->assertJsonPath('data.price_cents', 120);
    $this->assertDatabaseHas('ingredients', [
        'name' => 'Farina 00',
        'unit' => 'kg',
        'price_cents' => 120,
    ]);
});

// Aggiornamento: con tutti i campi nuovi la risposta è 200, e il database ha i valori nuovi.
it('updates every field of an existing ingredient', function () {
    $ingredient = Ingredient::factory()->create();

    $response = $this->putJson('/api/v1/ingredients/'.$ingredient->id, [
        'name' => 'Burro',
        'unit' => 'g',
        'price_cents' => 450,
    ]);

    $response->assertOk();
    $this->assertDatabaseHas('ingredients', [
        'id' => $ingredient->id,
        'name' => 'Burro',
        'unit' => 'g',
        'price_cents' => 450,
    ]);
});

// Cancellazione: la risposta è 204 e la riga non è più nel database.
it('deletes an existing ingredient', function () {
    $ingredient = Ingredient::factory()->create();

    $response = $this->deleteJson('/api/v1/ingredients/'.$ingredient->id);

    $response->assertNoContent();
    $this->assertDatabaseMissing('ingredients', ['id' => $ingredient->id]);
});

// Creazione con dati non validi: la risposta è 422 e l'errore è sul campo giusto.
it('rejects invalid ingredient data on create', function (array $override, string $field) {
    $payload = array_merge([
        'name' => 'Farina 00',
        'unit' => 'kg',
        'price_cents' => 120,
    ], $override);

    $response = $this->postJson('/api/v1/ingredients', $payload);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors([$field]);
})->with('invalid ingredient data');

// Aggiornamento con dati non validi: la risposta è 422 e l'errore è sul campo giusto.
it('rejects invalid ingredient data on update', function (array $override, string $field) {
    $ingredient = Ingredient::factory()->create();

    $payload = array_merge([
        'name' => 'Farina 00',
        'unit' => 'kg',
        'price_cents' => 120,
    ], $override);

    $response = $this->putJson('/api/v1/ingredients/'.$ingredient->id, $payload);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors([$field]);
})->with('invalid ingredient data');

// Id inesistente nell'aggiornamento: la risposta è 404.
it('returns 404 when updating an ingredient that does not exist', function () {
    $response = $this->putJson('/api/v1/ingredients/999999', [
        'name' => 'Burro',
        'unit' => 'g',
        'price_cents' => 450,
    ]);

    $response->assertNotFound();
});

// Id inesistente nella cancellazione: la risposta è 404.
it('returns 404 when deleting an ingredient that does not exist', function () {
    $response = $this->deleteJson('/api/v1/ingredients/999999');

    $response->assertNotFound();
});
