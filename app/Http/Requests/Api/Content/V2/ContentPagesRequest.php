<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\Content\V2;

use App\Enums\ContentPageFormatEnum;
use App\Http\Requests\Api\BaseFormRequest;
use Illuminate\Validation\Rule;

/**
 * Páginas de un contenido: `?format=` elige en qué formato sale `body`.
 *
 * Sin `format`, cada página sale en el suyo (el que marca el panel).
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
        ];
    }

    /**
     * @return array<string,string>
     */
    public function messages(): array
    {
        return [
            'format.enum' => 'El formato tiene que ser editorjs, markdown o html.',
        ];
    }
}
