<?php

declare(strict_types=1);

namespace Tests\Feature\SmartPlant;

use App\Models\SmartPlant\SmartPlantPlant;
use App\Models\SmartPlant\SmartPlantRegister;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Las vistas públicas de SmartPlant.
 *
 * Dos cosas que arreglar y una que no se puede romper:
 *
 *  - La descripción se escribe desde la intranet y tiene que admitir HTML
 *    básico, que antes salía escapado y se leía como «&lt;p&gt;».
 *  - Los estados («Riego activo» y compañía) usaban `bg-green-100` y
 *    `text-green-700`, colores fijos de Tailwind sin variante para el tema
 *    oscuro: fondo claro con la etiqueta encima también clara.
 *  - Y lo que no se puede romper: que por ahí entre un `<script>`.
 */
class SmartPlantViewsTest extends TestCase
{
    use RefreshDatabase;

    private function planta(string $descripcion = 'Un bonsái'): SmartPlantPlant
    {
        return SmartPlantPlant::create([
            'name' => 'Olmo chino',
            'name_scientific' => 'Ulmus parvifolia',
            'description' => $descripcion,
            'details' => 'Detalles de la planta.',
            'image' => 'smartplant/default.jpg',
            'start_at' => now()->subYear(),
        ]);
    }

    private function lectura(SmartPlantPlant $planta, bool $regando): void
    {
        SmartPlantRegister::create([
            'plant_id' => $planta->id,
            'soil_humidity' => 40,
            'temperature' => 21.5,
            'humidity' => 55,
            'uv' => 3,
            'full_water_tank' => true,
            'waterpump_enabled' => $regando,
            'vaporizer_enabled' => false,
        ]);
    }

    #[Test]
    public function la_descripcion_admite_html_basico(): void
    {
        $this->planta('<p>Un <strong>bonsái</strong> de interior.</p>');

        $this->get(route('smartplant.index'))
            ->assertOk()
            // Sin escapar: la etiqueta llega como etiqueta.
            ->assertSee('<strong>bonsái</strong>', escape: false)
            ->assertDontSee('&lt;strong&gt;');
    }

    #[Test]
    public function la_descripcion_no_deja_pasar_un_script(): void
    {
        $this->planta('<p>Hola</p><script>alert(1)</script><a href="javascript:alert(2)">x</a>');

        $respuesta = $this->get(route('smartplant.index'));

        $respuesta->assertOk()
            ->assertDontSee('<script>alert(1)</script>', escape: false)
            ->assertDontSee('javascript:alert(2)', escape: false);
    }

    #[Test]
    public function la_descripcion_tambien_admite_html_en_la_ficha(): void
    {
        $planta = $this->planta('<p>Un <em>olmo</em> con riego automático.</p>');

        $this->get(route('smartplant.show', $planta))
            ->assertOk()
            ->assertSee('<em>olmo</em>', escape: false);
    }

    /**
     * La `<meta description>` no admite HTML: si se cuela un `<p>`, sale tal
     * cual en el resultado de búsqueda.
     */
    #[Test]
    public function las_metas_de_la_ficha_van_en_texto_plano(): void
    {
        $planta = $this->planta('<p>Un <strong>olmo</strong> chino.</p>');

        $html = $this->get(route('smartplant.show', $planta))->assertOk()->getContent();

        preg_match('/<meta name="description" content="([^"]*)"/', (string) $html, $m);

        $this->assertNotEmpty($m, 'No se encontró la meta description.');
        $this->assertSame('Un olmo chino.', html_entity_decode($m[1]));
    }

    #[Test]
    public function una_planta_sin_descripcion_no_revienta(): void
    {
        $planta = $this->planta('');

        $this->get(route('smartplant.index'))->assertOk()->assertSee('Sin descripción');
        $this->get(route('smartplant.show', $planta))->assertOk()->assertSee('Sin descripción');
    }

    /**
     * Los estados tienen que ir con tokens del sistema, que sí cambian con el
     * tema, y no con los verdes fijos de Tailwind.
     */
    #[Test]
    public function los_estados_usan_tokens_y_no_colores_fijos(): void
    {
        $planta = $this->planta();
        $this->lectura($planta, regando: true);

        foreach ([route('smartplant.index'), route('smartplant.show', $planta)] as $url) {
            $html = (string) $this->get($url)->assertOk()->getContent();

            $this->assertStringContainsString('bg-success-container', $html);
            $this->assertStringContainsString('text-on-success-container', $html);
            $this->assertStringNotContainsString('bg-green-100', $html);
            $this->assertStringNotContainsString('text-green-700', $html);
        }
    }

    /**
     * El icono y los badges de «Hardware del proyecto» iban con
     * `on-tertiary-container` sobre `tertiary-fixed`: 2,91:1 en claro y 1,01:1
     * en oscuro, o sea el círculo color carne con el texto invisible.
     */
    #[Test]
    public function los_badges_de_los_repositorios_usan_el_token_correcto(): void
    {
        $html = (string) $this->get(route('smartplant.index'))->assertOk()->getContent();

        $this->assertStringContainsString('text-on-tertiary-fixed', $html);
        $this->assertStringNotContainsString(
            'text-on-tertiary-container bg-tertiary-fixed',
            $html,
        );
    }
}
