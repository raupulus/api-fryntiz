<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\Content\V2;

use App\Enums\ContentPageFormatEnum;
use App\Http\Requests\Api\BaseFormRequest;
use Illuminate\Validation\Rule;

/**
 * Detalle y páginas de un contenido.
 *
 * - `?format=` elige en qué formato sale `body`; sin él, cada página sale en el
 *   suyo (el que marca el panel).
 * - `?from=` y `?limit=`, en `…/pages`: desde qué página (su número) y
 *   cuántas, para contenidos muy largos.
 */
class ContentPagesRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'format' => ['sometimes', 'nullable', 'string', Rule::enum(ContentPageFormatEnum::class)],
            'from' => ['sometimes', 'integer', 'min:1'],
            'limit' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }

    /**
     * @return array<string,string>
     */
    public function messages(): array
    {
        return [
            'format.enum' => 'El formato tiene que ser editorjs, markdown o html.',
            'from.integer' => 'La página de inicio tiene que ser un número.',
            'from.min' => 'La página de inicio empieza en 1.',
            'limit.integer' => 'El número de páginas tiene que ser un número.',
            'limit.min' => 'Hay que pedir al menos una página.',
            'limit.max' => 'Como mucho 100 páginas por petición.',
        ];
    }
}
