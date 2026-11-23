<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\SmartPlant\V2;

use App\Http\Requests\Api\BaseFormRequest;
use App\Rules\DeviceStatusPayload;
use App\Rules\OwnedHardwareDevice;
use App\Rules\OwnedSmartPlant;

/**
 * Validación para almacenar registro de planta en API V2.
 */
class StoreRegisterRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    protected function prepareForValidation(): void
    {
        // Sin `user_id`: `smartplant_registers` no tiene esa columna y
        // `SmartPlantRegister::$fillable` tampoco la incluye. Se validaba con
        // `exists:users,id` un dato que se evaporaba (**N288**). De quién es la
        // lectura se sabe por su planta, y de eso se encarga `OwnedSmartPlant`.
        $mergeData = [];

        // La planta viene en la URL (`POST /smartplant/plants/4/readings`), no
        // en el cuerpo. Anidarla es lo que cierra H5: con `plant_id` suelto y
        // validado sólo con `exists`, cualquiera con la ability escribía en la
        // planta de otro.
        if ($this->route('plant') !== null) {
            $mergeData['plant_id'] = (int) $this->route('plant');
        }

        if ($mergeData !== []) {
            $this->merge($mergeData);
        }
    }

    public function rules(): array
    {
        return [
            // AR-V01: este bloque llegaba sin validar y se lo comía
            // `HardwareService::updateDeviceStatus()`. El servicio filtra las
            // claves, pero no los valores.
            'hardware_device_info' => ['nullable', new DeviceStatusPayload],
            'plant_id' => ['required', 'integer', 'exists:smartplant_plants,id', new OwnedSmartPlant],
            'hardware_device_id' => ['required', 'integer', 'exists:hardware_devices,id', new OwnedHardwareDevice],
            // AD-T01: `uv`, `soil_humidity` y `soil_humidity_raw` son `integer`
            // en BD; `numeric` admitía decimales que revientan el INSERT con un
            // 500. Se acotan además a rangos físicos plausibles.
            'uv' => ['nullable', 'integer', 'between:0,20'],
            'pressure' => ['nullable', 'numeric', 'between:800,1100'],
            'temperature' => ['nullable', 'numeric', 'between:-50,80'],
            'humidity' => ['nullable', 'numeric', 'between:0,100'],
            'soil_humidity' => ['required', 'integer', 'min:0', 'max:100'],
            'soil_humidity_raw' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'full_water_tank' => ['nullable', 'boolean'],
            'waterpump_enabled' => ['nullable', 'boolean'],
            'vaporizer_enabled' => ['nullable', 'boolean'],
        ];
    }
}
