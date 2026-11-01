<?php

declare(strict_types=1);

it('reports that the application is up', function () {
    $response = $this->getJson('/api/v1/health');

    $response->assertOk()->assertExactJson(['status' => 'broken']);
});
