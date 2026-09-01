<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Test;
use ReflectionMethod;
use ReflectionNamedType;
use Tests\TestCase;

/**
 * Los parámetros de ruta se pasan por POSICIÓN, no por nombre.
 *
 * Si la ruta declara `/resize/{module}/{id}/{width}/{slug?}` y el método los
 * recibe en otro orden, cada valor entra en el parámetro que no le toca. Con
 * `declare(strict_types=1)` —que este proyecto usa en todo— eso es un
 * TypeError, o sea un 500, en cuanto los tipos no cuadran.
 *
 * Así estaba `/file/resize`: la firma tenía `$slug` antes que `$width`, el slug
 * llegaba a un parámetro `int` y la ruta respondía 500 siempre. Nadie lo
 * detectó porque no había ningún test que la llamara.
 *
 * Este test recorre todas las rutas y compara el orden de los dos lados.
 */
class RouteParametersTest extends TestCase
{
    /** Tipos que Laravel rellena desde la URL; el resto los inyecta el contenedor. */
    private const ESCALARES = ['int', 'string', 'float', 'bool', 'mixed'];

    #[Test]
    public function los_parametros_de_cada_ruta_llegan_en_el_orden_de_su_firma(): void
    {
        $cruzadas = [];

        foreach (Route::getRoutes() as $route) {
            $action = $route->getActionName();

            if (! str_contains($action, '@')) {
                continue;
            }

            [$class, $method] = explode('@', $action);

            if (! class_exists($class) || ! method_exists($class, $method)) {
                continue;
            }

            $enRuta = $route->parameterNames();

            if ($enRuta === []) {
                continue;
            }

            $enFirma = $this->parametrosEscalares($class, $method);

            if ($enFirma === []) {
                continue;
            }

            // Se comparan sólo los que aparecen en ambos lados: un método puede
            // recibir parámetros que la ruta no declara, y viceversa.
            $ordenFirma = array_values(array_intersect($enFirma, $enRuta));
            $ordenRuta = array_values(array_intersect($enRuta, $enFirma));

            if ($ordenFirma !== $ordenRuta) {
                $cruzadas[] = sprintf(
                    '%s → %s::%s (ruta: %s | firma: %s)',
                    $route->uri(),
                    class_basename($class),
                    $method,
                    implode(', ', $ordenRuta),
                    implode(', ', $ordenFirma)
                );
            }
        }

        $this->assertSame([], $cruzadas, sprintf(
            "%d ruta(s) reciben sus parámetros en distinto orden del que declaran.\n".
            "Laravel los pasa por posición, así que esto es un 500 en cuanto los tipos no cuadran:\n  - %s\n",
            count($cruzadas),
            implode("\n  - ", $cruzadas)
        ));
    }

    /**
     * Parámetros del método que Laravel rellena desde la URL, en orden.
     *
     * @return list<string>
     */
    private function parametrosEscalares(string $class, string $method): array
    {
        $nombres = [];

        foreach ((new ReflectionMethod($class, $method))->getParameters() as $parametro) {
            $tipo = $parametro->getType();
            $nombre = $tipo instanceof ReflectionNamedType ? $tipo->getName() : null;

            // Request, FormRequest y modelos los resuelve el contenedor o el
            // route model binding: no ocupan posición de la URL.
            if ($nombre !== null && ! in_array($nombre, self::ESCALARES, true)) {
                continue;
            }

            $nombres[] = $parametro->getName();
        }

        return $nombres;
    }
}
