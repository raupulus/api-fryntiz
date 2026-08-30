<?php

declare(strict_types=1);

namespace App\Console\Commands\IoT;

use App\Models\Hardware\HardwareDevice;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Avisa de los dispositivos que han dejado de reportar.
 *
 * El agujero que tapa: **nada avisaba cuando un cacharro se callaba**. Al mirar
 * producción (2026-08-23) el monitor del Rover llevaba parado y no se había
 * enterado nadie; lo mismo pasaba con AEMET. Un dispositivo mudo es
 * indistinguible de uno que funciona hasta que alguien va a mirar una gráfica
 * meses después y ve el hueco.
 *
 * No manda correos ni notificaciones: deja constancia en el log, que es donde
 * se mira cuando algo va mal, y devuelve código de salida 1 si hay alguno mudo
 * para que el planificador lo marque como fallo.
 */
class CheckSilentDevicesCommand extends Command
{
    protected $signature = 'iot:check-silent-devices
                            {--hours=24 : Horas sin dar señales a partir de las cuales se considera mudo}';

    protected $description = 'Avisa de los dispositivos IoT que llevan tiempo sin reportar';

    public function handle(): int
    {
        $hours = max(1, (int) $this->option('hours'));
        $limit = now()->subHours($hours);

        $devices = HardwareDevice::query()
            ->orderBy('name')
            ->get(['id', 'name', 'last_seen_at']);

        if ($devices->isEmpty()) {
            $this->info('No hay dispositivos registrados.');

            return self::SUCCESS;
        }

        // Un dispositivo que nunca ha reportado no es un fallo nuevo: puede que
        // se acabe de dar de alta y aún no se haya flasheado. Se separa.
        $nuncaVistos = $devices->whereNull('last_seen_at');
        $silent = $devices->filter(
            fn (HardwareDevice $d) => $d->last_seen_at !== null && $d->last_seen_at->lt($limit)
        );

        foreach ($nuncaVistos as $device) {
            $this->warn("· #{$device->id} {$device->name}: nunca ha reportado.");
        }

        foreach ($silent as $device) {
            $from = $device->last_seen_at->diffForHumans();
            $this->error("· #{$device->id} {$device->name}: sin reportar desde {$from}.");
        }

        if ($silent->isEmpty()) {
            $this->info("Todos los dispositivos han reportado en las últimas {$hours} h.");

            return self::SUCCESS;
        }

        Log::warning('IoT: hay dispositivos que han dejado de reportar', [
            'horas' => $hours,
            'dispositivos' => $silent->map(fn (HardwareDevice $d) => [
                'id' => $d->id,
                'name' => $d->name,
                'last_seen_at' => $d->last_seen_at?->toIso8601String(),
            ])->values()->all(),
        ]);

        return self::FAILURE;
    }
}
