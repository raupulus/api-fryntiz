<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\Printers\V2;

use App\Http\Requests\Api\BaseFormRequest;

/**
 * Validación para reimprimir un trabajo existente.
 */
class ReprintJobRequest extends BaseFormRequest
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
            'priority' => ['nullable', 'integer', 'between:-100,100'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'priority.between' => 'La prioridad debe estar comprendida entre -100 y 100.',
        ];
    }
}
