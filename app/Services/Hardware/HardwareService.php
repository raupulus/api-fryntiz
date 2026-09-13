<?php

declare(strict_types=1);

namespace App\Services\Hardware;

use App\Models\Hardware\HardwareDevice;
use App\Models\Hardware\HardwareEnergy;
use App\Models\Hardware\HardwareEnergyHistorical;
use App\Models\Hardware\HardwareEnergyReading;
use App\Models\Hardware\HardwareEnergyToday;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

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

        $allowed = [
            'temp', 'voltage', 'battery_level', 'cpu', 'disk', 'ram', 'uptime',
            'ip_local', 'ip_public', 'extra', 'battery_voltage', 'battery_percentage', 'battery_read_at',
        ];

        $status = array_intersect_key($data, array_flip($allowed));

        $status['last_seen_at'] = now();

        $device->fill($status)->save();

        return $device;
    }

    /**
     * Guarda la telemetría de energía universal en la arquitectura unificada (D115, Fase 5).
     *
     * Persiste en hardware_energy_readings, actualiza agregados diarios en hardware_energy_today
     * y series acumuladas en hardware_energy_historical con gestión de sesiones.
     *
     * @param  int  $deviceId  Dispositivo medidor o al que pertenece la telemetría.
     * @param  array<string, mixed>  $energy  Estructura validada de telemetría.
     * @return array{readings: list<HardwareEnergyReading>, warnings: list<string>}
     */
    public function storeEnergyTelemetry(int $deviceId, array $energy): array
    {
        $device = HardwareDevice::query()
            ->with([
                // Con los borrados dentro: el índice único de `hardware_energy`
                // no sabe de `deleted_at`, así que un elemento borrado lógicamente
                // seguía siendo invisible aquí, se intentaba crear otro igual y
                // el dispositivo entero se comía un 500 en cada subida.
                'hardwareEnergy' => static fn ($q) => $q->withTrashed(),
                'hardwareEnergy.monitorized',
            ])
            ->find($deviceId);

        if (! $device) {
            return ['readings' => [], 'warnings' => ['El dispositivo no existe.']];
        }

        // `duration` es lo que convierte una potencia en energía. Cuando no
        // llega, cada elemento dice cuántos segundos suponer: depende de cada
        // cuánto sube ese cacharro, y asumir 60 para todos hacía que un nodo que
        // sube cada diez minutos registrara la sexta parte de la energía real,
        // en silencio. Se resuelve por elemento más abajo.
        $duration = isset($energy['duration']) ? (int) $energy['duration'] : null;

        // **Cuándo se tomó la muestra**, si el aparato lleva reloj. Sustituye a
        // la hora de llegada, que es lo que se usaba siempre: tras un corte de
        // red, un reintento guardaba media hora de lecturas todas con la hora
        // del reintento. Los que no llevan reloj no mandan nada y no cambia.
        $readAt = isset($energy['read_at'])
            ? Carbon::parse((string) $energy['read_at'])->utc()
            : null;

        return DB::transaction(function () use ($device, $energy, $duration, $readAt) {
            /** @var list<HardwareEnergyReading> $readings */
            $readings = [];
            /** @var list<string> $warnings */
            $warnings = [];

            // 1. GENERADOR
            if (isset($energy['generator']) && is_array($energy['generator']) && $energy['generator'] !== []) {
                $genData = $energy['generator'];
                [$element, $aviso] = $this->resolveTelemetryElement(
                    $device,
                    HardwareEnergy::ROLE_GENERATOR,
                    0,
                    'Generador'
                );

                if ($aviso !== null) {
                    $warnings[] = $aviso;
                }

                if ($element !== null) {
                    $intervalo = $duration ?? $element->defaultIntervalSeconds();
                    $amperage = isset($genData['amperage']) ? (float) $genData['amperage'] : null;
                    $measure = isset($genData['voltage']) ? (float) $genData['voltage'] : null;
                    [$voltage, $voltageSource] = $element->resolveVoltage($measure);

                    ['power' => $power, 'energy_wh' => $intervalWh, 'energy_ah' => $intervalAh] = $element->deriveMagnitudes(
                        $voltage,
                        $amperage,
                        isset($genData['power']) ? (float) $genData['power'] : null,
                        isset($genData['energy_wh']) ? (float) $genData['energy_wh'] : null,
                        isset($genData['energy_ah']) ? (float) $genData['energy_ah'] : null,
                        $intervalo
                    );

                    $energySource = isset($genData['energy_wh']) ? 'device' : 'derived';

                    $reading = new HardwareEnergyReading([
                        'hardware_device_id' => $device->id,
                        'hardware_energy_id' => $element->id,
                        'voltage' => $voltage,
                        'amperage' => $amperage,
                        'power' => $power,
                        'delta_seconds' => $intervalo,
                        'energy_wh' => $intervalWh,
                        'energy_ah' => $intervalAh,
                        'energy_source' => $energySource,
                        'voltage_source' => $voltageSource,
                        'temperature' => isset($genData['temperature']) ? (float) $genData['temperature'] : null,
                        'fan' => isset($genData['fan']) ? (int) $genData['fan'] : null,
                        'charging_status' => isset($genData['charging_status']) ? (int) $genData['charging_status'] : null,
                        'charging_status_label' => $genData['charging_status_label'] ?? null,
                        'light_status' => isset($genData['light_status']) ? (bool) $genData['light_status'] : null,
                        'light_brightness' => isset($genData['light_brightness']) ? (int) $genData['light_brightness'] : null,
                    ]);

                    if ($amperage !== null && $amperage < 0) {
                        $reading->markSuspicious('corriente negativa');
                        $warnings[] = "Generador: corriente negativa ({$amperage} A).";
                    }

                    if ($voltage === null) {
                        $reading->markSuspicious('sin tensión: ni medida ni nominal');
                        $warnings[] = 'Generador: sin tensión ni medida ni nominal.';
                    } elseif ($measure !== null && ! $element->voltageIsPlausible($measure)) {
                        // Se avisa pero se guarda: la medida rara puede ser el
                        // dato bueno. Sustituirla por la nominal dejaba el
                        // mínimo del día del panel en 24 V todas las noches.
                        $warnings[] = "Generador: {$measure} V se sale del rango configurado del elemento; se guarda igual.";
                    }

                    if ($readAt !== null) {
                        $reading->created_at = $readAt;
                        $reading->updated_at = $readAt;
                    }

                    $reading->save();
                    $readings[] = $reading;

                    if (! $reading->is_suspicious) {
                        $medido = [
                            'voltage' => $voltage,
                            'amperage' => $amperage,
                            'power' => $power,
                            'energy_wh' => $intervalWh,
                            'energy_ah' => $intervalAh,
                            'temperature' => $reading->temperature,
                            'fan' => $reading->fan,
                        ];

                        HardwareEnergyToday::recalculateForElement(
                            $device->id,
                            $element->id,
                            $medido + $this->resumenDeclaradoPorElAparato($genData),
                            $readAt?->format('Y-m-d')
                        );

                        HardwareEnergyHistorical::accumulateForElement(
                            $device->id,
                            $element->id,
                            $medido + $this->acumuladoDeclaradoPorElAparato($genData)
                        );
                    }
                }
            }

            // 2. BATERÍA
            if (isset($energy['battery']) && is_array($energy['battery']) && $energy['battery'] !== []) {
                $batData = $energy['battery'];
                [$element, $aviso] = $this->resolveTelemetryElement(
                    $device,
                    HardwareEnergy::ROLE_BATTERY,
                    0,
                    'Batería'
                );

                if ($aviso !== null) {
                    $warnings[] = $aviso;
                }

                if ($element !== null) {
                    $intervalo = $duration ?? $element->defaultIntervalSeconds();
                    $measure = isset($batData['voltage']) ? (float) $batData['voltage'] : null;
                    [$voltage, $voltageSource] = $element->resolveVoltage($measure);
                    $amperage = isset($batData['amperage']) ? (float) $batData['amperage'] : null;
                    ['power' => $power, 'energy_wh' => $intervalWh, 'energy_ah' => $intervalAh] = $element->deriveMagnitudes(
                        $voltage,
                        $amperage,
                        isset($batData['power']) ? (float) $batData['power'] : null,
                        isset($batData['energy_wh']) ? (float) $batData['energy_wh'] : null,
                        isset($batData['energy_ah']) ? (float) $batData['energy_ah'] : null,
                        $intervalo
                    );

                    $soc = isset($batData['soc']) ? (int) $batData['soc'] : (isset($batData['battery_percentage']) ? (int) $batData['battery_percentage'] : null);

                    // Fallback de cálculo de SOC si sólo viene tensión y no porcentaje
                    if ($soc === null && $voltage !== null && $element->voltage_min !== null && $element->voltage_max !== null && $element->voltage_max > $element->voltage_min) {
                        $calculatedSoc = (($voltage - $element->voltage_min) / ($element->voltage_max - $element->voltage_min)) * 100.0;
                        $soc = (int) round(max(0, min(100, $calculatedSoc)));
                    }

                    $reading = new HardwareEnergyReading([
                        'hardware_device_id' => $device->id,
                        'hardware_energy_id' => $element->id,
                        'voltage' => $voltage,
                        'amperage' => $amperage,
                        'power' => $power,
                        'delta_seconds' => $intervalo,
                        'energy_wh' => $intervalWh,
                        'energy_ah' => $intervalAh,
                        'energy_source' => isset($batData['energy_wh']) ? 'device' : 'derived',
                        'voltage_source' => $voltageSource,
                        'battery_voltage' => $voltage,
                        'battery_percentage' => $soc,
                        'temperature' => isset($batData['temperature']) ? (float) $batData['temperature'] : null,
                        'charging_status' => isset($batData['charging_status']) ? (int) $batData['charging_status'] : null,
                        'charging_status_label' => $batData['charging_status_label'] ?? null,
                    ]);

                    if ($voltage === null) {
                        $reading->markSuspicious('sin tensión de batería');
                        $warnings[] = 'Batería: sin tensión ni medida ni nominal.';
                    } elseif ($measure !== null && ! $element->voltageIsPlausible($measure)) {
                        // En una batería este rango es el que calibra el
                        // porcentaje de carga, así que salirse de él es
                        // información —una sobredescarga—, no un error.
                        $warnings[] = "Batería: {$measure} V se sale del rango de calibración del elemento; se guarda igual.";
                    }

                    if ($readAt !== null) {
                        $reading->created_at = $readAt;
                        $reading->updated_at = $readAt;
                    }

                    $reading->save();
                    $readings[] = $reading;

                    if (! $reading->is_suspicious) {
                        $medido = [
                            'voltage' => $voltage,
                            'amperage' => $amperage,
                            'power' => $power,
                            'energy_wh' => $intervalWh,
                            'energy_ah' => $intervalAh,
                            'battery_voltage' => $voltage,
                            'battery_percentage' => $soc,
                            'temperature' => $reading->temperature,
                        ];

                        HardwareEnergyToday::recalculateForElement(
                            $device->id,
                            $element->id,
                            $medido + $this->resumenDeclaradoPorElAparato($batData),
                            $readAt?->format('Y-m-d')
                        );

                        HardwareEnergyHistorical::accumulateForElement(
                            $device->id,
                            $element->id,
                            $medido + $this->acumuladoDeclaradoPorElAparato($batData)
                        );
                    }
                }
            }

            // 3. CONSUMOS (LOADS)
            if (isset($energy['loads']) && is_array($energy['loads'])) {
                foreach ($energy['loads'] as $loadData) {
                    if (! is_array($loadData)) {
                        continue;
                    }

                    $channel = (int) ($loadData['channel'] ?? $loadData['sensor_position'] ?? 0);
                    [$element, $aviso] = $this->resolveTelemetryElement(
                        $device,
                        HardwareEnergy::ROLE_LOAD,
                        $channel,
                        "Consumo canal {$channel}"
                    );

                    if ($aviso !== null) {
                        $warnings[] = $aviso;
                    }

                    if ($element === null) {
                        continue;
                    }

                    $intervalo = $duration ?? $element->defaultIntervalSeconds();
                    $amperage = isset($loadData['amperage']) ? (float) $loadData['amperage'] : null;
                    $measure = isset($loadData['voltage']) ? (float) $loadData['voltage'] : null;
                    [$voltage, $voltageSource] = $element->resolveVoltage($measure);

                    ['power' => $power, 'energy_wh' => $intervalWh, 'energy_ah' => $intervalAh] = $element->deriveMagnitudes(
                        $voltage,
                        $amperage,
                        isset($loadData['power']) ? (float) $loadData['power'] : null,
                        isset($loadData['energy_wh']) ? (float) $loadData['energy_wh'] : null,
                        isset($loadData['energy_ah']) ? (float) $loadData['energy_ah'] : null,
                        $intervalo
                    );

                    $reading = new HardwareEnergyReading([
                        'hardware_device_id' => $device->id,
                        'hardware_energy_id' => $element->id,
                        'voltage' => $voltage,
                        'amperage' => $amperage,
                        'power' => $power,
                        'delta_seconds' => $intervalo,
                        'energy_wh' => $intervalWh,
                        'energy_ah' => $intervalAh,
                        'energy_source' => isset($loadData['energy_wh']) ? 'device' : 'derived',
                        'voltage_source' => $voltageSource,
                        'temperature' => isset($loadData['temperature']) ? (float) $loadData['temperature'] : null,
                        'fan' => isset($loadData['fan']) ? (int) $loadData['fan'] : null,
                    ]);

                    if ($amperage !== null && $amperage < 0) {
                        $reading->markSuspicious('corriente negativa');
                        $warnings[] = "Consumo canal {$channel}: corriente negativa ({$amperage} A).";
                    }

                    if ($voltage === null) {
                        $reading->markSuspicious('sin tensión: ni medida ni nominal');
                        $warnings[] = "Consumo canal {$channel}: sin tensión ni medida ni nominal.";
                    } elseif ($measure !== null && ! $element->voltageIsPlausible($measure)) {
                        $warnings[] = "Consumo canal {$channel}: {$measure} V se sale del rango configurado del elemento; se guarda igual.";
                    }

                    if ($readAt !== null) {
                        $reading->created_at = $readAt;
                        $reading->updated_at = $readAt;
                    }

                    $reading->save();
                    $readings[] = $reading;

                    if (! $reading->is_suspicious) {
                        $medido = [
                            'voltage' => $voltage,
                            'amperage' => $amperage,
                            'power' => $power,
                            'energy_wh' => $intervalWh,
                            'energy_ah' => $intervalAh,
                            'temperature' => $reading->temperature,
                            'fan' => $reading->fan,
                        ];

                        HardwareEnergyToday::recalculateForElement(
                            $device->id,
                            $element->id,
                            $medido + $this->resumenDeclaradoPorElAparato($loadData),
                            $readAt?->format('Y-m-d')
                        );

                        HardwareEnergyHistorical::accumulateForElement(
                            $device->id,
                            $element->id,
                            $medido + $this->acumuladoDeclaradoPorElAparato($loadData)
                        );
                    }
                }
            }

            return ['readings' => $readings, 'warnings' => $warnings];
        });
    }

    /**
     * Los acumuladores y extremos **del día** que declara el aparato.
     *
     * Todo lo que un aparato manda se guarda tal cual y manda sobre lo que
     * calculamos nosotros; lo que no manda, se calcula. El Renogy Rover lleva
     * sus propios contadores de vatios-hora y amperios-hora del día, y también
     * los máximos reales del día —que un muestreo cada minuto se pierde—, así
     * que sus valores son mejores que cualquier integración nuestra.
     *
     * Los nombres son los mismos en los tres bloques a propósito: un generador,
     * una batería y un consumo declaran lo mismo de distinta manera, y tener
     * `today_energy_wh` sólo en unos sitios fue justo lo que hizo que los
     * amperios-hora de descarga del Rover se tiraran a la basura con un 201.
     *
     * @param  array<string, mixed>  $bloque
     * @return array<string, float|null>
     */
    private function resumenDeclaradoPorElAparato(array $bloque): array
    {
        $numero = static fn (string $clave): ?float => isset($bloque[$clave]) ? (float) $bloque[$clave] : null;

        return [
            'today_energy_wh' => $numero('today_energy_wh'),
            'today_energy_ah' => $numero('today_energy_ah'),
            'today_voltage_min' => $numero('today_voltage_min'),
            'today_voltage_max' => $numero('today_voltage_max'),
            'today_amperage_max' => $numero('today_amperage_max'),
            'today_power_max' => $numero('today_power_max'),
        ];
    }

    /**
     * Los acumuladores **de por vida** que declara el aparato.
     *
     * `total_operating_days` y `days_operating` son el mismo dato con dos
     * nombres: el primero es como lo llama el firmware del Rover y el segundo
     * como lo llamaba el contrato de la V1.
     *
     * @param  array<string, mixed>  $bloque
     * @return array<string, float|int|null>
     */
    private function acumuladoDeclaradoPorElAparato(array $bloque): array
    {
        $numero = static fn (string $clave): ?float => isset($bloque[$clave]) ? (float) $bloque[$clave] : null;

        return [
            'historical_energy_wh' => $numero('historical_energy_wh'),
            'historical_energy_ah' => $numero('historical_energy_ah'),
            'battery_full_charges' => isset($bloque['battery_full_charges']) ? (int) $bloque['battery_full_charges'] : null,
            'battery_over_discharges' => isset($bloque['battery_over_discharges']) ? (int) $bloque['battery_over_discharges'] : null,
            'days_operating' => isset($bloque['total_operating_days'])
                ? (int) $bloque['total_operating_days']
                : (isset($bloque['days_operating']) ? (int) $bloque['days_operating'] : null),
        ];
    }

    /**
     * El elemento de un rol y canal al que va una lectura de telemetría, o
     * `null` con el motivo por el que no se puede guardar.
     *
     * Tres cosas que antes se hacían mal:
     *
     * 1. **Se prefiere el activo.** El índice único incluye
     *    `hardware_device_monitorized_id`, así que un mismo aparato puede tener
     *    dos elementos del mismo rol midiendo cosas distintas. Coger «el
     *    primero» descartaba la lectura si ese primero estaba desactivado,
     *    aunque hubiera otro perfectamente activo.
     * 2. **Un elemento borrado no se resucita ni se esquiva creando otro.** La
     *    colección llega con `withTrashed()`, así que aquí se ve; crear otro
     *    igual reventaba contra el índice único y dejaba al dispositivo sin
     *    poder subir nada. Se avisa y se descarta la lectura: restaurarlo es
     *    una decisión de quien lo borró, no de una petición HTTP.
     * 3. **Al crear no se inventa una tensión nominal.** Tomarla de la primera
     *    lectura que llegase dejaba fijada como referencia permanente lo que
     *    igual era un pico de arranque, y a partir de ahí
     *    {@see HardwareEnergy::voltageIsPlausible()} marcaba como sospechosas
     *    las lecturas buenas. Sin nominal, una lectura que traiga tensión se
     *    guarda igual; la que no la traiga avisa de que hay que configurarlo.
     *
     * @param  string  $etiqueta  Cómo se nombra este bloque en los avisos.
     * @return array{0: HardwareEnergy|null, 1: string|null}
     */
    private function resolveTelemetryElement(
        HardwareDevice $device,
        string $role,
        int $channel,
        string $etiqueta
    ): array {
        $candidatos = $device->hardwareEnergy
            ->where('role', $role)
            ->when(
                $role === HardwareEnergy::ROLE_LOAD,
                static fn (Collection $c) => $c->where('sensor_position', $channel)
            );

        $element = $candidatos->first(
            static fn (HardwareEnergy $e) => $e->is_active && $e->deleted_at === null
        );

        if ($element !== null) {
            return [$element, null];
        }

        $borrado = $candidatos->first(static fn (HardwareEnergy $e) => $e->deleted_at !== null);

        if ($borrado !== null) {
            return [null, "{$etiqueta}: el elemento (#{$borrado->id}) está borrado; ".
                'restáuralo si quieres volver a guardar sus lecturas.'];
        }

        if ($candidatos->isNotEmpty()) {
            return [null, "{$etiqueta}: el elemento está desactivado; lectura ignorada."];
        }

        $element = HardwareEnergy::create([
            'hardware_device_id' => $device->id,
            'hardware_device_monitorized_id' => $device->id,
            'role' => $role,
            'sensor_position' => $channel,
            'is_active' => true,
        ]);

        $device->hardwareEnergy->push($element);

        return [$element, "{$etiqueta}: se ha dado de alta el elemento #{$element->id} automáticamente; ".
            'rellena su tensión nominal y su fuente para que las cuentas salgan bien.'];
    }
}
