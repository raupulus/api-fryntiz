<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Hardware\EnergySystem;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Las cuatro instalaciones reales del montaje (D79).
 *
 * Una instalación agrupa elementos que comparten batería y tensión, y es lo que
 * permite preguntar «cuánto ha generado la casa hoy» sin listar ids a mano.
 *
 * Aquí sólo se dan de alta **las instalaciones**, no sus elementos: un elemento
 * necesita saber qué dispositivo lo mide y en qué canal, y eso depende del
 * montaje de cada momento. Se asignan desde el panel poniéndole a cada fila de
 * `hardware_energy` su `energy_system_id`, su `role` y su `nominal_voltage`
 * —esta última es la que arregla el cálculo de los vatios—.
 *
 * Es idempotente: se identifica por `slug` y no duplica.
 */
class EnergySystemsSeeder extends Seeder
{
    /**
     * @var list<array<string,mixed>>
     */
    private const INSTALACIONES = [
        [
            'slug' => 'casa',
            'name' => 'Sistema principal de casa',
            'is_standalone' => false,
            'nominal_voltage' => 12.0,
            'battery_capacity_ah' => 500.0,
            'notes' => 'Placas a 24 V y batería a 12 V. Alimenta portátil, servidor y pantallas. '.
                'Es el que gobierna el Renogy Rover.',
        ],
        [
            'slug' => 'autonomo-grande',
            'name' => 'Sistema autónomo grande',
            'is_standalone' => false,
            'nominal_voltage' => 12.0,
            'battery_capacity_ah' => 100.0,
            'notes' => 'IoT y routers.',
        ],
        [
            'slug' => 'banco-routers',
            'name' => 'Banco de routers',
            'is_standalone' => false,
            'nominal_voltage' => 12.4,
            'battery_capacity_ah' => null,
            'notes' => 'Routers, switches y modems a 12,4 V estabilizados. Sólo consumo: '.
                'aquí no hay generación que medir, y todos sus elementos van con role = load.',
        ],
        [
            'slug' => 'nodos-iot',
            'name' => 'Nodos IoT',
            'is_standalone' => true,
            'nominal_voltage' => 3.7,
            'battery_capacity_ah' => 2.0,
            'notes' => 'Uno por cacharro: placa de 100-500 mA y batería de 500-2000 mAh, entre 3,7 y 6 V. '.
                'Autoabastecidos, sin red.',
        ],
    ];

    public function run(): void
    {
        $user = User::query()->orderBy('id')->first();

        if (! $user) {
            $this->command?->warn('No hay ningún usuario: las instalaciones se crean con el primero que exista.');

            return;
        }

        foreach (self::INSTALACIONES as $instalacion) {
            EnergySystem::query()->updateOrCreate(
                ['slug' => $instalacion['slug']],
                $instalacion + ['user_id' => $user->id]
            );
        }

        $this->command?->info(count(self::INSTALACIONES).' instalaciones energéticas listas.');
    }
}
