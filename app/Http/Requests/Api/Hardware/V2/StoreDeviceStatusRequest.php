<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\Hardware\V2;

use App\Http\Requests\Api\BaseFormRequest;
use App\Rules\OwnedHardwareDevice;
use Closure;

/**
 * Validación para almacenar el último estado conocido de un dispositivo en la
 * API V2.
 *
 * Acepta los campos de estado directamente en el cuerpo o agrupados dentro de
 * `hardware_device_info` (para subidas conjuntas junto a otros datos). En ese
 * caso se aplanan a la raíz antes de validar.
 */
class StoreDeviceStatusRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Campos que se aceptan dentro de `hardware_device_info`.
     *
     * Es una lista blanca a propósito: `merge($info)` a secas dejaba que el
     * cliente sobreescribiera CUALQUIER clave de la raíz metiéndola dentro del
     * grupo, incluido `hardware_device_id` (fix1 #13). Aquí sólo pasan los
     * campos de estado, y `hardware_device_id` nunca es uno de ellos.
     *
     * @var array<int, string>
     */
    /** Máximo de claves admitidas en `extra`. */
    private const MAX_EXTRA_KEYS = 30;

    /** Longitud máxima de cada valor de `extra` (o valor máximo si es número). */
    private const MAX_EXTRA_LENGTH = 255;

    private const STATUS_FIELDS = [
        'temp', 'voltage', 'battery_level', 'cpu', 'disk',
        'uptime', 'ip_local', 'ip_public', 'extra',
    ];

    /**
     * Si el estado viene agrupado en `hardware_device_info`, lo aplana a la raíz
     * para poder validarlo con un único conjunto de reglas.
     */
    protected function prepareForValidation(): void
    {
        // El dispositivo viene en la URL (`PUT /hardware/devices/7/status`), no
        // en el cuerpo. Se inyecta aquí para que las reglas de pertenencia
        // sigan aplicándose sobre `hardware_device_id` igual que antes.
        if ($this->route('device') !== null) {
            $this->merge(['hardware_device_id' => $this->route('device')]);
        }

        $info = $this->input('hardware_device_info');

        if (! is_array($info)) {
            return;
        }

        $allowed = array_intersect_key($info, array_flip(self::STATUS_FIELDS));

        // Lo que ya viene en la raíz manda sobre lo agrupado.
        $allowed = array_diff_key($allowed, $this->except('hardware_device_info'));

        if ($allowed !== []) {
            $this->merge($allowed);
        }
    }

    public function rules(): array
    {
        return [
            'hardware_device_id' => ['required', 'integer', 'exists:hardware_devices,id', new OwnedHardwareDevice],
            'temp' => ['nullable', 'numeric'],
            'voltage' => ['nullable', 'numeric'],
            'battery_level' => ['nullable', 'integer', 'between:0,100'],
            'cpu' => ['nullable', 'numeric', 'between:0,100'],
            'disk' => ['nullable', 'numeric', 'between:0,100'],
            'uptime' => ['nullable', 'integer', 'min:0'],
            'ip_local' => ['nullable', 'ip'],
            'ip_public' => ['nullable', 'ip'],
            // `extra` es un cajón de sastre que se guarda en JSON. Sin límites,
            // un cacharro (o quien le robe el token) puede engordar la fila sin
            // freno (fix1 #12).
            'extra' => ['nullable', 'array', 'max:'.self::MAX_EXTRA_KEYS],
            'extra.*' => ['nullable', $this->simpleBoundedValue()],
        ];
    }

    /**
     * Cada valor de `extra` tiene que ser un dato simple y de longitud acotada.
     * No vale una lista ni un objeto anidado: `extra` es un cajón de métricas,
     * no un sitio donde meter un árbol entero.
     */
    private function simpleBoundedValue(): Closure
    {
        return function (string $atributo, mixed $value, Closure $failure): void {
            if (is_array($value) || is_object($value)) {
                $failure('Los valores de extra deben ser simples (número, texto o booleano).');

                return;
            }

            if (is_string($value) && mb_strlen($value) > self::MAX_EXTRA_LENGTH) {
                $failure('Cada valor de extra no puede superar los '.self::MAX_EXTRA_LENGTH.' caracteres.');
            }
        };
    }
}
