<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\Hardware\V2;

use App\Http\Requests\Api\BaseFormRequest;
use Illuminate\Validation\Rule;

/**
 * Validación para consultar un dispositivo concreto.
 *
 * Acepta el parámetro opcional `include` (lista separada por comas) con lo que
 * se quiere añadir a la respuesta. Hoy sólo existe `status`.
 *
 * El bloque de estado va **detrás de un parámetro** y no de serie: lleva la IP
 * local y la pública del cacharro, y esas no tienen por qué viajar en cada
 * respuesta del inventario.
 */
class ShowDeviceRequest extends BaseFormRequest
{
    /**
     * Lo que se puede pedir en `include`.
     */
    public const INCLUDES = ['status'];

    public function authorize(): bool
    {
        return true;
    }

    /**
     * Normaliza `include` de "status" o "status,otro" a array antes de validar.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('include') && is_string($this->query('include'))) {
            $partes = array_filter(array_map('trim', explode(',', (string) $this->query('include'))));
            $this->merge(['include' => array_values($partes)]);
        }
    }

    public function rules(): array
    {
        return [
            'include' => ['sometimes', 'array'],
            'include.*' => [Rule::in(self::INCLUDES)],
        ];
    }

    /**
     * @return array<string,string>
     */
    public function messages(): array
    {
        return [
            'include.*.in' => 'Sólo se puede incluir: '.implode(', ', self::INCLUDES).'.',
        ];
    }

    /**
     * ¿Se ha pedido el bloque de estado?
     */
    public function quiereEstado(): bool
    {
        return in_array('status', (array) $this->input('include', []), true);
    }
}
