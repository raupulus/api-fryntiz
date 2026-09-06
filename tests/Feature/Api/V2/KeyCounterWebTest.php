<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V2;

use App\Models\Hardware\HardwareDevice;
use App\Models\Hardware\HardwareType;
use App\Models\KeyCounter\Keyboard;
use App\Models\User;
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

        $this->assertSame(
            1,
            substr_count($html, 'bg-purple-50'),
            'Sólo una tarjeta puede ir destacada como dispositivo top.'
        );

        // La tarjeta destacada es la del equipo con más pulsaciones.
        $tarjeta = substr($html, (int) strpos($html, 'bg-purple-50'), 1500);
        $this->assertStringContainsString($top->name_friendly, $tarjeta);
        $this->assertStringContainsString('emoji_events', $tarjeta);
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
