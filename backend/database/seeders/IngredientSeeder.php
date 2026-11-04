<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\Unit;
use App\Models\Ingredient;
use Illuminate\Database\Seeder;
use RuntimeException;

final class IngredientSeeder extends Seeder
{
    public const COUNT = 20_000;

    private const CHUNK_SIZE = 1_000;

    private const BASE_NAMES = [
        'farina', 'burro', 'zucchero', 'uova', 'latte', 'sale', 'lievito', 'cioccolato',
        'panna', 'mascarpone', 'ricotta', 'mandorle', 'nocciole', 'pistacchi', 'cacao', 'miele',
        'olio', 'vaniglia', 'cannella', 'limone', 'arancia', 'fragole', 'lamponi', 'mele',
        'pere', 'albicocche', 'amido', 'gelatina', 'strutto', 'pomodoro', 'basilico', 'origano',
        'rum', 'marsala', 'caffè', 'crema di nocciole', 'sciroppo di glucosio', 'semola', 'orzo',
    ];

    private const VARIANTS = [
        '00', 'integrale', 'bio', 'fondente', 'al latte', 'semisalato', 'dolce', 'fresco',
        'in polvere', 'pastorizzato', 'extra vergine', 'a fiocchi',
    ];

    public function run(int $count = self::COUNT, int $chunkSize = self::CHUNK_SIZE): void
    {
        if (app()->isProduction()) {
            throw new RuntimeException('IngredientSeeder must not run in production.');
        }

        fake()->seed(1);

        $now = now()->toDateTimeString();
        $written = 0;

        while ($written < $count) {
            $chunkLength = min($chunkSize, $count - $written);
            $rows = [];

            for ($i = 0; $i < $chunkLength; $i++) {
                $rows[] = [
                    'name' => fake()->randomElement(self::BASE_NAMES).' '.fake()->randomElement(self::VARIANTS),
                    'unit' => fake()->randomElement(Unit::cases())->value,
                    'price_cents' => fake()->numberBetween(50, 5000),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            Ingredient::insert($rows);
            $written += $chunkLength;
        }
    }
}
