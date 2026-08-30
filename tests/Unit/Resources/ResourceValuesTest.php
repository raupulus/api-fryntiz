<?php

declare(strict_types=1);

namespace Tests\Unit\Resources;

use App\Http\Resources\V2\Content\ContentPageResource;
use App\Http\Resources\V2\Hardware\EnergyMonitorResource;
use App\Http\Resources\V2\Hardware\SolarReadingResource;
use App\Http\Resources\V2\KeyCounter\MouseResource;
use App\Http\Resources\V2\PlatformResource;
use App\Http\Resources\V2\SmartPlant\SmartPlantRegisterResource;
use App\Models\Hardware\HardwareDevice;
use App\Models\SmartPlant\SmartPlantPlant;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Api\ApiTestCase;

/**
 * Los Resources que devuelven claves siempre nulas — N280.
 *
 * La suite vieja usa `assertJsonStructure` 35 veces. Esa assertion comprueba que
 * **la clave exista**, no que tenga valor:
 *
 *     assertJsonStructure(['data' => ['id', 'body']])   pasa con   {"id":3,"body":null}
 *
 * Por eso los Resources con campos siempre nulos llevan meses en verde.
 *
 * Aquí no se pasa por HTTP a propósito. Se inserta la fila **a pelo con
 * `DB::table()`** —sin modelo, sin `$fillable`, sin FormRequest— y se le pasa el
 * Resource por encima. Así, si un campo sale nulo, el único responsable posible
 * es el Resource: lee una clave que en la fila no existe (**caso A**).
 */
class ResourceValuesTest extends ApiTestCase
{
    private User $user;

    private HardwareDevice $device;

    private SmartPlantPlant $plant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = $this->createAuthenticatedUser(3);
        $this->device = HardwareDevice::create([
            'user_id' => $this->user->id,
            'name' => 'Cacharro de pruebas',
        ]);
        $this->plant = SmartPlantPlant::create([
            'user_id' => $this->user->id,
            'name' => 'Planta de pruebas',
            'name_scientific' => 'Testum plantus',
            'description' => 'Planta de pruebas',
            'details' => 'Planta de pruebas',
            'start_at' => now(),
        ]);
    }

    /**
     * Resource => [tabla, fila a insertar, claves que hoy salen nulas y por qué]
     *
     * @return array<string,array{0:class-string,1:string,2:array<string,mixed>,3:array<string,string>}>
     */
    public static function resources(): array
    {
        return [
            // Un dato, un nombre: la lectura del controlador solar ya no
            // tiene `pv_*` junto a `energy_*` ni `device_id` junto a
            // `hardware_device_id`. El Resource leía tres claves inexistentes y
            // salían nulas en todas las respuestas (**R-4**).
            'SolarReadingResource' => [
                SolarReadingResource::class,
                'hardware_power_generators_solar',
                [
                    'battery_voltage' => 12.8,
                    'battery_percentage' => 87,
                    'temperature' => 24.5,
                    'load_voltage' => 12.7,
                    'load_current' => 2.4,
                    'load_power' => 31.0,
                    'voltage' => 18.2,
                    'amperage' => 3.4,
                    'power' => 61.9,
                    'total_operating_days' => 412,
                ],
                [],
            ],

            // Ya no envuelve `hardware_energy` (configuración) sino la lectura
            // real de `hardware_power_loads` (**R-3** arreglado).
            'EnergyMonitorResource' => [
                EnergyMonitorResource::class,
                'hardware_power_loads',
                [
                    'voltage' => 12.1,
                    'amperage' => 1.4,
                    'power' => 16.9,
                    'temperature' => 41.5,
                ],
                [],
            ],

            // `content_pages` guarda el texto en `content`, no en `body`; y tiene
            // `current_page_raw_id`, no `raw_type` (N219). Todas las páginas del
            // CMS salen con `body: null`.
            'ContentPageResource' => [
                ContentPageResource::class,
                'content_pages',
                [
                    'title' => 'Página de pruebas',
                    'slug' => 'pagina-de-pruebas',
                    'content' => 'El cuerpo de verdad.',
                    'order' => 1,
                ],
                [
                    'body' => '`content_pages` guarda el texto en `content`, no en `body`',
                    'raw_type' => '`content_pages` no tiene `raw_type`; tiene `current_page_raw_id`',
                ],
            ],

            // `platforms` guarda el nombre en `title`, no en `name` (N1).
            'PlatformResource' => [
                PlatformResource::class,
                'platforms',
                [
                    'title' => 'Fryntiz',
                    'slug' => 'fryntiz',
                    'domain' => 'fryntiz.es',
                    'description' => 'Plataforma de pruebas',
                ],
                ['name' => '`platforms` guarda el nombre en `title`, no en `name`'],
            ],

            // `keycounter_mouse` no tiene `score` (R-6).
            'MouseResource' => [
                MouseResource::class,
                'keycounter_mouse',
                [
                    'start_at' => '2026-08-19 09:00:00',
                    'end_at' => '2026-08-19 17:00:00',
                    'duration' => 28800,
                    'weekday' => 3,
                    'clicks_left' => 120,
                    'clicks_right' => 45,
                    'clicks_middle' => 3,
                    'total_clicks' => 168,
                    'clicks_average' => 2.8,
                ],
                ['score' => '`keycounter_mouse` no tiene la columna `score`'],
            ],

            // `smartplant_registers` no tiene `user_id`: el dueño de una lectura
            // consta solo a través de la planta (`plant_id`). El Resource
            // referenciaba esa columna inexistente y salía `null` en todas las
            // respuestas (arreglado 2026-08-30).
            'SmartPlantRegisterResource' => [
                SmartPlantRegisterResource::class,
                'smartplant_registers',
                [
                    'soil_humidity' => 42,
                    'soil_humidity_raw' => 610,
                    'uv' => 2,
                    'temperature' => 24.5,
                    'humidity' => 55.0,
                    'full_water_tank' => true,
                    'waterpump_enabled' => false,
                    'vaporizer_enabled' => false,
                ],
                ['user_id' => '`smartplant_registers` no tiene la columna `user_id`'],
            ],
        ];
    }

    /**
     * @param  class-string  $resource
     * @param  array<string,mixed>  $row
     * @param  array<string,string>  $brokenKeys
     */
    #[Test]
    #[DataProvider('resources')]
    public function the_resource_does_not_return_always_null_keys(
        string $resource,
        string $table,
        array $row,
        array $brokenKeys
    ): void {
        if (! Schema::hasTable($table)) {
            $this->markTestSkipped("La tabla `{$table}` no existe.");
        }

        $exitCode = $this->resourceOutput($resource, $table, $row);

        $broken = [];
        foreach ($brokenKeys as $key => $reason) {
            if (! array_key_exists($key, $exitCode)) {
                continue; // ya arreglado: la clave desapareció del Resource.
            }
            if ($exitCode[$key] === null) {
                $broken[] = sprintf('%-22s null  <- %s', $key, $reason);
            }
        }

        $this->assertSame([], $broken, sprintf(
            "\n`%s` devuelve %d clave(s) que valen `null` en TODAS las respuestas:\n\n  %s\n\n".
            "Ninguna la ve `assertJsonStructure`, porque la clave sí existe (N280).\n".
            "Arreglo: leer la columna real, o quitar la clave del Resource si sobra.\n",
            class_basename($resource),
            count($broken),
            implode("\n  ", $broken)
        ));
    }

    /**
     * Además de las claves ya fichadas, cualquier OTRA clave del Resource que
     * salga nula sobre una fila recién insertada es sospechosa. Este test no
     * falla: informa. Sirve para encontrar los casos A que no están en la lista.
     *
     * @param  class-string  $resource
     * @param  array<string,mixed>  $row
     * @param  array<string,string>  $brokenKeys
     */
    #[Test]
    #[DataProvider('resources')]
    public function no_inserted_column_is_lost_on_the_way(
        string $resource,
        string $table,
        array $row,
        array $brokenKeys
    ): void {
        if (! Schema::hasTable($table) || $row === []) {
            $this->markTestSkipped("Sin fila de ejemplo para `{$table}`.");
        }

        $exitCode = $this->resourceOutput($resource, $table, $row);

        $losses = [];
        foreach ($row as $column => $value) {
            if (! array_key_exists($column, $exitCode)) {
                continue; // el Resource no expone esa columna: es una decisión, no un fallo.
            }
            if ($exitCode[$column] === null) {
                $losses[] = sprintf('%-22s se insertó `%s` y el Resource devuelve null', $column, $value);
            }
        }

        $this->assertSame([], $losses, sprintf(
            "\n`%s` pierde %d valor(es) que SÍ estaban en la fila:\n\n  %s\n",
            class_basename($resource),
            count($losses),
            implode("\n  ", $losses)
        ));
    }

    /**
     * Inserta la fila con `DB::table()` y le pasa el Resource por encima usando
     * un modelo anónimo sin `$fillable` ni `$guarded`: el Resource recibe
     * exactamente lo que hay en la tabla, ni más ni menos.
     *
     * @param  class-string  $resource
     * @param  array<string,mixed>  $row
     * @return array<string,mixed>
     */
    private function resourceOutput(string $resource, string $table, array $row): array
    {
        if (Schema::hasColumn($table, 'hardware_device_id')) {
            $row['hardware_device_id'] = $this->device->id;
        }
        if (Schema::hasColumn($table, 'user_id')) {
            $row['user_id'] = $this->user->id;
        }
        if (Schema::hasColumn($table, 'plant_id')) {
            $row['plant_id'] = $this->plant->id;
        }
        if (Schema::hasColumn($table, 'created_at')) {
            $row['created_at'] = now();
        }
        if (Schema::hasColumn($table, 'updated_at')) {
            $row['updated_at'] = now();
        }

        $row = array_filter(
            $row,
            static fn (string $column): bool => Schema::hasColumn($table, $column),
            ARRAY_FILTER_USE_KEY
        );

        $id = DB::table($table)->insertGetId($row);
        $record = (array) DB::table($table)->find($id);

        $model = new class extends Model
        {
            public $timestamps = false;

            protected $guarded = [];

            protected $casts = [
                'created_at' => 'datetime',
                'updated_at' => 'datetime',
                'published_at' => 'datetime',
                'read_at' => 'datetime',
            ];
        };

        $model->setTable($table);
        $model->setRawAttributes($record, true);
        $model->exists = true;

        return (new $resource($model))->toArray(Request::create('/'));
    }
}
