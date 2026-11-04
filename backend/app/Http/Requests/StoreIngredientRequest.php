<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\Unit;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreIngredientRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
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
