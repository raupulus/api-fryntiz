<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\Printers\V2;

use App\Http\Requests\Api\BaseFormRequest;
use App\Rules\DeviceStatusPayload;

/**
 * Validación para notificar el resultado de la ejecución física de un trabajo de impresión.
 */
class UpdatePrintJobStatusRequest extends BaseFormRequest
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
            'status' => ['required', 'string', 'in:completed,failed,out_of_paper'],
            'error_message' => ['nullable', 'string', 'required_if:status,failed', 'max:1000'],
            'hardware_device_info' => ['nullable', new DeviceStatusPayload],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'status.required' => 'El estado del trabajo es obligatorio.',
            'status.in' => 'El estado debe ser: completed, failed u out_of_paper.',
            'error_message.required_if' => 'El mensaje de error es obligatorio cuando el estado es failed.',
            'error_message.max' => 'El mensaje de error no puede superar los 1000 caracteres.',
        ];
    }
}
