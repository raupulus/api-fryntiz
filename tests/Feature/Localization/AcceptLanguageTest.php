<?php

declare(strict_types=1);

namespace Tests\Feature\Localization;

use App\Http\Middleware\SetLocale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * El idioma de la respuesta de la API.
 *
 * Las webs que consumen esta API no comparten sesión con ella, así que lo único
 * que dice en qué idioma responder es la cabecera. Sin esto, un formulario en
 * inglés recibía los errores de validación en español y los enseñaba tal cual.
 */
class AcceptLanguageTest extends TestCase
{
    private function resultingLocale(?string $header, ?string $query = null): string
    {
        App::setLocale(config('app.locale'));

        $request = Request::create('/api/v2/whatever'.($query !== null ? '?lang='.$query : ''));

        if ($header !== null) {
            $request->headers->set('Accept-Language', $header);
        } else {
            // `Request::create()` de Symfony NO crea una petición pelada: mete
            // por su cuenta un `Accept-Language: en-us,en;q=0.5`. Sin quitarlo,
            // el caso «sin cabecera» estaría probando justo lo contrario.
            $request->headers->remove('Accept-Language');
        }

        (new SetLocale)->handle($request, fn () => response('ok'));

        return App::getLocale();
    }

    /**
     * @return array<string,array{0:?string,1:string}>
     */
    public static function headerCases(): array
    {
        return [
            'sin cabecera → el de por defecto' => [null, 'es'],
            'inglés a secas' => ['en', 'en'],
            'inglés con región' => ['en-GB', 'en'],
            'mayúsculas' => ['EN-US', 'en'],
            'lista por preferencia' => ['en-GB,en;q=0.9,es;q=0.8', 'en'],
            'el de mayor q gana aunque vaya después' => ['fr;q=0.5,en;q=0.9', 'en'],
            'un idioma que no tenemos se ignora' => ['fr-FR', 'es'],
            'q=0 significa «este no»' => ['en;q=0,es;q=0.5', 'es'],
            'comodín no es un idioma' => ['*', 'es'],
            'cabecera vacía' => ['', 'es'],
            'basura no revienta' => [';;;q=', 'es'],
        ];
    }

    #[Test]
    #[DataProvider('headerCases')]
    public function the_locale_comes_from_accept_language(?string $header, string $expected): void
    {
        $this->assertSame($expected, $this->resultingLocale($header));
    }

    /**
     * `?lang=` es una elección explícita de la web que consume la API, así que
     * manda sobre lo que ponga el navegador de quien la visita.
     */
    #[Test]
    public function the_lang_parameter_wins_over_the_header(): void
    {
        $this->assertSame('en', $this->resultingLocale('es-ES', 'en'));
        $this->assertSame('es', $this->resultingLocale('en-GB', 'es'));
    }

    #[Test]
    public function an_unknown_lang_does_not_override_the_header(): void
    {
        $this->assertSame('en', $this->resultingLocale('en-GB', 'fr'));
    }

    #[Test]
    public function the_response_declares_its_language(): void
    {
        $request = Request::create('/api/v2/whatever');
        $request->headers->set('Accept-Language', 'en');

        $response = (new SetLocale)->handle($request, fn () => response('ok'));

        $this->assertSame('en', $response->headers->get('Content-Language'));
        $this->assertStringContainsString('Accept-Language', (string) $response->headers->get('Vary'));
    }

    /**
     * Lo que de verdad se quería arreglar: que el mensaje de error salga en el
     * idioma pedido, y con el nombre del campo en cristiano.
     */
    #[Test]
    public function validation_messages_come_out_translated(): void
    {
        App::setLocale('es');
        $es = trans('validation.required', ['attribute' => trans('validation.attributes.hardware_device_id')]);

        App::setLocale('en');
        $en = trans('validation.required', ['attribute' => trans('validation.attributes.hardware_device_id')]);

        $this->assertStringContainsString('dispositivo', $es);
        $this->assertStringContainsString('device', $en);
        $this->assertNotSame($es, $en, 'Los dos idiomas devuelven lo mismo: las traducciones no se están cargando.');

        // Y que no devuelva la clave, que es el síntoma de que `lang/` no se lee.
        $this->assertStringNotContainsString('validation.', $es);
    }
}
