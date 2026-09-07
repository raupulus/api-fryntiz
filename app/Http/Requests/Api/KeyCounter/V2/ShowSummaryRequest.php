<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\KeyCounter\V2;

use App\Http\Requests\Api\BaseFormRequest;
use App\Rules\OwnedHardwareDevice;
use Carbon\CarbonImmutable;

/**
 * Validación del resumen de KeyCounter.
 *
 * `date` admite cuatro formas y ninguna más:
 *
 *   today        el día de hoy
 *   month        el mes en curso
 *   2026-09-07   ese día
 *   2026-09      ese mes entero
 *
 * Un solo parámetro para los cuatro casos: el cliente que quiere «lo de hoy»
 * escribe `today` y no tiene que calcular la fecha, y el que quiere un día
 * concreto la escribe. Sin `date` se entiende `today`, que es para lo que se
 * pide el endpoint: un cacharro arrancando.
 */
class ShowSummaryRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'device_id' => ['required', 'integer', 'exists:hardware_devices,id', new OwnedHardwareDevice],
            // El regex fija la forma; la closure, que la fecha exista de verdad:
            // «2026-13-45» tiene la forma buena y no es ninguna fecha.
            'date' => ['nullable', 'string', 'regex:/^(today|month|\d{4}-\d{2}(-\d{2})?)$/', function (string $atributo, mixed $valor, \Closure $fail) {
                if (! is_string($valor) || in_array($valor, ['today', 'month'], true)) {
                    return;
                }

                [$anio, $mes, $dia] = array_pad(array_map('intval', explode('-', $valor)), 3, 1);

                if (! checkdate($mes, $dia, $anio)) {
                    $fail('El periodo indica una fecha que no existe.');
                }
            }],
        ];
    }

    /**
     * `OwnedHardwareDevice` informa sobre el campo que valida, y aquí se llama
     * `device_id`, no `hardware_device_id`.
     *
     * @return array<string,string>
     */
    public function attributes(): array
    {
        return [
            'device_id' => 'dispositivo',
        ];
    }

    /**
     * @return array<string,string>
     */
    public function messages(): array
    {
        return [
            'date.regex' => 'El periodo debe ser «today», «month», una fecha «AAAA-MM-DD» o un mes «AAAA-MM».',
        ];
    }

    /**
     * Periodo pedido, tal cual lo escribió el cliente.
     */
    public function periodo(): string
    {
        $date = $this->input('date');

        return is_string($date) && $date !== '' ? $date : 'today';
    }

    /**
     * Dispositivo del que se pide el resumen.
     *
     * Obligatorio: el resumen es de **un** cacharro, el que pregunta. No hay
     * agregado de varios ni falta que hace. `OwnedHardwareDevice` ya comprueba
     * que sea del usuario del token y que el token lo alcance si está ligado a
     * un `device:{id}`.
     */
    public function dispositivo(): int
    {
        return (int) $this->input('device_id');
    }

    /**
     * Los dos extremos del periodo, ambos incluidos.
     *
     * Las fechas se guardan en UTC (`APP_TIMEZONE=UTC`) y el corte se hace en
     * UTC: «hoy» es el día UTC, el mismo que enseña la web de KeyCounter. Un
     * cacharro en España que arranca a la 01:00 local pide el día que ya lleva
     * una hora corriendo aquí.
     *
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    public function rango(): array
    {
        $periodo = $this->periodo();
        $hoy = CarbonImmutable::now('UTC');

        return match (true) {
            $periodo === 'today' => [$hoy->startOfDay(), $hoy->endOfDay()],
            $periodo === 'month' => [$hoy->startOfMonth(), $hoy->endOfMonth()],
            // «2026-09»: mes entero.
            strlen($periodo) === 7 => [
                $mes = CarbonImmutable::createFromFormat('Y-m-d', $periodo.'-01', 'UTC')->startOfDay(),
                $mes->endOfMonth(),
            ],
            // «2026-09-07»: ese día.
            default => [
                $dia = CarbonImmutable::createFromFormat('Y-m-d', $periodo, 'UTC')->startOfDay(),
                $dia->endOfDay(),
            ],
        };
    }
}
