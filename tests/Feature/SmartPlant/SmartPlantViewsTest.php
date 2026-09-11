<?php

declare(strict_types=1);

namespace Tests\Feature\SmartPlant;

use App\Models\SmartPlant\SmartPlantPlant;
use App\Models\SmartPlant\SmartPlantRegister;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
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

    private function plant(string $description = 'Un bonsái', string $details = 'Detalles de la planta.'): SmartPlantPlant
    {
        return SmartPlantPlant::create([
            'name' => 'Olmo chino',
            'name_scientific' => 'Ulmus parvifolia',
            'description' => $description,
            'details' => $details,
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

    /**
     * Crea una lectura con la humedad de tierra indicada, en el instante
     * dado. `soil_humidity` no admite null en la tabla, así que sirve para
     * comprobar los mínimos/máximos sin ambigüedad de tipos (a diferencia de
     * las columnas `decimal`, que vuelven de la agregación como cadena).
     */
    private function readingAt(SmartPlantPlant $plant, int $soilHumidity, Carbon $createdAt): void
    {
        $register = SmartPlantRegister::create([
            'plant_id' => $plant->id,
            'soil_humidity' => $soilHumidity,
        ]);

        // `save()` no sirve para pisar `created_at` aquí: la tabla no tiene
        // `updated_at`, y `SmartPlantRegister::setUpdatedAt()` está anulado a
        // propósito para no escribirlo — pero `Eloquent\Builder::update()`
        // lo vuelve a añadir por su cuenta en cuanto el modelo usa
        // timestamps, sin pasar por ese método. Una query directa a la tabla
        // no tiene ese problema.
        DB::table('smartplant_registers')->where('id', $register->id)->update(['created_at' => $createdAt]);
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
     * `details` es donde de verdad se escribe con marcado (secciones «Origen»,
     * «Ecología»... envueltas en `<p>`/`<strong>`/`<br>`). Antes se partía a
     * mano por líneas en blanco con `{{ }}`, así que las etiquetas salían
     * escapadas y se leían tal cual: «&lt;p&gt;».
     */
    #[Test]
    public function the_details_field_also_allows_basic_html(): void
    {
        $plant = $this->plant(details: '<p><strong>Origen</strong>: de Asia.<br>Crece rápido.</p>');

        $this->get(route('smartplant.show', $plant))
            ->assertOk()
            ->assertSee('<strong>Origen</strong>', escape: false)
            ->assertDontSee('&lt;strong&gt;');
    }

    #[Test]
    public function the_details_field_does_not_let_a_script_through(): void
    {
        $plant = $this->plant(details: '<p>Hola</p><script>alert(1)</script>');

        $this->get(route('smartplant.show', $plant))
            ->assertOk()
            ->assertDontSee('<script>alert(1)</script>', escape: false);
    }

    /**
     * Las tarjetas de mínimo/máximo se calculan con agregados sobre toda la
     * tabla, no sobre las últimas 50 lecturas, así que hace falta fijar
     * `now()` para poder situar cada lectura en su ventana (hoy/semana/mes)
     * sin que dependa del día en que se ejecute el test.
     *
     * 2026-09-16 es miércoles: la semana (lunes) empieza el 14, el mes el 1.
     */
    #[Test]
    public function the_summary_cards_show_min_and_max_per_window(): void
    {
        $this->travelTo(Carbon::parse('2026-09-16 12:00:00'));

        $plant = $this->plant();

        $this->readingAt($plant, 30, now());                          // hoy
        $this->readingAt($plant, 10, Carbon::parse('2026-09-15 08:00')); // esta semana, no hoy
        $this->readingAt($plant, 50, Carbon::parse('2026-09-05 08:00')); // este mes, no esta semana
        $this->readingAt($plant, 1000, Carbon::parse('2026-08-20 08:00')); // fuera de las tres ventanas

        $html = $this->get(route('smartplant.show', $plant))->assertOk()->getContent();

        // La lectura de hace un mes (1000%) sigue saliendo en la tabla de
        // últimas lecturas —eso no cambia—, así que la comprobación de que no
        // se cuela en ningún mínimo/máximo se hace sólo sobre esta sección.
        $statsSection = Str::between($html, 'Mínimos y máximos', '<table');

        $this->assertStringContainsString('Humedad Tierra', $statsSection);
        $this->assertStringContainsString('30% / 30%', $statsSection); // hoy
        $this->assertStringContainsString('10% / 30%', $statsSection); // semana
        $this->assertStringContainsString('10% / 50%', $statsSection); // mes
        $this->assertStringNotContainsString('1000', $statsSection);
    }

    /**
     * `soil_humidity` es el único sensor obligatorio en el hardware; el resto
     * depende del kit instalado. Una planta sin datos de presión no debe
     * enseñar una tarjeta de presión vacía.
     *
     * "Presión" y "Radiación UV" ya aparecen en la página por otro lado (la
     * cabecera de la tabla y el bloque de «Última lectura»), así que hace
     * falta comprobar la cabecera exacta de la tarjeta y no sólo el texto.
     */
    #[Test]
    public function a_sensor_without_any_data_gets_no_card(): void
    {
        $plant = $this->plant();
        $this->readingAt($plant, 40, now());

        $html = $this->get(route('smartplant.show', $plant))->assertOk()->getContent();

        $this->assertStringContainsString('<h4 class="text-on-surface font-bold mb-3">Humedad Tierra</h4>', $html);
        $this->assertStringNotContainsString('<h4 class="text-on-surface font-bold mb-3">Presión</h4>', $html);
        $this->assertStringNotContainsString('<h4 class="text-on-surface font-bold mb-3">Radiación UV</h4>', $html);
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
