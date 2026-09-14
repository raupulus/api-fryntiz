<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Repara los días de energía del Renogy Rover (aparato 6) guardados mal entre el
 * 7 y el 14 de septiembre de 2026.
 *
 * ## Qué estaba mal
 *
 * 1. **Consumo (#7) del 7 al 13.** El sistema viejo guardaba como total del día
 *    el contador del Rover, que se pone a cero hacia las 19:14 UTC y no a
 *    medianoche: cada día quedaba con lo gastado de 19:14 a 24:00 (120 Wh en vez
 *    de ~620). El traspaso lo copió tal cual.
 * 2. **Panel (#4), batería (#11) y consumo, del 13 en adelante.** El firmware
 *    mandaba `today_*` con esos mismos contadores y la API los sustituía en el
 *    día; además, el día quedaba marcado como «del aparato» y dejaba de sumar lo
 *    que llegaba después. El 13 el panel acabó con 1081 Ah.
 * 3. **Lecturas del panel y la batería desde el 13 a las 12:00.** Los Ah del
 *    panel iban a su tensión real (~34 V) y los Wh de la batería sumaban la
 *    descarga como carga. Ahora la API los calcula con la tensión nominal
 *    (`HardwareEnergy::deriveMagnitudes()`); aquí se rehacen los ya guardados.
 *
 * ## Cómo se rehace
 *
 * - **Consumo:** cada día desde el 7 es la suma de sus lecturas. Las del sistema
 *   viejo no traen energía, así que se integra su potencia (y su corriente) con
 *   el tiempo desde la lectura anterior, con un tope de 15 minutos para no
 *   inventar energía en un corte.
 * - **Panel y batería, día 13:** lo que marcaba el contador del Rover a las
 *   11:58 en las tablas viejas (hasta ahí estaba bien: esos contadores se ponen
 *   a cero de madrugada) más lo que sumaron las lecturas nuevas. **Del 14 en
 *   adelante:** la suma de sus lecturas.
 * - Cada magnitud que el Rover no mide sale de la otra con la tensión nominal:
 *   panel `Ah = Wh ÷ V`, batería `Wh = Ah × V`.
 * - **Acumulado de por vida:** el del panel recalcula sus Ah de sus Wh; el de la
 *   batería y el del consumo pasan a ser la suma de sus días, igual que hizo el
 *   traspaso con la batería.
 *
 * No hace nada si esos elementos no existen o no son los del Rover, así que en
 * una instalación nueva o en los tests pasa de largo. No tiene vuelta atrás.
 */
return new class extends Migration
{
    private const DISPOSITIVO = 6;

    private const GENERADOR = 4;

    private const CONSUMO = 7;

    private const BATERIA = 11;

    /** Última lectura del sistema viejo: 11:58:57. Primera del nuevo: 14:25. */
    private const CORTE = '2026-09-13 12:00:00';

    private const DIA_DEL_CAMBIO = '2026-09-13';

    /** El 6 aún cuadra con sus lecturas; desde el 7 no. */
    private const PRIMER_DIA_DE_CONSUMO_MAL = '2026-09-07';

    /** Tope del hueco entre dos lecturas viejas al integrar su potencia. */
    private const HUECO_MAXIMO_SEGUNDOS = 900;

    public function up(): void
    {
        $elementos = DB::table('hardware_energy')
            ->whereIn('id', [self::GENERADOR, self::CONSUMO, self::BATERIA])
            ->where('hardware_device_id', self::DISPOSITIVO)
            ->get(['id', 'role', 'nominal_voltage'])
            ->keyBy('id');

        if ($elementos->get(self::GENERADOR)?->role !== 'generator'
            || $elementos->get(self::CONSUMO)?->role !== 'load'
            || $elementos->get(self::BATERIA)?->role !== 'battery') {
            return;
        }

        $tensionPanel = (float) $elementos->get(self::GENERADOR)->nominal_voltage;
        $tensionBanco = (float) $elementos->get(self::BATERIA)->nominal_voltage;

        if ($tensionPanel <= 0.0 || $tensionBanco <= 0.0) {
            return;
        }

        DB::transaction(function () use ($tensionPanel, $tensionBanco): void {
            $this->rehaceLecturas($tensionPanel, $tensionBanco);
            $this->rehaceDiasDeConsumo();
            $this->rehaceDias(self::GENERADOR, 'energy_wh', 'energy_ah', $tensionPanel, $this->baseDelPanel());
            $this->rehaceDias(self::BATERIA, 'energy_ah', 'energy_wh', $tensionBanco, $this->baseDeLaBateria());
            $this->rehaceAcumulados($tensionPanel, $tensionBanco);
        });
    }

    public function down(): void
    {
        // Reparación de datos: no hay estado anterior que merezca volver.
    }

    private function rehaceLecturas(float $tensionPanel, float $tensionBanco): void
    {
        DB::update(
            'UPDATE hardware_energy_readings SET energy_ah = ROUND(energy_wh / ?, 4)
             WHERE hardware_energy_id = ? AND created_at >= ? AND energy_wh IS NOT NULL',
            [$tensionPanel, self::GENERADOR, self::CORTE]
        );

        DB::update(
            'UPDATE hardware_energy_readings SET energy_wh = ROUND(energy_ah * ?, 4)
             WHERE hardware_energy_id = ? AND created_at >= ? AND energy_ah IS NOT NULL',
            [$tensionBanco, self::BATERIA, self::CORTE]
        );
    }

    private function rehaceDiasDeConsumo(): void
    {
        DB::update(
            "UPDATE hardware_energy_today t
             SET energy_wh = ROUND(s.wh, 4), energy_ah = ROUND(s.ah, 4),
                 energy_wh_source = 'derived', energy_ah_source = 'derived', updated_at = NOW()
             FROM (
                 SELECT d, COALESCE(SUM(wh), 0) AS wh, COALESCE(SUM(ah), 0) AS ah
                 FROM (
                     SELECT created_at::date AS d, is_suspicious,
                            COALESCE(energy_wh, power * hueco / 3600) AS wh,
                            COALESCE(energy_ah, amperage * hueco / 3600) AS ah
                     FROM (
                         -- LEAST ignora los NULL: sin el CASE, la primera lectura
                         -- —que no tiene anterior— contaría el tope entero.
                         SELECT *, CASE WHEN segundos IS NULL THEN NULL ELSE LEAST(segundos, ?) END AS hueco
                         FROM (
                             SELECT *, EXTRACT(EPOCH FROM created_at - LAG(created_at) OVER (ORDER BY created_at, id)) AS segundos
                             FROM hardware_energy_readings
                             WHERE hardware_energy_id = ? AND created_at >= (?::date - 1)
                         ) con_segundos
                     ) con_hueco
                 ) lecturas
                 WHERE NOT is_suspicious AND d >= ?::date
                 GROUP BY d
             ) s
             WHERE t.hardware_energy_id = ? AND t.date = s.d",
            [self::HUECO_MAXIMO_SEGUNDOS, self::CONSUMO, self::PRIMER_DIA_DE_CONSUMO_MAL, self::PRIMER_DIA_DE_CONSUMO_MAL, self::CONSUMO]
        );
    }

    /**
     * Días desde el del cambio: la magnitud que mide el Rover es la base de las
     * tablas viejas (sólo el día 13) más la suma de las lecturas nuevas; la otra
     * sale con la tensión nominal.
     */
    private function rehaceDias(int $elemento, string $medida, string $calculada, float $tension, ?float $baseDelDiaDelCambio): void
    {
        $dias = DB::table('hardware_energy_today')
            ->where('hardware_energy_id', $elemento)
            ->where('date', '>=', self::DIA_DEL_CAMBIO)
            ->pluck('date');

        foreach ($dias as $dia) {
            $dia = substr((string) $dia, 0, 10);
            $esElDelCambio = $dia === self::DIA_DEL_CAMBIO;

            // Sin la base de las tablas viejas el día 13 no se puede rehacer
            // entero: mejor dejarlo como está que quitarle media mañana.
            if ($esElDelCambio && $baseDelDiaDelCambio === null) {
                continue;
            }

            $suma = (float) DB::table('hardware_energy_readings')
                ->where('hardware_energy_id', $elemento)
                ->where('is_suspicious', false)
                ->where('created_at', '>=', $esElDelCambio ? self::CORTE : $dia.' 00:00:00')
                ->where('created_at', '<', date('Y-m-d', strtotime($dia.' +1 day')).' 00:00:00')
                ->sum($medida);

            $valor = ($esElDelCambio ? $baseDelDiaDelCambio : 0.0) + $suma;
            $otra = $calculada === 'energy_ah' ? $valor / $tension : $valor * $tension;

            DB::table('hardware_energy_today')
                ->where('hardware_energy_id', $elemento)
                ->where('date', $dia)
                ->update([
                    $medida => round($valor, 4),
                    $calculada => round($otra, 4),
                    'energy_wh_source' => 'derived',
                    'energy_ah_source' => 'derived',
                    'updated_at' => now(),
                ]);
        }
    }

    /** Wh que marcaba el contador de generación del Rover el 13 a las 11:58. */
    private function baseDelPanel(): ?float
    {
        if (! Schema::hasTable('hardware_power_generators_today')) {
            return null;
        }

        $valor = DB::table('hardware_power_generators_today')
            ->where('hardware_device_id', self::DISPOSITIVO)
            ->where('date', self::DIA_DEL_CAMBIO)
            ->orderByDesc('updated_at')
            ->value('energy_wh');

        return $valor === null ? null : (float) $valor;
    }

    /** Ah que marcaba el contador de carga de batería del Rover el 13 a las 11:58. */
    private function baseDeLaBateria(): ?float
    {
        if (! Schema::hasTable('hardware_power_generators_solar')) {
            return null;
        }

        $valor = DB::table('hardware_power_generators_solar')
            ->where('hardware_device_id', self::DISPOSITIVO)
            ->where('created_at', '>=', self::DIA_DEL_CAMBIO.' 00:00:00')
            ->where('created_at', '<', self::CORTE)
            ->whereNotNull('day_charging_amp_hours')
            ->orderByDesc('created_at')
            ->value('day_charging_amp_hours');

        return $valor === null ? null : (float) $valor;
    }

    private function rehaceAcumulados(float $tensionPanel, float $tensionBanco): void
    {
        $unaSesion = static fn (int $elemento): bool => DB::table('hardware_energy_historical')
            ->where('hardware_energy_id', $elemento)
            ->count() === 1;

        $sumaDeDias = static fn (int $elemento, string $magnitud): float => (float) DB::table('hardware_energy_today')
            ->where('hardware_energy_id', $elemento)
            ->sum($magnitud);

        if ($unaSesion(self::GENERADOR)) {
            DB::update(
                "UPDATE hardware_energy_historical
                 SET energy_ah = ROUND(energy_wh / ?, 4), energy_ah_source = 'derived', updated_at = NOW()
                 WHERE hardware_energy_id = ?",
                [$tensionPanel, self::GENERADOR]
            );
        }

        if ($unaSesion(self::BATERIA)) {
            $ah = $sumaDeDias(self::BATERIA, 'energy_ah');

            DB::table('hardware_energy_historical')
                ->where('hardware_energy_id', self::BATERIA)
                ->update([
                    'energy_ah' => round($ah, 4),
                    'energy_wh' => round($ah * $tensionBanco, 4),
                    'energy_wh_source' => 'derived',
                    'updated_at' => now(),
                ]);
        }

        if ($unaSesion(self::CONSUMO)) {
            DB::table('hardware_energy_historical')
                ->where('hardware_energy_id', self::CONSUMO)
                ->update([
                    'energy_wh' => round($sumaDeDias(self::CONSUMO, 'energy_wh'), 4),
                    'energy_ah' => round($sumaDeDias(self::CONSUMO, 'energy_ah'), 4),
                    'updated_at' => now(),
                ]);
        }
    }
};
