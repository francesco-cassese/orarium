<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;

// Chiede al server motore, versione e database su cui sta lavorando, non alla configurazione.
// Fallisce con "Access denied" o "Unknown database" se il database di test non è raggiungibile.
// La versione va aggiornata insieme al tag dell'immagine in compose.yaml (D-004).

it('runs the tests on MySQL 8.4', function () {
    $server = DB::selectOne('SELECT VERSION() AS version');

    expect(str_starts_with($server->version, '8.4.'))->toBeTrue();
});

it('uses the dedicated test database', function () {
    $database = DB::selectOne('SELECT DATABASE() AS name');

    expect($database->name)->toBe('orarium_test');
});
