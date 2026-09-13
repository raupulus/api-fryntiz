<?php

declare(strict_types=1);

namespace Tests\Unit\Rules;

use App\Rules\EnergyTelemetryPayload;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EnergyTelemetryPayloadTest extends TestCase
{
    #[Test]
    public function it_accepts_a_simple_iot_load_with_duration(): void
    {
        $payload = [
            'duration' => 60,
            'loads' => [
                [
                    'channel' => 0,
                    'voltage' => 12.1,
                    'amperage' => 1.5,
                    'power' => 18.15,
                ],
            ],
        ];

        $validator = Validator::make(
            ['energy' => $payload],
            ['energy' => ['required', 'array', new EnergyTelemetryPayload]]
        );

        $this->assertTrue($validator->passes());
    }

    #[Test]
    public function it_accepts_a_complex_payload_with_generator_battery_and_loads(): void
    {
        $payload = [
            'duration' => 300,
            'generator' => [
                'voltage' => 34.5,
                'amperage' => 4.2,
                'power' => 144.9,
                'temperature' => 38.0,
                'fan' => 1,
                'charging_status' => 3,
                'charging_status_label' => 'mppt',
                'light_status' => false,
                'today_energy_wh' => 1250.0,
                'historical_energy_wh' => 45000.0,
            ],
            'battery' => [
                'voltage' => 26.8,
                'soc' => 95,
                'temperature' => 24.5,
                'today_energy_ah' => 40.0,
                'historical_energy_ah' => 1500.0,
                'battery_full_charges' => 25,
            ],
            'loads' => [
                [
                    'channel' => 0,
                    'voltage' => 24.0,
                    'amperage' => 2.1,
                    'power' => 50.4,
                    'today_energy_wh' => 500.0,
                ],
            ],
        ];

        $validator = Validator::make(
            ['energy' => $payload],
            ['energy' => ['required', 'array', new EnergyTelemetryPayload]]
        );

        $this->assertTrue($validator->passes());
    }

    #[Test]
    public function it_rejects_an_empty_energy_block(): void
    {
        $payload = [
            'duration' => 60,
        ];

        $validator = Validator::make(
            ['energy' => $payload],
            ['energy' => ['required', 'array', new EnergyTelemetryPayload]]
        );

        $this->assertFalse($validator->passes());
        $this->assertContains(
            'El bloque energy debe contener al menos uno de los subsistemas: generator, battery o loads.',
            $validator->errors()->all()
        );
    }

    #[Test]
    public function it_rejects_invalid_battery_soc_greater_than_100(): void
    {
        $payload = [
            'battery' => [
                'voltage' => 12.0,
                'soc' => 150,
            ],
        ];

        $validator = Validator::make(
            ['energy' => $payload],
            ['energy' => ['required', 'array', new EnergyTelemetryPayload]]
        );

        $this->assertFalse($validator->passes());
        $this->assertContains(
            'El estado de carga (SOC) de la batería debe estar comprendido entre 0 y 100 %.',
            $validator->errors()->all()
        );
    }

    #[Test]
    public function it_rejects_duration_less_than_one(): void
    {
        $payload = [
            'duration' => 0,
            'generator' => [
                'voltage' => 12.0,
            ],
        ];

        $validator = Validator::make(
            ['energy' => $payload],
            ['energy' => ['required', 'array', new EnergyTelemetryPayload]]
        );

        $this->assertFalse($validator->passes());
        $this->assertContains(
            'La duración del intervalo debe ser al menos de 1 segundo.',
            $validator->errors()->all()
        );
    }

    #[Test]
    public function it_validates_the_interval_energy_and_the_operating_days(): void
    {
        // Estos cuatro campos los lee el servicio pero no estaban declarados
        // aquí: entraban sin validar y se casteaban en silencio.
        //
        // Ojo con la batería: ahí la energía del intervalo **sí** puede ser
        // negativa, porque es el neto de carga menos descarga. Lo que no puede
        // ser negativo es un acumulado de por vida.
        $casos = [
            ['generator' => ['voltage' => 24.0, 'energy_wh' => -5]],
            ['generator' => ['voltage' => 24.0, 'total_operating_days' => 'muchos']],
            ['battery' => ['voltage' => 13.0, 'historical_energy_ah' => -1]],
            ['battery' => ['voltage' => 13.0, 'days_operating' => -3]],
            ['loads' => [['channel' => 0, 'amperage' => 1.0, 'energy_wh' => -0.5]]],
            ['loads' => [['channel' => 0, 'amperage' => 1.0, 'total_operating_days' => 1.5]]],
        ];

        foreach ($casos as $indice => $energy) {
            $fallos = [];
            (new EnergyTelemetryPayload)->validate(
                'energy',
                $energy,
                static function (string $mensaje) use (&$fallos) {
                    $fallos[] = $mensaje;
                }
            );

            $this->assertNotEmpty($fallos, "El caso {$indice} tenía que fallar y ha pasado.");
        }
    }

    #[Test]
    public function it_accepts_the_interval_energy_and_the_operating_days_when_they_are_right(): void
    {
        $fallos = [];

        (new EnergyTelemetryPayload)->validate(
            'energy',
            [
                'duration' => 60,
                'generator' => ['voltage' => 24.0, 'energy_wh' => 2.4, 'energy_ah' => 0.1, 'total_operating_days' => 1745],
                'battery' => ['voltage' => 13.0, 'energy_ah' => 0.08, 'days_operating' => 900],
                'loads' => [['channel' => 0, 'amperage' => 1.0, 'energy_wh' => 0.5, 'total_operating_days' => 30]],
            ],
            static function (string $mensaje) use (&$fallos) {
                $fallos[] = $mensaje;
            }
        );

        $this->assertSame([], $fallos);
    }
}
