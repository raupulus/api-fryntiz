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

    public function test_it_exits_with_zero_even_when_it_finds_silent_devices(): void
    {
        $this->device('Pico Display', now()->subDays(3));

        Log::shouldReceive('warning')->once()->withArgs(
            function (string $message, array $context): bool {
                return str_contains($message, 'dejado de reportar')
                    && $context['horas'] === 24
                    && $context['dispositivos'][0]['name'] === 'Pico Display';
            }
        );

        $this->artisan('iot:check-silent-devices', ['--hours' => 24])
            ->assertExitCode(0);
    }

    public function test_it_exits_with_zero_when_all_devices_have_reported(): void
    {
        $this->device('Rover', now()->subHour());

        Log::shouldReceive('warning')->never();

        $this->artisan('iot:check-silent-devices', ['--hours' => 24])
            ->expectsOutputToContain('Todos los dispositivos han reportado')
            ->assertExitCode(0);
    }

    public function test_a_device_that_has_never_reported_does_not_count_as_silent(): void
    {
        $this->device('Recién dado de alta', null);

        Log::shouldReceive('warning')->never();

        $this->artisan('iot:check-silent-devices', ['--hours' => 24])
            ->assertExitCode(0);
    }

    public function test_it_does_not_warn_when_there_are_no_registered_devices(): void
    {
        Log::shouldReceive('warning')->never();

        $this->artisan('iot:check-silent-devices')
            ->expectsOutputToContain('No hay dispositivos registrados.')
            ->assertExitCode(0);
    }

    private function device(string $name, mixed $lastSeenAt): HardwareDevice
    {
        // Sin dueño a propósito: el comando mira todo el parque, no filtra por
        // usuario. Montar uno sólo para satisfacer una FK nullable es ruido.
        return HardwareDevice::create([
            'name' => $name,
            'last_seen_at' => $lastSeenAt,
        ]);
    }
}
