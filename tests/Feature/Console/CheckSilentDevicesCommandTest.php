<?php

declare(strict_types=1);

namespace Tests\Feature\Console;

use App\Models\Hardware\HardwareDevice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * `iot:check-silent-devices`.
 *
 * Lo que fija este test es el código de salida: encontrar un cacharro mudo es
 * el trabajo del comando, no un fallo suyo. Cuando devolvía 1, el planificador
 * lo tomaba por una excepción y volcaba su traza en el log cada mañana, al lado
 * del WARNING que sí sirve.
 */
class CheckSilentDevicesCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_sale_con_cero_aunque_encuentre_dispositivos_mudos(): void
    {
        $this->dispositivo('Pico Display', now()->subDays(3));

        Log::shouldReceive('warning')->once()->withArgs(
            function (string $mensaje, array $contexto): bool {
                return str_contains($mensaje, 'dejado de reportar')
                    && $contexto['horas'] === 24
                    && $contexto['dispositivos'][0]['name'] === 'Pico Display';
            }
        );

        $this->artisan('iot:check-silent-devices', ['--hours' => 24])
            ->assertExitCode(0);
    }

    public function test_sale_con_cero_cuando_todos_han_reportado(): void
    {
        $this->dispositivo('Rover', now()->subHour());

        Log::shouldReceive('warning')->never();

        $this->artisan('iot:check-silent-devices', ['--hours' => 24])
            ->expectsOutputToContain('Todos los dispositivos han reportado')
            ->assertExitCode(0);
    }

    public function test_un_dispositivo_que_nunca_ha_reportado_no_cuenta_como_mudo(): void
    {
        $this->dispositivo('Recién dado de alta', null);

        Log::shouldReceive('warning')->never();

        $this->artisan('iot:check-silent-devices', ['--hours' => 24])
            ->assertExitCode(0);
    }

    public function test_sin_dispositivos_registrados_no_avisa(): void
    {
        Log::shouldReceive('warning')->never();

        $this->artisan('iot:check-silent-devices')
            ->expectsOutputToContain('No hay dispositivos registrados.')
            ->assertExitCode(0);
    }

    private function dispositivo(string $nombre, mixed $lastSeenAt): HardwareDevice
    {
        // Sin dueño a propósito: el comando mira todo el parque, no filtra por
        // usuario. Montar uno sólo para satisfacer una FK nullable es ruido.
        return HardwareDevice::create([
            'name' => $nombre,
            'last_seen_at' => $lastSeenAt,
        ]);
    }
}
