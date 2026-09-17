<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\Printers\V2;

use App\Enums\PrinterStatusEnum;
use App\Http\Requests\Api\BaseFormRequest;
use App\Rules\DeviceStatusPayload;
use Illuminate\Validation\Rule;

/**
 * Validación para el heartbeat de una impresora física.
 */
class PrinterHeartbeatRequest extends BaseFormRequest
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
            'status' => ['required', 'string', Rule::enum(PrinterStatusEnum::class)],
            'hardware_device_info' => ['nullable', new DeviceStatusPayload],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'status.required' => 'El estado de la impresora es obligatorio.',
            'status.enum' => 'El estado especificado no es válido (ready, busy, out_of_paper, cover_open, offline, error).',
        ];
    }
}
