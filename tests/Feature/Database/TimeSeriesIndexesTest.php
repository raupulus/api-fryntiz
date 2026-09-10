<?php

declare(strict_types=1);

namespace Tests\Feature\Database;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Las tablas de serie temporal están indexadas por donde las consulta la API.
 *
 * Estas tablas tenían un único índice —la clave primaria— mientras la API
 * filtraba por dispositivo, acotaba por `created_at`, ordenaba por `created_at`
 * y paginaba, que además añade un `count(*)` completo (auditoría AR-R01).
 *
 * Sobre los millones de filas que acumula una estación reportando cada minuto,
 * cada petición era un recorrido secuencial entero más una ordenación completa.
 *
 * Este test no comprueba rendimiento —eso no se mide en una suite—, comprueba
 * que el índice **existe**: un `dropIndex` de más en una migración futura, o una
 * tabla de sensor nueva creada copiando una vieja, se ven aquí.
 */
class TimeSeriesIndexesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Tabla => columnas que debe llevar el índice, en ese orden.
     *
     * @return array<string, array{0: string, 1: string}>
     */
    public static function tablesProvider(): array
    {
        $sensors = [
            'meteorology_temperature', 'meteorology_humidity', 'meteorology_pressure',
            'meteorology_light', 'meteorology_rain', 'meteorology_wind_direction',
            'meteorology_lightning', 'meteorology_eco2', 'meteorology_tvoc',
            'meteorology_air_quality', 'meteorology_winter',
            'meteorology_resume_today', 'meteorology_resume_historical',
        ];

        $cases = [];

        foreach ($sensors as $table) {
            $cases[$table] = [$table, 'hardware_device_id, created_at'];
        }

        $cases['smartplant_registers'] = ['smartplant_registers', 'plant_id, created_at'];
        $cases['keycounter_keyboard'] = ['keycounter_keyboard', 'user_id, start_at'];
        $cases['keycounter_mouse'] = ['keycounter_mouse', 'user_id, start_at'];
        $cases['airflight_routes'] = ['airflight_routes', 'airplane_id, created_at'];

        return $cases;
    }

    #[Test]
    #[DataProvider('tablesProvider')]
    public function the_table_has_its_composite_index(string $table, string $columns): void
    {
        $definitions = collect(DB::select(
            'select indexdef from pg_indexes where schemaname = current_schema() and tablename = ?',
            [$table]
        ))->pluck('indexdef');

        $this->assertTrue(
            $definitions->contains(fn (string $def): bool => str_contains($def, "({$columns})")),
            "La tabla «{$table}» no tiene índice por ({$columns}), que es justo por donde la ".
            "consulta la API.\nÍndices que tiene:\n  ".$definitions->implode("\n  ")
        );
    }

    #[Test]
    public function the_column_order_matters(): void
    {
        // Un índice `(created_at, hardware_device_id)` no serviría para lo que
        // más filas descarta —acotar por dispositivo— y encima no daría el
        // orden gratis. Se comprueba sobre la tabla más consultada.
        $definitions = collect(DB::select(
            "select indexdef from pg_indexes where schemaname = current_schema() and tablename = 'meteorology_temperature'"
        ))->pluck('indexdef');

        $this->assertTrue($definitions->contains(fn (string $d): bool => str_contains($d, '(hardware_device_id, created_at)')));
        $this->assertFalse($definitions->contains(fn (string $d): bool => str_contains($d, '(created_at, hardware_device_id)')));
    }
}
