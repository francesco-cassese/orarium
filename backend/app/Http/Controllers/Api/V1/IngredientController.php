<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreIngredientRequest;
use App\Http\Requests\UpdateIngredientRequest;
use App\Http\Resources\IngredientResource;
use App\Models\Ingredient;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

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

    public function store(StoreIngredientRequest $request): IngredientResource
    {
        return new IngredientResource(Ingredient::create($request->validated()));
    }

    public function update(UpdateIngredientRequest $request, Ingredient $ingredient): IngredientResource
    {
        $ingredient->update($request->validated());

        return new IngredientResource($ingredient);
    }

    public function destroy(Ingredient $ingredient): Response
    {
        $ingredient->delete();

        return response()->noContent();
    }
}
