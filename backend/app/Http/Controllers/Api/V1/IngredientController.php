<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\IngredientResource;
use App\Models\Ingredient;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class IngredientController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Ingredient::query();

        $search = $request->string('search');

        if ($search->isNotEmpty()) {
            // Limite noto: % e _ nel testo cercato fanno da jolly.
            $query->where('name', 'like', '%'.$search.'%');
        }

        return IngredientResource::collection($query->get());
    }

    public function show(Ingredient $ingredient): IngredientResource
    {
        return new IngredientResource($ingredient);
    }
}
