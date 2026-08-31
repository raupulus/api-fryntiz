<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Foundation\Console\ServeCommand as BaseServeCommand;
use Symfony\Component\Process\Process;

use function Illuminate\Support\php_binary;

/**
 * Sobrescribe el `serve` nativo de Laravel para levantar también el
 * servidor de WebSockets (`reverb:start`) cuando `BROADCAST_CONNECTION=reverb`.
 *
 * Se registra bajo el mismo nombre `serve` (heredado del padre), por lo que
 * Artisan sustituye al comando del framework por este durante el auto-discovery
 * de app/Console/Commands.
 */
class ServeCommand extends BaseServeCommand
{
    protected ?Process $reverbProcess = null;

    /** {@inheritdoc} */
    public function handle()
    {
        $this->startReverbIfEnabled();

        return parent::handle();
    }

    protected function startReverbIfEnabled(): void
    {
        if (config('broadcasting.default') !== 'reverb') {
            return;
        }

        $this->reverbProcess = new Process(
            [php_binary(), 'artisan', 'reverb:start'],
            base_path(),
        );
        $this->reverbProcess->setTimeout(null);

        $this->reverbProcess->start(function (string $type, string $buffer): void {
            foreach (explode("\n", rtrim($buffer)) as $line) {
                if ($line !== '') {
                    $this->output->writeln("  <fg=magenta>[reverb]</> {$line}");
                }
            }
        });

        $reverbProcess = $this->reverbProcess;
        register_shutdown_function(static function () use ($reverbProcess): void {
            if ($reverbProcess->isRunning()) {
                $reverbProcess->stop(5);
            }
        });

        $this->components->info('Reverb (WebSockets) iniciado en segundo plano.');
    }
}
