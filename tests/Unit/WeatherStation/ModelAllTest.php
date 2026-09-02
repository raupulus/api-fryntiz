<?php

declare(strict_types=1);

namespace Tests\Unit\WeatherStation;

use App\Models\WeatherStation\AirQuality;
use App\Models\WeatherStation\Light;
use App\Models\WeatherStation\Rain;
use App\Models\WeatherStation\Temperature;
use App\Models\WeatherStation\Wind;
use App\Models\WeatherStation\WindDirection;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * `Model::all()` funciona en los modelos de la estación.
 *
 * Suena a probar el framework, y lo sería si estos modelos no hubieran
 * sobrescrito `all()` con esto (auditoría AR-E03):
 *
 *     public static function all($columns = ['*'])
 *     {
 *         $query = parent::all();          // una Collection
 *         $query::whereNotNull('lumens')   // llamada ESTÁTICA a un método de instancia
 *             ->orderBy('created_at', 'DESC')  // orderBy() no existe en Collection
 *             ->get();                     // y el resultado se tiraba
 *
 *         return $query;                   // se devolvía la colección sin filtrar
 *     }
 *
 * Tres fallos en cinco líneas. El primero es fatal en PHP 8:
 *
 *     Error: Non-static method Illuminate\Support\Collection::whereNotNull()
 *     cannot be called statically
 *
 * No reventaba nada porque daba la casualidad de que nadie llamaba a `all()`
 * sobre estos seis modelos. `all()` es API pública de Eloquent —la usa un
 * `Select::options()` de Filament, un seeder, un comando nuevo— y el fallo no
 * es una excepción capturable, es un `Error` fatal.
 *
 * PHPStan lo señalaba con precisión («Call to an undefined method
 * Collection::orderBy()») y estaba silenciado en el baseline.
 *
 * @return array<string, array{0: class-string}>
 */
class ModelAllTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{0: class-string}>
     */
    public static function modelosProvider(): array
    {
        return [
            'Light' => [Light::class],
            'Rain' => [Rain::class],
            'Wind' => [Wind::class],
            'AirQuality' => [AirQuality::class],
            'WindDirection' => [WindDirection::class],
            // No sobrescribía `all()`, pero hereda de la misma base: se prueba
            // para que quede claro que la base tampoco lo rompe.
            'Temperature' => [Temperature::class],
        ];
    }

    #[Test]
    #[DataProvider('modelosProvider')]
    public function all_devuelve_una_coleccion_sin_reventar(string $modelo): void
    {
        $resultado = $modelo::all();

        $this->assertInstanceOf(Collection::class, $resultado);
    }

    #[Test]
    #[DataProvider('modelosProvider')]
    public function un_update_no_intenta_escribir_updated_at(string $modelo): void
    {
        // Estas tablas no tienen `updated_at`. Se resolvía sobrescribiendo
        // `setUpdatedAt()` con un cuerpo vacío, y eso no bastaba:
        // `Builder::addUpdatedAtColumn()` no llama a ese método, mira
        // `getUpdatedAtColumn()`. Cualquier `update()` de Eloquent montaba la
        // columna en el SQL y reventaba con:
        //
        //   SQLSTATE[42703]: column "updated_at" of relation
        //   "meteorology_temperature" does not exist
        //
        // No daba la cara porque la ingesta escribe con `insert()` del query
        // builder. Salió al limpiar el baseline de PHPStan (AR-E03 / D14).
        $this->assertNull(
            (new $modelo)->getUpdatedAtColumn(),
            $modelo.' declara una columna updated_at que su tabla no tiene.'
        );

        // La consulta se construye y se ejecuta: si volviera a colarse
        // `updated_at`, PostgreSQL la rechazaría aquí mismo. Se actualiza
        // `hardware_device_id` porque es la única columna que tienen todas
        // —cada sensor guarda lo suyo en `lumens`, `rain`, `average`…— y el
        // `whereKey(-1)` garantiza que no toca ninguna fila.
        $modelo::query()->whereKey(-1)->update(['hardware_device_id' => 1]);
    }

    #[Test]
    #[DataProvider('modelosProvider')]
    public function all_devuelve_lo_mismo_que_una_consulta_normal(string $modelo): void
    {
        // El `all()` roto aparentaba filtrar por «valor no nulo» y en realidad
        // devolvía la colección entera, porque el resultado del filtro se
        // tiraba. Así que además de no reventar hay que fijar QUÉ devuelve:
        // exactamente lo mismo que `query()->get()`, ni más ni menos.
        //
        // Estos modelos no tienen `HasFactory` —las lecturas las escribe la API
        // con `insert()` del query builder—, así que se compara contra la
        // consulta directa en lugar de sembrar filas.
        $this->assertSame(
            $modelo::query()->pluck('id')->all(),
            $modelo::all()->pluck('id')->all(),
        );
    }
}
