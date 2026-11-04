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

    /**
     * Ogni ingrediente porta con sé la sua unità e le sue varianti.
     * Con 'variants' => [] il nome è solo quello base.
     *
     * @var list<array{name: string, unit: Unit, variants: list<string>}>
     */
    public const INGREDIENTS = [
        ['name' => 'farina', 'unit' => Unit::Kilogram, 'variants' => ['00', 'integrale', 'manitoba']],
        ['name' => 'burro', 'unit' => Unit::Kilogram, 'variants' => ['salato', 'dolce']],
        ['name' => 'zucchero', 'unit' => Unit::Kilogram, 'variants' => ['semolato', 'a velo', 'di canna']],
        ['name' => 'uova', 'unit' => Unit::Piece, 'variants' => []],
        ['name' => 'latte', 'unit' => Unit::Liter, 'variants' => ['intero', 'parzialmente scremato', 'di soia']],
        ['name' => 'sale', 'unit' => Unit::Kilogram, 'variants' => ['fino', 'grosso', 'affumicato']],
        ['name' => 'lievito', 'unit' => Unit::Gram, 'variants' => ['di birra', 'madre', 'istantaneo']],
        ['name' => 'cioccolato', 'unit' => Unit::Gram, 'variants' => ['fondente', 'al latte', 'bianco']],
        ['name' => 'panna', 'unit' => Unit::Liter, 'variants' => ['fresca', 'vegetale']],
        ['name' => 'mascarpone', 'unit' => Unit::Kilogram, 'variants' => []],
        ['name' => 'ricotta', 'unit' => Unit::Kilogram, 'variants' => ['vaccina', 'di pecora']],
        ['name' => 'mandorle', 'unit' => Unit::Kilogram, 'variants' => ['pelate', 'a lamelle', 'in polvere']],
        ['name' => 'nocciole', 'unit' => Unit::Kilogram, 'variants' => ['tostate', 'in polvere']],
        ['name' => 'pistacchi', 'unit' => Unit::Kilogram, 'variants' => ['sgusciati', 'in polvere']],
        ['name' => 'cacao', 'unit' => Unit::Gram, 'variants' => ['amaro', 'olandese']],
        ['name' => 'miele', 'unit' => Unit::Kilogram, 'variants' => ['millefiori', 'di acacia']],
        ['name' => 'olio', 'unit' => Unit::Liter, 'variants' => ['extra vergine', 'di semi']],
        ['name' => 'vaniglia', 'unit' => Unit::Gram, 'variants' => ['bacche', 'estratto']],
        ['name' => 'cannella', 'unit' => Unit::Gram, 'variants' => ['in polvere', 'in stecche']],
        ['name' => 'limone', 'unit' => Unit::Piece, 'variants' => ['non trattato', 'biologico']],
        ['name' => 'arancia', 'unit' => Unit::Piece, 'variants' => ['biologica', 'non trattata']],
        ['name' => 'fragole', 'unit' => Unit::Kilogram, 'variants' => ['surgelate', 'fresche']],
        ['name' => 'lamponi', 'unit' => Unit::Kilogram, 'variants' => ['surgelati', 'freschi']],
        ['name' => 'mele', 'unit' => Unit::Kilogram, 'variants' => ['golden', 'renette']],
        ['name' => 'pere', 'unit' => Unit::Kilogram, 'variants' => ['williams', 'abate']],
        ['name' => 'albicocche', 'unit' => Unit::Kilogram, 'variants' => ['sciroppate', 'fresche']],
        ['name' => 'amido', 'unit' => Unit::Kilogram, 'variants' => ['di mais', 'di patate']],
        ['name' => 'gelatina', 'unit' => Unit::Gram, 'variants' => ['in fogli', 'in polvere']],
        ['name' => 'strutto', 'unit' => Unit::Kilogram, 'variants' => []],
        ['name' => 'pomodoro', 'unit' => Unit::Kilogram, 'variants' => ['pelati', 'passata']],
        ['name' => 'basilico', 'unit' => Unit::Gram, 'variants' => ['fresco', 'secco']],
        ['name' => 'origano', 'unit' => Unit::Gram, 'variants' => ['secco', 'in foglie']],
        ['name' => 'rum', 'unit' => Unit::Liter, 'variants' => ['scuro', 'bianco']],
        ['name' => 'marsala', 'unit' => Unit::Liter, 'variants' => ['fine', 'superiore']],
        ['name' => 'caffè', 'unit' => Unit::Kilogram, 'variants' => ['in grani', 'macinato']],
        ['name' => 'crema di nocciole', 'unit' => Unit::Kilogram, 'variants' => []],
        ['name' => 'sciroppo di glucosio', 'unit' => Unit::Kilogram, 'variants' => []],
        ['name' => 'semola', 'unit' => Unit::Kilogram, 'variants' => ['di grano duro', 'rimacinata']],
        ['name' => 'orzo', 'unit' => Unit::Kilogram, 'variants' => ['perlato', 'mondo']],
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
                $ingredient = self::INGREDIENTS[fake()->numberBetween(0, count(self::INGREDIENTS) - 1)];
                $name = $ingredient['name'];

                if ($ingredient['variants'] !== []) {
                    $name .= ' '.$ingredient['variants'][fake()->numberBetween(0, count($ingredient['variants']) - 1)];
                }

                $rows[] = [
                    'name' => $name,
                    'unit' => $ingredient['unit']->value,
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
