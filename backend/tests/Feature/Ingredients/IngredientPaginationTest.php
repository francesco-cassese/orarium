<?php

declare(strict_types=1);

use App\Models\Ingredient;

// Valori non validi per per_page: ognuno deve dare 422 con l'errore sul campo per_page.
dataset('invalid per_page', [
    'zero' => ['0'],
    'negative' => ['-1'],
    'above the maximum of 100' => ['101'],
    'not a number' => ['abc'],
    'with decimals' => ['12.5'],
]);

// Valori ai limiti di per_page: il minimo è 1 e il massimo è 100, entrambi accettati.
dataset('valid per_page', [
    'the minimum' => ['1', 1],
    'the maximum' => ['100', 100],
]);

// Pagina predefinita: con 60 ingredienti, la prima pagina ne contiene 50 e il totale è 60.
it('returns the first page of 50 ingredients by default', function () {
    Ingredient::factory()->count(60)->create();

    $response = $this->getJson('/api/v1/ingredients');

    $response->assertOk();
    $response->assertJsonCount(50, 'data');
    $response->assertJsonPath('meta.current_page', 1);
    $response->assertJsonPath('meta.per_page', 50);
    $response->assertJsonPath('meta.total', 60);
    $response->assertJsonPath('meta.last_page', 2);
});

// Forma della risposta: accanto a data ci sono i link e le informazioni di pagina.
it('returns the pagination links and meta next to data', function () {
    Ingredient::factory()->count(3)->create();

    $response = $this->getJson('/api/v1/ingredients');

    $response->assertOk();
    $response->assertJsonStructure([
        'data' => [['id', 'name', 'unit', 'price_cents']],
        'links' => ['first', 'last', 'prev', 'next'],
        'meta' => ['current_page', 'last_page', 'per_page', 'total'],
    ]);
});

// Seconda pagina: con 60 ingredienti restano gli ultimi 10.
it('returns the remaining ingredients on the second page', function () {
    Ingredient::factory()->count(60)->create();

    $response = $this->getJson('/api/v1/ingredients?page=2');

    $response->assertOk();
    $response->assertJsonCount(10, 'data');
    $response->assertJsonPath('meta.current_page', 2);
});

// Nessuna riga ripetuta o saltata: due pagine da 30 danno tutti i 60 id, in ordine di id.
it('does not repeat or skip ingredients between pages and orders them by id', function () {
    $created = Ingredient::factory()->count(60)->create()->pluck('id')->all();

    $first = $this->getJson('/api/v1/ingredients?per_page=30&page=1')->json('data.*.id');
    $second = $this->getJson('/api/v1/ingredients?per_page=30&page=2')->json('data.*.id');

    expect([...$first, ...$second])->toBe($created);
});

// per_page: con 5 ingredienti e per_page=2 la pagina ne ha 2 e le pagine sono 3.
it('returns the number of ingredients asked with per_page', function () {
    Ingredient::factory()->count(5)->create();

    $response = $this->getJson('/api/v1/ingredients?per_page=2');

    $response->assertOk();
    $response->assertJsonCount(2, 'data');
    $response->assertJsonPath('meta.per_page', 2);
    $response->assertJsonPath('meta.last_page', 3);
});

// Limiti validi di per_page: la risposta è 200 e il valore arriva in meta.
it('accepts the limits of per_page', function (string $value, int $expected) {
    Ingredient::factory()->count(3)->create();

    $response = $this->getJson('/api/v1/ingredients?per_page='.$value);

    $response->assertOk();
    $response->assertJsonPath('meta.per_page', $expected);
})->with('valid per_page');

// per_page non valido: la risposta è 422 e l'errore è sul campo giusto.
it('rejects an invalid per_page', function (string $value) {
    $response = $this->getJson('/api/v1/ingredients?per_page='.$value);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['per_page']);
})->with('invalid per_page');

// Pagina oltre l'ultima: la risposta è 200 con data vuoto e il totale resta quello vero.
it('returns an empty page when the page is beyond the last one', function () {
    Ingredient::factory()->count(5)->create();

    $response = $this->getJson('/api/v1/ingredients?page=99');

    $response->assertOk();
    $response->assertJsonCount(0, 'data');
    $response->assertJsonPath('meta.total', 5);
});

// Ricerca con pagina: 55 nomi con farina e 3 burro, la seconda pagina della ricerca ne ha 5.
it('paginates the search results', function () {
    Ingredient::factory()
        ->count(55)
        ->sequence(fn ($sequence) => ['name' => 'Farina '.$sequence->index])
        ->create();
    Ingredient::factory()->count(3)->create(['name' => 'Burro']);

    $response = $this->getJson('/api/v1/ingredients?search=farina&page=2');

    $response->assertOk();
    $response->assertJsonCount(5, 'data');
    $response->assertJsonPath('meta.total', 55);
    $response->assertJsonMissing(['name' => 'Burro']);
});

// Elenco vuoto: il totale è 0 e c'è una sola pagina.
it('reports a total of zero when there are no ingredients', function () {
    $response = $this->getJson('/api/v1/ingredients');

    $response->assertOk();
    $response->assertJsonPath('meta.total', 0);
    $response->assertJsonPath('meta.last_page', 1);
});

// Link di pagina: la pagina successiva di una ricerca mantiene il testo cercato e per_page.
it('keeps the search text and per_page in the pagination links', function () {
    Ingredient::factory()
        ->count(5)
        ->sequence(fn ($sequence) => ['name' => 'Farina '.$sequence->index])
        ->create();

    $response = $this->getJson('/api/v1/ingredients?search=farina&per_page=2');

    $response->assertOk();
    expect($response->json('links.next'))
        ->toContain('search=farina')
        ->toContain('per_page=2')
        ->toContain('page=2');
});
