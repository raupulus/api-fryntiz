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

    private function plant(string $description = 'Un bonsái'): SmartPlantPlant
    {
        return SmartPlantPlant::create([
            'name' => 'Olmo chino',
            'name_scientific' => 'Ulmus parvifolia',
            'description' => $description,
            'details' => 'Detalles de la planta.',
            'image' => 'smartplant/default.jpg',
            'start_at' => now()->subYear(),
        ]);
    }

    private function reading(SmartPlantPlant $plant, bool $watering): void
    {
        SmartPlantRegister::create([
            'plant_id' => $plant->id,
            'soil_humidity' => 40,
            'temperature' => 21.5,
            'humidity' => 55,
            'uv' => 3,
            'full_water_tank' => true,
            'waterpump_enabled' => $watering,
            'vaporizer_enabled' => false,
        ]);
    }

    #[Test]
    public function the_description_allows_basic_html(): void
    {
        $this->plant('<p>Un <strong>bonsái</strong> de interior.</p>');

        $this->get(route('smartplant.index'))
            ->assertOk()
            // Sin escapar: la etiqueta llega como etiqueta.
            ->assertSee('<strong>bonsái</strong>', escape: false)
            ->assertDontSee('&lt;strong&gt;');
    }

    #[Test]
    public function the_description_does_not_let_a_script_through(): void
    {
        $this->plant('<p>Hola</p><script>alert(1)</script><a href="javascript:alert(2)">x</a>');

        $response = $this->get(route('smartplant.index'));

        $response->assertOk()
            ->assertDontSee('<script>alert(1)</script>', escape: false)
            ->assertDontSee('javascript:alert(2)', escape: false);
    }

    #[Test]
    public function the_description_also_allows_html_on_the_detail_page(): void
    {
        $plant = $this->plant('<p>Un <em>olmo</em> con riego automático.</p>');

        $this->get(route('smartplant.show', $plant))
            ->assertOk()
            ->assertSee('<em>olmo</em>', escape: false);
    }

    /**
     * La `<meta description>` no admite HTML: si se cuela un `<p>`, sale tal
     * cual en el resultado de búsqueda.
     */
    #[Test]
    public function the_detail_pages_meta_tags_are_plain_text(): void
    {
        $plant = $this->plant('<p>Un <strong>olmo</strong> chino.</p>');

        $html = $this->get(route('smartplant.show', $plant))->assertOk()->getContent();

        preg_match('/<meta name="description" content="([^"]*)"/', (string) $html, $m);

        $this->assertNotEmpty($m, 'No se encontró la meta description.');
        $this->assertSame('Un olmo chino.', html_entity_decode($m[1]));
    }

    #[Test]
    public function a_plant_without_a_description_does_not_blow_up(): void
    {
        $plant = $this->plant('');

        $this->get(route('smartplant.index'))->assertOk()->assertSee('Sin descripción');
        $this->get(route('smartplant.show', $plant))->assertOk()->assertSee('Sin descripción');
    }

    /**
     * Los estados tienen que ir con tokens del sistema, que sí cambian con el
     * tema, y no con los verdes fijos de Tailwind.
     */
    #[Test]
    public function the_statuses_use_tokens_and_not_fixed_colors(): void
    {
        $plant = $this->plant();
        $this->reading($plant, watering: true);

        foreach ([route('smartplant.index'), route('smartplant.show', $plant)] as $url) {
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
    public function the_repository_badges_use_the_correct_token(): void
    {
        $html = (string) $this->get(route('smartplant.index'))->assertOk()->getContent();

        $this->assertStringContainsString('text-on-tertiary-fixed', $html);
        $this->assertStringNotContainsString(
            'text-on-tertiary-container bg-tertiary-fixed',
            $html,
        );
    }
}
