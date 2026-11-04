<?php

declare(strict_types=1);

use App\Models\Ingredient;

// Lista vuota: senza ingredienti, la risposta è 200 con data vuoto.
it('returns an empty list when there are no ingredients', function () {
    $response = $this->getJson('/api/v1/ingredients');

    $response->assertOk();
    $response->assertJsonCount(0, 'data');
});

// Lista con dati: con tre ingredienti, la risposta è 200 con tre elementi in data.
it('returns every ingredient when there are some', function () {
    Ingredient::factory()->count(3)->create();

    $response = $this->getJson('/api/v1/ingredients');

    $response->assertOk();
    $response->assertJsonCount(3, 'data');
});

// Ricerca con risultati: restano solo gli ingredienti il cui nome contiene il testo cercato.
it('returns only the ingredients whose name contains the search text', function () {
    Ingredient::factory()->create(['name' => 'Farina 00']);
    Ingredient::factory()->create(['name' => 'Farina integrale']);
    Ingredient::factory()->create(['name' => 'Burro']);

    $response = $this->getJson('/api/v1/ingredients?search=farina');

    $response->assertOk();
    $response->assertJsonCount(2, 'data');
    $response->assertJsonMissing(['name' => 'Burro']);
});

// Ricerca senza risultati: nessun nome contiene il testo, quindi data è vuoto e la risposta resta 200.
it('returns an empty list when no ingredient matches the search text', function () {
    Ingredient::factory()->create(['name' => 'Burro']);

    $response = $this->getJson('/api/v1/ingredients?search=zucchero');

    $response->assertOk();
    $response->assertJsonCount(0, 'data');
});

// Show trovato: l'ingrediente richiesto risponde 200 con i suoi campi.
it('returns one ingredient with its fields', function () {
    $ingredient = Ingredient::factory()->create();

    $response = $this->getJson('/api/v1/ingredients/'.$ingredient->id);

    $response->assertOk();
    $response->assertJsonStructure(['data' => ['id', 'name', 'unit', 'price_cents']]);
    $response->assertJsonPath('data.id', $ingredient->id);
    $response->assertJsonPath('data.unit', $ingredient->unit->value);
});

// Show non trovato: un id che non esiste risponde 404 in JSON.
it('returns a JSON 404 when the ingredient does not exist', function () {
    $response = $this->getJson('/api/v1/ingredients/999999');

    $response->assertNotFound();
    $response->assertJsonStructure(['message']);
});
