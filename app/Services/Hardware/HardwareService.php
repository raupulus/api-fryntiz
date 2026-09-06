<?php

declare(strict_types=1);

namespace App\Services\Hardware;

use App\Models\Hardware\HardwareDevice;
use App\Models\Hardware\HardwareEnergy;
use App\Models\Hardware\HardwarePowerGenerator;
use App\Models\Hardware\HardwarePowerGeneratorHistorical;
use App\Models\Hardware\HardwarePowerGeneratorSolar;
use App\Models\Hardware\HardwarePowerGeneratorToday;
use App\Models\Hardware\HardwarePowerLoad;
use App\Models\Hardware\HardwarePowerLoadHistorical;
use App\Models\Hardware\HardwarePowerLoadToday;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;

/**
 * Servicio encargado de la gestión de dispositivos de hardware, monitorización y registros energéticos.
 */
class HardwareService
{
    /**
     * Obtiene la información detallada de un dispositivo de hardware específico,
     * incluyendo su tipo y los componentes instalados.
     *
     * **Siempre acotado a un usuario.** Antes hacía `find($deviceId)` a secas,
     * así que `GET /api/v2/hardware/device/{id}` devolvía cualquier dispositivo
     * de cualquier usuario —con número de serie incluido— iterando el id
     * (auditoría A3). El parámetro `$userId` no es opcional a propósito: si
     * alguna vez hace falta el dispositivo sin filtrar (comandos, panel), se
     * usa el modelo directamente y se ve en el diff.
     *
     * @param  int  $deviceId  Identificador único del dispositivo.
     * @param  int  $userId  Propietario que debe tener el dispositivo.
     * @return HardwareDevice|null Modelo del dispositivo o null si no existe o no es suyo.
     */
    public function getDeviceInfo(int $deviceId, int $userId): ?HardwareDevice
    {
        return HardwareDevice::query()
            ->where('user_id', $userId)
            ->with(['type', 'components.availableComponent'])
            ->find($deviceId);
    }

    /**
     * Obtiene la lista de computadoras o dispositivos asociados a un usuario determinado.
     *
     * @param  int  $userId  Identificador único del usuario.
     * @return Collection Colección de dispositivos de hardware.
     */
    public function getComputersList(int $userId): Collection
    {
        return HardwareDevice::forUser($userId)->with('type')->get();
    }

    /**
     * Guarda las lecturas que manda un monitor de energía (D115).
     *
     * `hardware_energy` **es la tabla de configuración**: dice qué mide este
     * dispositivo, en qué canal y si eso es generación o consumo. No es una
     * tabla de lecturas. v2 hacía `HardwareEnergy::create($data)`, o sea que por
     * cada petición creaba una fila de configuración basura y no guardaba ni una
     * medida (**H1**, **R-3**, **N295**).
     *
     * El recorrido es éste:
     *
     *   readings[].pos --(== hardware_energy.sensor_position)--> elemento
     *                                     |
     *                    role: generator  |  role: load | storage
     *                          v          |          v
     *          hardware_power_generators  |  hardware_power_loads
     *                          +----------+----------+
     *                                     v
     *                        ..._today       (resumen del día)
     *                        ..._historical  (acumulado)
     *
     * Un elemento con `role = storage` es la batería: su corriente se registra
     * en la tabla de consumos, porque los agregados de generación de una
     * instalación cuentan **sólo** los elementos con `role = generator`. Así una
     * batería cargándose no se cuenta como energía producida.
     *
     * La lectura se atribuye al dispositivo **monitorizado**, no al monitor: el
     * monitor mide varias cosas y cada una es de su aparato.
     *
     * @param  array<string,mixed>  $data  Petición ya validada.
     * @return array{readings: list<HardwarePowerLoad|HardwarePowerGenerator>, warnings: list<string>}
     */
    public function storeEnergyData(array $data): array
    {
        $device = HardwareDevice::query()
            ->with([
                'hardwareEnergy' => static fn ($q) => $q->where('is_active', true),
                'hardwareEnergy.monitorized',
            ])
            ->find((int) ($data['hardware_device_id'] ?? 0));

        if (! $device) {
            return ['readings' => [], 'warnings' => ['El dispositivo no existe.']];
        }

        // La batería del propio monitor va en el dispositivo, no en las tablas
        // de energía (D108). Meterla ahí es lo que hacía el caso especial del
        // dispositivo 14: la tensión de una Pico acababa contada como consumo.
        $this->updateDeviceBattery($device, $data);

        $elements = $device->hardwareEnergy->keyBy('sensor_position');
        $now = now();
        $date = $now->format('Y-m-d');
        $duration = isset($data['duration']) ? (int) $data['duration'] : null;
        $temperature = isset($data['temperature']) ? (float) $data['temperature'] : null;

        $guardadas = [];
        $warnings = [];

        foreach ($data['readings'] ?? [] as $reading) {
            $position = (int) ($reading['pos'] ?? -1);
            $element = $elements->get($position);

            if (! $element instanceof HardwareEnergy) {
                $warnings[] = "El canal {$position} no tiene ningún elemento activo dado de alta; su lectura no se ha guardado.";

                continue;
            }

            $guardadas[] = $this->storeReading(
                $element,
                $reading,
                $duration,
                $temperature,
                $now,
                $date,
                $warnings
            );
        }

        return ['readings' => $guardadas, 'warnings' => $warnings];
    }

    /**
     * Deriva y guarda una lectura, y la suma a los resúmenes si es fiable.
     *
     * @param  array<string,mixed>  $reading
     * @param  list<string>  $warnings
     */
    private function storeReading(
        HardwareEnergy $element,
        array $reading,
        ?int $duration,
        ?float $temperature,
        CarbonInterface $now,
        string $date,
        array &$warnings
    ): HardwarePowerLoad|HardwarePowerGenerator {
        $amperage = isset($reading['amperage']) ? (float) $reading['amperage'] : null;
        $measure = isset($reading['voltage']) ? (float) $reading['voltage'] : null;
        $seconds = isset($reading['duration']) ? (int) $reading['duration'] : $duration;

        [$voltage, $fuenteDelVoltaje] = $element->resolveVoltage($measure);

        $deviceWattHours = isset($reading['energy_wh']) ? (float) $reading['energy_wh'] : null;
        $vatiosHora = $deviceWattHours ?? $element->computeWattHours($amperage, $voltage, $seconds);
        $fuenteDeLaEnergia = $deviceWattHours !== null ? 'device' : 'derived';

        $isGenerator = $element->isGenerator();
        $name = $element->name ?? "canal {$element->sensor_position}";

        $model = $isGenerator ? new HardwarePowerGenerator : new HardwarePowerLoad;

        $model->fill([
            // El aparato al que pertenece la medida, no el que mide.
            'hardware_device_id' => $element->hardware_device_monitorized_id ?? $element->hardware_device_id,
            'hardware_energy_id' => $element->id,

            // Crudos: la verdad. Se guardan aunque sean raros.
            'amperage' => $amperage,
            'voltage' => $voltage,
            'delta_seconds' => $seconds,

            // Derivados: caché de lo que sale de los crudos.
            'power' => $element->computePower($amperage, $voltage),
            'energy_wh' => $vatiosHora,
            'energy_ah' => $element->computeAmpHours($amperage, $seconds),

            'energy_source' => $fuenteDeLaEnergia,
            'voltage_source' => $fuenteDelVoltaje,

            'temperature' => $reading['temperature'] ?? $temperature,
            'battery_voltage' => $reading['battery_voltage'] ?? null,
            'battery_percentage' => $reading['battery_percentage'] ?? null,
            'read_at' => $reading['read_at'] ?? $now,
        ]);

        if ($model instanceof HardwarePowerLoad) {
            $model->fan = $reading['fan'] ?? null;
        }

        // ── Lo que hace sospechosa una lectura (D72, D110) ──────────────────
        //
        // Se marca, nunca se descarta: si un día resulta que el raro era el
        // criterio y no el dato, los crudos siguen ahí para recalcularlo todo.
        if ($amperage !== null && $amperage < 0) {
            $model->markSuspicious('corriente negativa: revisar el conexionado del INA');
            $warnings[] = "«{$name}» ha mandado corriente negativa ({$amperage} A): revisa el conexionado del sensor.";
        }

        if ($voltage === null) {
            $model->markSuspicious('sin tension: ni medida ni nominal');
            $warnings[] = "«{$name}» no trae tensión y el elemento no tiene tensión nominal: sin eso no hay vatios. Rellena `nominal_voltage`.";
        } elseif ($fuenteDelVoltaje === 'nominal' && $measure !== null) {
            $warnings[] = "«{$name}» ha medido {$measure} V, fuera de lo plausible para el elemento: se ha usado su tensión nominal ({$voltage} V).";
        }

        if ($seconds === null) {
            $warnings[] = "«{$name}» no trae «duration»: se guarda la potencia, pero no los vatios-hora del periodo.";
        }

        $model->save();

        // Una lectura marcada no entra en los agregados del día.
        if (! $model->is_suspicious) {
            $this->summariseReading($model, $element, $isGenerator, $date);
        }

        return $model;
    }

    /**
     * Suma la lectura al resumen del día del elemento y recalcula su acumulado.
     */
    private function summariseReading(
        HardwarePowerLoad|HardwarePowerGenerator $model,
        HardwareEnergy $element,
        bool $isGenerator,
        string $date
    ): void {
        $summary = [
            'voltage' => $model->voltage,
            'amperage' => $model->amperage,
            'power' => $model->power,
            'energy_wh' => $model->energy_wh,
            'energy_ah' => $model->energy_ah,
            'temperature' => $model->temperature,
            'battery' => $model->battery_voltage,
            'battery_percentage' => $model->battery_percentage,
            'fan' => $model instanceof HardwarePowerLoad ? $model->fan : null,
            'read_at' => $model->read_at,
        ];

        $deviceId = (int) $model->hardware_device_id;
        $elementId = $element->id;

        if ($isGenerator) {
            HardwarePowerGeneratorToday::recalculateToday($deviceId, $elementId, $summary, $date);
            HardwarePowerGeneratorHistorical::calculateHistoricalFromTodays($deviceId, $elementId);

            return;
        }

        HardwarePowerLoadToday::recalculateToday($deviceId, $elementId, $summary, $date);
        HardwarePowerLoadHistorical::calculateHistoricalFromTodays($deviceId, $elementId);
    }

    /**
     * Guarda, si viene, la batería del propio dispositivo (D108).
     *
     * Es siempre opcional y la puede mandar cualquier endpoint IoT.
     * `battery_read_at` existe para poder distinguir un dato de ahora de uno de
     * hace tres semanas.
     *
     * @param  array<string,mixed>  $data
     */
    public function updateDeviceBattery(HardwareDevice $device, array $data): void
    {
        $voltageValue = $data['battery_voltage'] ?? null;
        $percentage = $data['battery_percentage'] ?? null;

        if ($voltageValue === null && $percentage === null) {
            return;
        }

        if ($voltageValue !== null) {
            $device->battery_voltage = (float) $voltageValue;
        }

        if ($percentage !== null) {
            $device->battery_percentage = (int) $percentage;
        }

        $device->battery_read_at = $data['read_at'] ?? now();
        $device->save();
    }

    /**
     * Registra la lectura de un controlador solar (D109).
     *
     * Es un generador con dos bloques de más —estadísticas del día y acumulado
     * histórico del controlador—, y con la detección de reinicio que traía V1:
     * `total_operating_days` sólo puede subir, así que si **baja**, el aparato se
     * ha reseteado y sus contadores han vuelto a cero. Esa lectura abre fila
     * nueva y no machaca la anterior; sin eso, un reset borra el acumulado de
     * años.
     *
     * @param  array<string,mixed>  $data  Petición ya validada.
     * @return array{reading: HardwarePowerGeneratorSolar, warnings: list<string>}
     */
    public function storeSolarReading(array $data): array
    {
        $warnings = [];
        $deviceId = (int) $data['hardware_device_id'];

        $previous = HardwarePowerGeneratorSolar::latestForDevice($deviceId, $data['serial_number'] ?? null);
        $daysNow = isset($data['total_operating_days']) ? (int) $data['total_operating_days'] : null;

        if (HardwarePowerGeneratorSolar::hasRestarted($previous, $daysNow)) {
            $warnings[] = sprintf(
                'El controlador se ha reiniciado: sus días de funcionamiento han bajado de %d a %d. '.
                'Los acumulados anteriores se conservan en la lectura del %s.',
                (int) $previous->total_operating_days,
                (int) $daysNow,
                (string) $previous->read_at
            );
        }

        $element = $this->solarElementFor($deviceId);

        if ($element === null) {
            $warnings[] = 'El controlador no tiene ningún elemento generador dado de alta: la lectura se guarda sin asignar a una instalación.';
        }

        $reading = new HardwarePowerGeneratorSolar($data);
        $reading->hardware_energy_id = $element?->id;

        // Un controlador manda su propio acumulado, no una media de periodo: los
        // vatios-hora del día los da él y no hay nada que derivar.
        $reading->energy_source = isset($data['day_power_generation_wh']) ? 'device' : 'derived';

        // AD-Q04 (auditoría de datos 2026-09-02): se guarda antes de que el
        // bloque siguiente lo sobrescriba con V×A, para poder comparar lo que
        // dijo el aparato contra lo calculado.
        $devicePower = $reading->power;

        if ($element !== null) {
            [$voltage, $fuente] = $element->resolveVoltage($reading->voltage);
            $reading->voltage = $voltage;
            $reading->voltage_source = $fuente;
            $reading->power = $element->computePower($reading->amperage, $voltage);
        }

        if ($reading->amperage !== null && $reading->amperage < 0) {
            $reading->markSuspicious('corriente negativa: revisar el conexionado');
            $warnings[] = 'El controlador ha mandado corriente negativa: revisa el conexionado.';
        }

        // AD-Q04: un Renogy Rover real en producción mandó `power` hasta 82 W
        // distinto de V×A en un puñado de lecturas (hipo del firmware, no
        // redondeo: el ruido normal de muestreo se queda por debajo de 20 W).
        // Umbral absoluto y no porcentual: uno porcentual dispara en falso
        // constantemente con corrientes bajas (p.ej. 0,03 A).
        if ($devicePower !== null && $reading->power !== null
            && abs($devicePower - $reading->power) > 20.0) {
            $reading->markSuspicious(sprintf(
                'potencia del aparato (%.2f W) muy distinta de V×A (%.2f W)',
                $devicePower,
                $reading->power
            ));
            $warnings[] = sprintf(
                'El controlador ha mandado %.2f W pero V×A da %.2f W: revisa la calibración del sensor.',
                $devicePower,
                $reading->power
            );
        }

        $reading->save();

        return ['reading' => $reading, 'warnings' => $warnings];
    }

    /**
     * Elemento generador activo del controlador solar, si lo hay.
     *
     * Un controlador solar es un aparato entero, no un canal de un monitor: se
     * coge su primer elemento generador y no se exige `sensor_position`.
     */
    private function solarElementFor(int $hardwareDeviceId): ?HardwareEnergy
    {
        return HardwareEnergy::query()
            ->where('hardware_device_id', $hardwareDeviceId)
            ->active()
            ->generators()
            ->orderBy('sensor_position')
            ->first();
    }

    /**
     * Actualiza el último estado conocido de un dispositivo de hardware.
     *
     * No se guarda histórico: solo se sobrescribe el último estado del propio
     * dispositivo (temperatura, tensión, batería, CPU, disco, uptime, IPs y
     * métricas extra). El campo `last_seen_at` se fija al momento actual.
     *
     * Solo se actualizan las claves presentes en `$data` para no sobrescribir
     * con nulos valores previamente conocidos que no vengan en esta subida.
     *
     * @param  int  $deviceId  Identificador del dispositivo hardware.
     * @param  array  $data  Estado del dispositivo (temp, voltage, battery_level, cpu, disk, ram, uptime, ip_local, ip_public, extra).
     * @return HardwareDevice Dispositivo actualizado.
     */
    public function updateDeviceStatus(int $deviceId, array $data): HardwareDevice
    {
        $device = HardwareDevice::query()->findOrFail($deviceId);

        $allowed = ['temp', 'voltage', 'battery_level', 'cpu', 'disk', 'ram', 'uptime', 'ip_local', 'ip_public', 'extra'];

        $status = array_intersect_key($data, array_flip($allowed));

        $status['last_seen_at'] = now();

        $device->fill($status)->save();

        return $device;
    }
}
