<?php

declare(strict_types=1);

namespace Tests\Feature\Console;

use App\Filament\Admin\Pages\AemetDashboard;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Artisan;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * El planificador y el panel sólo pueden llamar a comandos que existan.
 *
 * Motivo: durante mucho tiempo `routes/console.php` programó cuatro comandos
 * inexistentes (`aemet:adverse-events`, `aemet:contamination`,
 * `aemet:predictions`, `keycounter:maintenance`) y el panel de AEMET tenía
 * botones apuntando a otros. Nada de eso da error visible: la tarea se ejecuta,
 * artisan no encuentra el comando y todo sigue en silencio. Ésa es la razón de
 * fondo de que el módulo AEMET llevara años sin actualizarse.
 *
 * Estos dos tests convierten ese fallo silencioso en uno ruidoso.
 */
class SchedulerTest extends TestCase
{
    #[Test]
    public function every_scheduled_task_calls_a_command_that_exists(): void
    {
        $registered = array_keys(Artisan::all());
        $programados = $this->scheduledCommands();

        $this->assertNotEmpty($programados, 'No hay ninguna tarea programada; el planificador está vacío.');

        foreach ($programados as $comando) {
            $this->assertContains(
                $comando,
                $registered,
                "El planificador llama a «{$comando}» y ese comando no existe."
            );
        }
    }

    #[Test]
    public function every_aemet_panel_button_calls_a_command_that_exists(): void
    {
        $registered = array_keys(Artisan::all());
        $tables = (new AemetDashboard)->describeTables();

        $this->assertNotEmpty($tables);

        foreach ($tables as $key => $meta) {
            $this->assertArrayHasKey('command', $meta, "La tarjeta «{$key}» no declara comando.");
            $this->assertContains(
                $meta['command'],
                $registered,
                "La tarjeta «{$key}» llama a «{$meta['command']}» y ese comando no existe."
            );
        }
    }

    #[Test]
    public function each_aemet_table_has_its_own_command(): void
    {
        // Cinco de las ocho tarjetas apuntaban al mismo comando, y encima a uno
        // con el `handle()` vacío: los botones decían que todo había ido bien y
        // no traían nada.
        $commands = array_column((new AemetDashboard)->describeTables(), 'command');

        $this->assertSame(
            count($commands),
            count(array_unique($commands)),
            'Hay tarjetas de AEMET compartiendo comando: '.implode(', ', $commands)
        );
    }

    /**
     * Nombres de los comandos que el planificador tiene programados.
     *
     * @return array<int, string>
     */
    private function scheduledCommands(): array
    {
        $schedule = $this->app->make(Schedule::class);

        $commands = [];

        foreach ($schedule->events() as $event) {
            $name = $this->commandName($event);

            if ($name !== null) {
                $commands[] = $name;
            }
        }

        return array_values(array_unique($commands));
    }

    /**
     * Saca el nombre del comando de la línea que ejecuta el planificador.
     *
     * La línea es del estilo `'/usr/bin/php' 'artisan' aemet:coast --opción`.
     * Sólo interesan los eventos de comando artisan; los `exec` se ignoran.
     */
    private function commandName(Event $event): ?string
    {
        if (! preg_match("/artisan'?\s+(?:'([^']+)'|([^\s'\"]+))/", $event->command ?? '', $matches)) {
            return null;
        }

        $name = $matches[1] !== '' ? $matches[1] : ($matches[2] ?? '');

        return $name !== '' ? $name : null;
    }
}
