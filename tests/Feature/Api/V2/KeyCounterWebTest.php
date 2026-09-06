<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V2;

use App\Models\Hardware\HardwareDevice;
use App\Models\Hardware\HardwareType;
use App\Models\KeyCounter\Keyboard;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Tests\Feature\Api\ApiTestCase;

class KeyCounterWebTest extends ApiTestCase
{
    public function test_keycounter_web_page_renders_successfully_with_data()
    {
        [$top, $secundario] = $this->crearDispositivosConPulsaciones();

        $response = $this->get('/keycounter');

        $response->assertStatus(200);

        // Widgets con su icono propio.
        $response->assertSee('functions');
        $response->assertSee('military_tech');
        $response->assertSee('event');
        $response->assertSee('bar_chart');

        // El dispositivo con más pulsaciones va destacado (icono `devices`) y
        // el resto con el icono común (`dns`).
        $response->assertSee('devices');
        $response->assertSee('dns');
        $response->assertSee($top->name_friendly);
        $response->assertSee($secundario->name_friendly);
    }

    /**
     * El dispositivo top no tiene tarjeta propia: se marca la suya dentro de
     * los totales por dispositivo. Antes se pintaban las dos, así que el mismo
     * equipo salía repetido en la rejilla.
     */
    public function test_top_device_is_shown_once_with_its_badge()
    {
        [$top] = $this->crearDispositivosConPulsaciones();

        $html = $this->get('/keycounter')->assertStatus(200)->getContent();

        $this->assertSame(
            1,
            substr_count($html, (string) __('keycounter.top_device')),
            'El distintivo «Dispositivo top» debe aparecer una sola vez.'
        );

        // La tarjeta destacada es la del equipo con más pulsaciones, y es
        // única: el ayudante ya comprueba que no hay otra.
        $tarjeta = $this->tarjetaDestacada();
        $this->assertStringContainsString($top->name_friendly, $tarjeta);
        $this->assertStringContainsString('emoji_events', $tarjeta);
    }

    /**
     * El distintivo no está atado a ningún equipo concreto: cuando otro pasa
     * a acumular más pulsaciones, la tarjeta destacada es la suya.
     */
    public function test_top_device_badge_follows_the_new_leader()
    {
        [$antiguo, $nuevo] = $this->crearDispositivosConPulsaciones();

        $tarjeta = $this->tarjetaDestacada();
        $this->assertStringContainsString($antiguo->name_friendly, $tarjeta);

        // El segundo equipo adelanta al primero.
        Keyboard::create([
            'user_id' => $nuevo->user_id,
            'hardware_device_id' => $nuevo->id,
            'start_at' => now()->subMinutes(10),
            'end_at' => now(),
            'duration' => 600,
            'pulsations' => 5000,
            'pulsations_special_keys' => 10,
            'pulsation_average' => 8.3,
            'score' => 90,
            'weekday' => 1,
            'created_at' => now(),
        ]);

        Cache::forget('keycounter:widgets');

        $tarjeta = $this->tarjetaDestacada();
        $this->assertStringContainsString($nuevo->name_friendly, $tarjeta);
        $this->assertStringNotContainsString($antiguo->name_friendly, $tarjeta);
    }

    /**
     * Devuelve el HTML de la única tarjeta destacada de la rejilla.
     */
    private function tarjetaDestacada(): string
    {
        $html = $this->get('/keycounter')->assertStatus(200)->getContent();

        $this->assertSame(1, substr_count($html, 'bg-purple-50'));

        // Se recorta en el arranque de la tarjeta siguiente para no arrastrar
        // el contenido de los demás dispositivos.
        $inicio = (int) strpos($html, 'bg-purple-50');
        $propia = (int) strpos($html, 'rounded-lg p-4 text-center shadow', $inicio);
        $siguiente = strpos($html, 'rounded-lg p-4 text-center shadow', $propia + 1);

        return $siguiente === false
            ? substr($html, $inicio)
            : substr($html, $inicio, $siguiente - $inicio);
    }

    /**
     * Crea dos dispositivos con pulsaciones distintas y devuelve
     * [el de más pulsaciones, el de menos].
     *
     * @return array{0: HardwareDevice, 1: HardwareDevice}
     */
    private function crearDispositivosConPulsaciones(): array
    {
        $user = User::factory()->create(['role_id' => 1]);
        $type = HardwareType::create(['name' => 'Generic', 'description' => 'Tipo de prueba']);

        $crear = function (string $name, string $friendly, int $pulsations) use ($user, $type): HardwareDevice {
            $device = HardwareDevice::create([
                'user_id' => $user->id,
                'hardware_type_id' => $type->id,
                'name' => $name,
                'name_friendly' => $friendly,
            ]);

            Keyboard::create([
                'user_id' => $user->id,
                'hardware_device_id' => $device->id,
                'start_at' => now()->subMinutes(10),
                'end_at' => now(),
                'duration' => 600,
                'pulsations' => $pulsations,
                'pulsations_special_keys' => 50,
                'pulsation_average' => 2.0,
                'score' => 75,
                'weekday' => 1,
                'created_at' => now(),
            ]);

            return $device;
        };

        return [
            $crear('test-device', 'Test Keyboard Device', 1200),
            $crear('test-device-2', 'Segundo Teclado', 300),
        ];
    }
}
