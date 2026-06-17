<?php

declare(strict_types=1);

namespace App\Console\Commands\Debug;

use App\Console\Commands\Debug\Concerns\ResolvesDebugDefaults;
use Illuminate\Console\Command;

class SeedAllDebugCommand extends Command
{
    use ResolvesDebugDefaults;

    protected $signature = 'debug:seed-all {--small : Reduce las cantidades por defecto a 1/5 para llenado rápido}';

    protected $description = 'Ejecuta TODOS los comandos debug:seed-* en orden seguro (solo desarrollo)';

    /**
     * Orden de ejecución: primero usuarios, luego hardware (que depende de user),
     * luego el resto de módulos en cualquier orden ya que dependen del hardware/usuario.
     */
    public function handle(): int
    {
        if (! $this->guardEnvironment()) {
            return self::FAILURE;
        }

        $scale = $this->option('small') ? 0.2 : 1.0;

        $commands = [
            ['debug:seed-hardware', ['--count' => max(1, (int) (5 * $scale))]],
            ['debug:seed-platform', ['--count' => max(1, (int) (3 * $scale))]],
            ['debug:seed-content', ['--count' => max(1, (int) (10 * $scale))]],
            ['debug:seed-cv', []],
            ['debug:seed-newsletter', ['--count' => max(1, (int) (10 * $scale))]],
            ['debug:seed-contact', ['--count' => max(1, (int) (10 * $scale))]],
            ['debug:seed-weatherstation', ['--count' => max(1, (int) (20 * $scale))]],
            ['debug:seed-keycounter', ['--count' => max(1, (int) (50 * $scale))]],
            ['debug:seed-airflight', ['--planes' => max(1, (int) (10 * $scale)), '--routes' => max(1, (int) (100 * $scale))]],
            ['debug:seed-smartplant', ['--plants' => max(1, (int) (5 * $scale)), '--registers' => max(1, (int) (50 * $scale))]],
            ['debug:seed-energy', ['--devices' => max(1, (int) (5 * $scale)), '--records' => max(1, (int) (100 * $scale))]],
        ];

        foreach ($commands as [$name, $args]) {
            $this->newLine();
            $this->line('════════════════════════════════════════════');
            $this->line("▶ Ejecutando: {$name}");
            $this->line('════════════════════════════════════════════');

            $exitCode = $this->call($name, $args);

            if ($exitCode !== self::SUCCESS) {
                $this->warn("⚠️  Comando {$name} terminó con código {$exitCode}. Continuando.");
            }
        }

        $this->newLine();
        $this->info('✅ Seed-all completado.');

        return self::SUCCESS;
    }
}
