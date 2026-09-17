<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\Printers\V2;

use App\Http\Requests\Api\BaseFormRequest;

/**
 * Validación para alternar o establecer el estado favorito de un trabajo.
 */
class ToggleFavoriteJobRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'is_favorite' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'is_favorite.boolean' => 'El campo favorito debe ser un valor booleano.',
        ];
    }
}
