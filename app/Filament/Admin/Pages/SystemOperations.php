<?php

declare(strict_types=1);

namespace App\Filament\Admin\Pages;

use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Panel de operaciones de mantenimiento para SuperAdmin: permite ejecutar
 * tareas seguras del sistema desde la intranet sin abrir una terminal SSH.
 */
class SystemOperations extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCommandLine;

    protected static string|\UnitEnum|null $navigationGroup = 'Sistema';

    protected static ?int $navigationSort = 40;

    protected static ?string $title = 'Operaciones del sistema';

    protected static ?string $navigationLabel = 'Operaciones';

    protected static ?string $slug = 'system-operations';

    protected string $view = 'filament.admin.pages.system-operations';

    public ?string $lastCommand = null;

    public ?string $lastOutput = null;

    public ?int $lastExitCode = null;

    public static function canAccess(): bool
    {
        return auth()->user()?->isSuperAdmin() ?? false;
    }

    /**
     * Lista blanca de comandos permitidos desde la intranet.
     *
     * @return array<string, array{label: string, description: string, command: string, color: string}>
     */
    public function availableOperations(): array
    {
        return [
            'sitemap' => [
                'label' => 'Regenerar Sitemap XML',
                'description' => 'Actualiza public/sitemap.xml con las páginas públicas, hardware, plantas y currículums activos.',
                'command' => 'sitemap:generate',
                'color' => 'primary',
            ],
            'gdacs' => [
                'label' => 'Sincronizar alertas GDACS',
                'description' => 'Consulta el feed oficial de desastres naturales cercanos y actualiza los avisos activos.',
                'command' => 'gdacs:sync',
                'color' => 'primary',
            ],
            'marine' => [
                'label' => 'Sincronizar oleaje (Open-Meteo)',
                'description' => 'Descarga los últimos datos marinos y de oleaje para el panel de meteorología.',
                'command' => 'marine:sync',
                'color' => 'primary',
            ],
            'check_config' => [
                'label' => 'Verificar configuración',
                'description' => 'Audita claves críticas del entorno (APP_KEY, base de datos, caché, correo y AEMET).',
                'command' => 'project:check-config',
                'color' => 'info',
            ],
            'clear' => [
                'label' => 'Limpiar cachés de aplicación',
                'description' => 'Limpia vistas compiladas, rutas, configuración y caché de aplicación sin tocar datos de BD.',
                'command' => 'project:clear',
                'color' => 'warning',
            ],
            'silent_devices' => [
                'label' => 'Comprobar dispositivos mudos',
                'description' => 'Detecta dispositivos IoT que llevan demasiado tiempo sin reportar telemetría.',
                'command' => 'iot:check-silent-devices',
                'color' => 'info',
            ],
            'stale_print_jobs' => [
                'label' => 'Limpiar cola de impresión atascada',
                'description' => 'Marca como fallidos los trabajos bloqueados en estado «imprimiendo» por encima del límite.',
                'command' => 'printers:cleanup-stale-jobs',
                'color' => 'warning',
            ],
            'purge_unused_files' => [
                'label' => 'Purgar ficheros sin uso (CMS)',
                'description' => 'Elimina ficheros huérfanos antiguos del editor de contenidos que no están referenciados.',
                'command' => 'content:purge-unused-files',
                'color' => 'danger',
            ],
            'warm_keycounter' => [
                'label' => 'Precalentar caché KeyCounter',
                'description' => 'Recalcula las estadísticas agregadas de teclado y ratón para acelerar las consultas públicas.',
                'command' => 'keycounter:warm_cache',
                'color' => 'info',
            ],
        ];
    }

    public function runOperation(string $command): void
    {
        if (! static::canAccess()) {
            abort(403);
        }

        $allowed = array_column($this->availableOperations(), 'command');

        if (! in_array($command, $allowed, true)) {
            Notification::make()
                ->title('Operación no permitida')
                ->danger()
                ->send();

            return;
        }

        try {
            $exitCode = Artisan::call($command);
            $output = trim(Artisan::output());

            $this->lastCommand = $command;
            $this->lastExitCode = $exitCode;
            $this->lastOutput = $output !== '' ? $output : '(Sin salida en consola)';

            Log::info('SystemOperations: command executed from admin panel', [
                'command' => $command,
                'exit_code' => $exitCode,
                'user_id' => auth()->id(),
            ]);

            if ($exitCode === 0) {
                Notification::make()
                    ->title('Operación completada')
                    ->body($command)
                    ->success()
                    ->send();
            } else {
                Notification::make()
                    ->title('Operación finalizada con avisos')
                    ->body("{$command} (código {$exitCode})")
                    ->warning()
                    ->send();
            }
        } catch (Throwable $e) {
            $this->lastCommand = $command;
            $this->lastExitCode = 1;
            $this->lastOutput = $e->getMessage();

            Log::error('SystemOperations: command failed', [
                'command' => $command,
                'message' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            Notification::make()
                ->title('Error al ejecutar la operación')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    protected function getViewData(): array
    {
        return [
            'operations' => $this->availableOperations(),
        ];
    }
}
