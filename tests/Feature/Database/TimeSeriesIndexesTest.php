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
    public static function tablasProvider(): array
    {
        $sensores = [
            'meteorology_temperature', 'meteorology_humidity', 'meteorology_pressure',
            'meteorology_light', 'meteorology_rain', 'meteorology_wind_direction',
            'meteorology_lightning', 'meteorology_uv_index', 'meteorology_uva',
            'meteorology_uvb', 'meteorology_eco2', 'meteorology_tvoc',
            'meteorology_air_quality', 'meteorology_winter',
            'meteorology_resume_today', 'meteorology_resume_historical',
        ];

        $casos = [];

        foreach ($sensores as $tabla) {
            $casos[$tabla] = [$tabla, 'hardware_device_id, created_at'];
        }

        $casos['smartplant_registers'] = ['smartplant_registers', 'plant_id, created_at'];
        $casos['keycounter_keyboard'] = ['keycounter_keyboard', 'user_id, start_at'];
        $casos['keycounter_mouse'] = ['keycounter_mouse', 'user_id, start_at'];
        $casos['airflight_routes'] = ['airflight_routes', 'airplane_id, created_at'];

        return $casos;
    }

    #[Test]
    #[DataProvider('tablasProvider')]
    public function la_tabla_tiene_su_indice_compuesto(string $tabla, string $columnas): void
    {
        $definiciones = collect(DB::select(
            'select indexdef from pg_indexes where schemaname = current_schema() and tablename = ?',
            [$tabla]
        ))->pluck('indexdef');

        $this->assertTrue(
            $definiciones->contains(fn (string $def): bool => str_contains($def, "({$columnas})")),
            "La tabla «{$tabla}» no tiene índice por ({$columnas}), que es justo por donde la ".
            "consulta la API.\nÍndices que tiene:\n  ".$definiciones->implode("\n  ")
        );
    }

    #[Test]
    public function el_orden_de_las_columnas_importa(): void
    {
        // Un índice `(created_at, hardware_device_id)` no serviría para lo que
        // más filas descarta —acotar por dispositivo— y encima no daría el
        // orden gratis. Se comprueba sobre la tabla más consultada.
        $definiciones = collect(DB::select(
            "select indexdef from pg_indexes where schemaname = current_schema() and tablename = 'meteorology_temperature'"
        ))->pluck('indexdef');

        $this->assertTrue($definiciones->contains(fn (string $d): bool => str_contains($d, '(hardware_device_id, created_at)')));
        $this->assertFalse($definiciones->contains(fn (string $d): bool => str_contains($d, '(created_at, hardware_device_id)')));
    }
}
