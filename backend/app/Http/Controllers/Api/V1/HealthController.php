<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class HealthController extends Controller
{
    /**
     * Liveness check: answers without touching the database or Redis, so that the k6 benchmark
     * measures only the cost of the framework.
     */
    final public function __invoke(): JsonResponse
    {
        return response()->json(['status' => 'ok']);
    }
}
