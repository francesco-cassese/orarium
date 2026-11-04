<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// I test in Feature usano TestCase e ripartono da un database pulito (RefreshDatabase).
pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');
