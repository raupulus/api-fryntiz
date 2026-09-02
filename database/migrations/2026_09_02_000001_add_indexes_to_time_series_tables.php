<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Índices para las tablas de serie temporal (auditoría AR-R01).
 *
 * La API consulta estas tablas SIEMPRE igual: filtra por el dispositivo o la
 * planta, acota por `created_at`, ordena por `created_at` descendente y pagina
 * —y paginar añade encima un `count(*)` completo—. Y todas ellas tenían un
 * único índice: la clave primaria.
 *
 * Sobre los millones de filas que acumula una estación reportando cada minuto,
 * eso es un recorrido secuencial entero más una ordenación completa **por cada
 * petición**, en rutas que hasta la misma auditoría no tenían ni límite de
 * peticiones. El módulo de energía (`hardware_power_*`) sí estaba bien
 * indexado desde el principio; el contraste deja claro que en los sensores
 * simplemente se olvidó.
 *
 * ── Por qué el orden de las columnas es ése ──────────────────────────────────
 *
 * Un índice compuesto `(dispositivo, created_at)` sirve para las tres cosas a
 * la vez: acota por dispositivo, acota el rango de fechas dentro de ese
 * dispositivo y devuelve las filas ya ordenadas por fecha, así que PostgreSQL
 * se ahorra el `sort`. Al revés —`(created_at, dispositivo)`— no serviría para
 * lo primero, que es lo que más filas descarta.
 *
 * ── Por qué CONCURRENTLY ─────────────────────────────────────────────────────
 *
 * Un `CREATE INDEX` normal bloquea la tabla para escritura mientras se
 * construye. En estas tablas eso significa parar la ingesta de los cacharros
 * durante el despliegue, y un microcontrolador que recibe un error no reintenta
 * indefinidamente: pierde la lectura.
 *
 * `CONCURRENTLY` no bloquea, pero **no puede ejecutarse dentro de una
 * transacción**, y PostgreSQL sí soporta DDL transaccional, así que Laravel
 * envuelve la migración por defecto. De ahí `$withinTransaction = false`.
 *
 * El precio de no ir en transacción es que un fallo a mitad deja los índices ya
 * creados. Por eso todo va con `IF NOT EXISTS`: la migración se puede relanzar
 * sin tener que limpiar nada a mano.
 */
return new class extends Migration
{
    /**
     * `CREATE INDEX CONCURRENTLY` no admite transacción.
     */
    public $withinTransaction = false;

    /**
     * Tabla => columnas del índice.
     *
     * Las trece de sensores comparten forma porque comparten consulta: la de
     * `SensorReadingController::index()`.
     *
     * @var array<string, list<string>>
     */
    private const INDEXES = [
        // ── Estación meteorológica ──────────────────────────────────────────
        // GET /weather-stations/{station}/{sensor}
        'meteorology_temperature' => ['hardware_device_id', 'created_at'],
        'meteorology_humidity' => ['hardware_device_id', 'created_at'],
        'meteorology_pressure' => ['hardware_device_id', 'created_at'],
        'meteorology_light' => ['hardware_device_id', 'created_at'],
        'meteorology_rain' => ['hardware_device_id', 'created_at'],
        'meteorology_wind_direction' => ['hardware_device_id', 'created_at'],
        'meteorology_lightning' => ['hardware_device_id', 'created_at'],
        'meteorology_uv_index' => ['hardware_device_id', 'created_at'],
        'meteorology_uva' => ['hardware_device_id', 'created_at'],
        'meteorology_uvb' => ['hardware_device_id', 'created_at'],
        'meteorology_eco2' => ['hardware_device_id', 'created_at'],
        'meteorology_tvoc' => ['hardware_device_id', 'created_at'],
        'meteorology_air_quality' => ['hardware_device_id', 'created_at'],
        'meteorology_winter' => ['hardware_device_id', 'created_at'],

        // Resúmenes: se leen por dispositivo y fecha igual que las lecturas.
        'meteorology_resume_today' => ['hardware_device_id', 'created_at'],
        'meteorology_resume_historical' => ['hardware_device_id', 'created_at'],

        // ── Plantas ─────────────────────────────────────────────────────────
        // GET /smartplant/plants/{plant}/readings — la lectura cuelga de su
        // planta, y `smartplant_registers` no tiene `user_id` (N288), así que
        // `plant_id` es por donde entra todo.
        'smartplant_registers' => ['plant_id', 'created_at'],

        // ── KeyCounter ──────────────────────────────────────────────────────
        // GET /keycounter/{keyboard,mouse}-sessions filtran por usuario y
        // ordenan por `start_at`, que es el orden por defecto del endpoint.
        'keycounter_keyboard' => ['user_id', 'start_at'],
        'keycounter_mouse' => ['user_id', 'start_at'],

        // ── AirFlight ───────────────────────────────────────────────────────
        // `latestRoute` busca la última ruta de cada avión.
        'airflight_routes' => ['airplane_id', 'created_at'],
    ];

    public function up(): void
    {
        foreach (self::INDEXES as $tabla => $columnas) {
            DB::statement(sprintf(
                'CREATE INDEX CONCURRENTLY IF NOT EXISTS %s ON %s (%s)',
                $this->indexName($tabla, $columnas),
                $tabla,
                implode(', ', $columnas),
            ));
        }
    }

    public function down(): void
    {
        foreach (self::INDEXES as $tabla => $columnas) {
            // También CONCURRENTLY: un DROP normal coge un lock exclusivo.
            DB::statement(sprintf(
                'DROP INDEX CONCURRENTLY IF EXISTS %s',
                $this->indexName($tabla, $columnas),
            ));
        }
    }

    /**
     * Nombre del índice con la convención de Laravel: tabla_columnas_index.
     *
     * PostgreSQL corta los identificadores a 63 caracteres, y el más largo de
     * aquí —`meteorology_resume_historical_hardware_device_id_created_at_index`,
     * 66— se pasaría. Si se pasa, se acorta dejando la tabla completa y las
     * iniciales de las columnas, que sigue siendo único dentro de la tabla.
     *
     * @param  list<string>  $columnas
     */
    private function indexName(string $tabla, array $columnas): string
    {
        $nombre = $tabla.'_'.implode('_', $columnas).'_index';

        if (mb_strlen($nombre) <= 63) {
            return $nombre;
        }

        $iniciales = implode('_', array_map(
            static fn (string $columna): string => implode('', array_map(
                static fn (string $parte): string => $parte[0],
                explode('_', $columna)
            )),
            $columnas
        ));

        return $tabla.'_'.$iniciales.'_index';
    }
};
