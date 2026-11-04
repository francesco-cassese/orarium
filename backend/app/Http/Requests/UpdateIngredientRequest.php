<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\Unit;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateIngredientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'unit' => ['required', Rule::enum(Unit::class)],
            // Massimo di un intero a 32 bit senza segno (D-011).
            'price_cents' => ['required', 'integer', 'min:0', 'max:4294967295'],
        ];
    }
}
