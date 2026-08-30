<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

use function in_array;

/**
 * Fija el idioma de la respuesta a partir de `Accept-Language`.
 *
 * Las webs que consumen esta API no comparten sesión con ella: cada petición
 * llega sola y sin más contexto que sus cabeceras. Sin esto, la API responde
 * **siempre** en el idioma por defecto, así que un formulario en inglés recibe
 * los errores de validación en español y los enseña tal cual.
 *
 * Se acepta la cabecera estándar y, por delante, un `?lang=` explícito: es lo
 * que permite a una web forzar el idioma sin depender de lo que mande el
 * navegador de quien la visita.
 *
 * Un idioma que no tengamos **no es un error**: se ignora y se responde en el
 * idioma por defecto. Devolver un 400 por una cabecera que el navegador pone
 * solo sería castigar al cliente por algo que no ha elegido.
 */
class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $this->requestedLocale($request);

        if ($locale !== null) {
            App::setLocale($locale);
        }

        $response = $next($request);

        // Que el cliente y las cachés intermedias sepan en qué idioma va esto y
        // que la respuesta depende de la cabecera.
        $response->headers->set('Content-Language', App::getLocale());
        $response->headers->set('Vary', 'Accept-Language', false);

        return $response;
    }

    /**
     * El primer idioma pedido que tengamos, o `null`.
     */
    private function requestedLocale(Request $request): ?string
    {
        $disponibles = $this->available();

        // `?lang=en` manda sobre la cabecera: es una elección explícita.
        $explicit = $this->normalise((string) $request->query('lang', ''));

        if ($explicit !== null && in_array($explicit, $disponibles, true)) {
            return $explicit;
        }

        foreach ($this->byPreference((string) $request->header('Accept-Language', '')) as $candidato) {
            if (in_array($candidato, $disponibles, true)) {
                return $candidato;
            }
        }

        return null;
    }

    /**
     * Los idiomas de `Accept-Language`, ordenados por su factor `q`.
     *
     * `es-ES,es;q=0.9,en;q=0.8` → `['es', 'es', 'en']`. El `q` por defecto es 1,
     * y un `q=0` significa «este no», así que se descarta.
     *
     * @return list<string>
     */
    private function byPreference(string $header): array
    {
        if (trim($header) === '') {
            return [];
        }

        $candidates = [];

        foreach (explode(',', $header) as $chunk) {
            $parts = explode(';', trim($chunk));
            $locale = $this->normalise($parts[0]);

            if ($locale === null) {
                continue;
            }

            $quality = 1.0;

            foreach (array_slice($parts, 1) as $parameter) {
                if (str_starts_with(trim($parameter), 'q=')) {
                    $quality = (float) substr(trim($parameter), 2);
                }
            }

            if ($quality > 0) {
                $candidates[] = ['language' => $locale, 'q' => $quality];
            }
        }

        // Orden estable: a igual `q`, gana el que venga antes en la cabecera.
        usort($candidates, static fn (array $a, array $b): int => $b['q'] <=> $a['q']);

        return array_column($candidates, 'language');
    }

    /**
     * `es-ES` y `ES` son el idioma `es`. `*` no es un idioma.
     */
    private function normalise(string $value): ?string
    {
        $clean = strtolower(trim($value));

        if ($clean === '' || $clean === '*') {
            return null;
        }

        return explode('-', $clean)[0];
    }

    /**
     * Los idiomas que existen de verdad en `lang/`.
     *
     * Se leen del disco y no de una lista escrita a mano para que añadir un
     * idioma sea crear su carpeta y nada más.
     *
     * @return list<string>
     */
    private function available(): array
    {
        static $locales = null;

        if ($locales !== null) {
            return $locales;
        }

        $carpetas = glob(lang_path('*'), GLOB_ONLYDIR) ?: [];

        $locales = array_values(array_filter(
            array_map('basename', $carpetas),
            static fn (string $name): bool => $name !== 'vendor'
        ));

        // El de por defecto siempre vale, aunque no tenga carpeta propia.
        if (! in_array(config('app.locale'), $locales, true)) {
            $locales[] = (string) config('app.locale');
        }

        return $locales;
    }
}
