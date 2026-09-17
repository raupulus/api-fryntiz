<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\Printers\V2;

use App\Http\Requests\Api\BaseFormRequest;
use App\Rules\DeviceStatusPayload;

/**
 * Validación para reclamar el siguiente trabajo de impresión por parte del agente IoT.
 */
class ClaimNextJobRequest extends BaseFormRequest
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
            'hardware_device_info' => ['nullable', new DeviceStatusPayload],
        ];
    }
}
