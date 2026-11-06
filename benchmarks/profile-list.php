<?php

declare(strict_types=1);

/**
 * Misura dove va il tempo di GET /api/v1/ingredients, senza server web.
 *
 * Uso, dalla radice del progetto, con il database di sviluppo acceso:
 *   php benchmarks/profile-list.php [--out=percorso.json]
 *
 * Un processo = una corsa. Il tempo di avvio dell'applicazione si misura una volta per processo;
 * ogni passo ha un riscaldamento scartato e cinque ripetizioni, e si riporta la mediana.
 */

use App\Http\Resources\IngredientResource;
use App\Models\Ingredient;
use Illuminate\Contracts\Console\Kernel as ConsoleKernel;
use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

const REPEATS = 5;
const SEARCH = 'farina';

function median(array $values): float
{
    sort($values);
    $n = count($values);

    return $n % 2 === 1
        ? (float) $values[intdiv($n, 2)]
        : ($values[intdiv($n, 2) - 1] + $values[intdiv($n, 2)]) / 2;
}

/**
 * @return array{median_ms: float, min_ms: float, max_ms: float, peak_mb: float}
 */
function measure(callable $step): array
{
    $step(); // riscaldamento, scartato

    $times = [];
    $peaks = [];

    for ($i = 0; $i < REPEATS; $i++) {
        gc_collect_cycles();
        memory_reset_peak_usage();
        $base = memory_get_usage();
        $start = hrtime(true);
        $result = $step();
        $times[] = (hrtime(true) - $start) / 1e6;
        $peaks[] = memory_get_peak_usage() - $base;
        unset($result);
    }

    return [
        'median_ms' => round(median($times), 2),
        'min_ms' => round(min($times), 2),
        'max_ms' => round(max($times), 2),
        'peak_mb' => round(median($peaks) / 1048576, 2),
    ];
}

$out = null;
foreach (array_slice($argv, 1) as $argument) {
    if (str_starts_with($argument, '--out=')) {
        $out = substr($argument, 6);
    }
}

// Avvio dell'applicazione: caricamento di Composer, configurazione, provider.
$backend = dirname(__DIR__).'/backend';
$bootStart = hrtime(true);
require $backend.'/vendor/autoload.php';
$app = require $backend.'/bootstrap/app.php';
$app->make(ConsoleKernel::class)->bootstrap();
$bootMs = round((hrtime(true) - $bootStart) / 1e6, 2);

if (! $app->environment('local')) {
    fwrite(STDERR, 'Ambiente "'.$app->environment().'": questo script gira solo in "local".'.PHP_EOL);
    exit(1);
}

$database = DB::getDatabaseName();
if (str_ends_with($database, '_test')) {
    fwrite(STDERR, "Il database \"$database\" e' quello dei test: serve quello di sviluppo.".PHP_EOL);
    exit(1);
}

/** @var HttpKernel $http */
$http = $app->make(HttpKernel::class);

$request = static fn (string $uri): Request => Request::create($uri, 'GET', [], [], [], ['HTTP_ACCEPT' => 'application/json']);

$rows = Ingredient::count();
$result = [
    'php' => PHP_VERSION,
    'opcache' => function_exists('opcache_get_status') && ini_get('opcache.enable_cli') === '1',
    'database' => $database,
    'rows' => $rows,
    'boot_ms' => $bootMs,
    'framework_request_ms' => measure(fn () => $http->handle($request('/api/v1/health'))->getContent()),
    'scenarios' => [],
];

$scenarios = [
    'list' => ['uri' => '/api/v1/ingredients', 'search' => null],
    'search' => ['uri' => '/api/v1/ingredients?search='.SEARCH, 'search' => SEARCH],
];

foreach ($scenarios as $name => $scenario) {
    $search = $scenario['search'];

    $query = static fn () => Ingredient::query()
        ->when($search !== null, static fn ($builder) => $builder->where('name', 'like', '%'.$search.'%'));

    $sql = 'select * from ingredients'.($search !== null ? ' where name like ?' : '');
    $bindings = $search !== null ? ['%'.$search.'%'] : [];

    $models = $query()->get();
    $resolved = IngredientResource::collection($models)->resolve($request($scenario['uri']));
    $bytes = strlen($http->handle($request($scenario['uri']))->getContent());

    $result['scenarios'][$name] = [
        'rows' => count($models),
        'bytes' => $bytes,
        'query_raw' => measure(fn () => DB::select($sql, $bindings)),
        'query_and_models' => measure(fn () => $query()->get()),
        'resources' => measure(fn () => IngredientResource::collection($models)->resolve($request($scenario['uri']))),
        'json_encode' => measure(fn () => json_encode(['data' => $resolved])),
        'count_query' => measure(fn () => $query()->count()),
        'first_page' => measure(fn () => $query()->orderBy('id')->limit(50)->get()),
        'full_request' => measure(fn () => $http->handle($request($scenario['uri']))->getContent()),
    ];
}

// Piano di esecuzione della ricerca (solo MySQL). Il testo e' fisso: nessun input esterno.
if (DB::getDriverName() === 'mysql') {
    try {
        $result['explain_search'] = DB::select("explain select * from ingredients where name like '%".SEARCH."%'");
        $analyze = DB::select("explain analyze select * from ingredients where name like '%".SEARCH."%'");
        $result['explain_analyze_search'] = array_map(static fn ($row) => array_values((array) $row)[0], $analyze);
    } catch (Throwable $e) {
        $result['explain_error'] = $e->getMessage();
    }
}

$json = json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

if ($out !== null) {
    file_put_contents($out, $json.PHP_EOL);
}

echo $json.PHP_EOL;