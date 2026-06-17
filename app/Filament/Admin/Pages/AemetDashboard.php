<?php

declare(strict_types=1);

namespace App\Filament\Admin\Pages;

use App\Models\WeatherStation\AEMET\AEMETAdverseEvents;
use App\Models\WeatherStation\AEMET\AEMETCoast;
use App\Models\WeatherStation\AEMET\AEMETContamination;
use App\Models\WeatherStation\AEMET\AEMETHighSea;
use App\Models\WeatherStation\AEMET\AEMETOzone;
use App\Models\WeatherStation\AEMET\AEMETPrediction;
use App\Models\WeatherStation\AEMET\AEMETPredictionBeach;
use App\Models\WeatherStation\AEMET\AEMETSunRadiation;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Artisan;

/**
 * Página dashboard AEMET — tarjetas por tabla con conteo + último update +
 * botón "Re-sincronizar" que lanza el comando artisan correspondiente.
 */
class AemetDashboard extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCloud;

    protected static string|\UnitEnum|null $navigationGroup = 'Módulos';

    protected static ?int $navigationSort = 80;

    protected static ?string $title = 'AEMET';

    protected static ?string $slug = 'aemet';

    protected string $view = 'filament.admin.pages.aemet-dashboard';

    public static function canAccess(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    /**
     * @return array<string,array{label:string,description:string,model:class-string,command:string}>
     */
    public function describeTables(): array
    {
        return [
            'adverse_events' => [
                'label' => 'Eventos adversos',
                'description' => 'Avisos de fenómenos meteorológicos adversos.',
                'model' => AEMETAdverseEvents::class,
                'command' => 'aemet:every-30m',
            ],
            'high_seas' => [
                'label' => 'Alta mar',
                'description' => 'Avisos y predicciones de alta mar.',
                'model' => AEMETHighSea::class,
                'command' => 'aemet:every-4h',
            ],
            'contamination' => [
                'label' => 'Contaminación',
                'description' => 'Mediciones de contaminación atmosférica.',
                'model' => AEMETContamination::class,
                'command' => 'aemet:daily',
            ],
            'prediction_beachs' => [
                'label' => 'Predicción playas',
                'description' => 'Predicción meteorológica de playas.',
                'model' => AEMETPredictionBeach::class,
                'command' => 'aemet:daily',
            ],
            'prediction_coasts' => [
                'label' => 'Predicción costas',
                'description' => 'Predicción meteorológica de costas.',
                'model' => AEMETCoast::class,
                'command' => 'aemet:daily',
            ],
            'ozone' => [
                'label' => 'Ozono',
                'description' => 'Datos de ozono en superficie.',
                'model' => AEMETOzone::class,
                'command' => 'aemet:daily',
            ],
            'sun_radiation' => [
                'label' => 'Radiación solar',
                'description' => 'Radiación solar acumulada diaria.',
                'model' => AEMETSunRadiation::class,
                'command' => 'aemet:daily-8',
            ],
            'predictions' => [
                'label' => 'Predicciones generales',
                'description' => 'Predicción meteorológica general por municipios.',
                'model' => AEMETPrediction::class,
                'command' => 'aemet:daily-12',
            ],
        ];
    }

    public function getViewData(): array
    {
        $cards = [];
        foreach ($this->describeTables() as $key => $meta) {
            $model = $meta['model'];
            if (! class_exists($model)) {
                $cards[$key] = $meta + ['count' => 0, 'last' => null, 'missing' => true];

                continue;
            }
            $count = $model::query()->count();
            $last = $model::query()->latest('updated_at')->value('updated_at')
                ?? $model::query()->latest('created_at')->value('created_at');
            $cards[$key] = $meta + ['count' => $count, 'last' => $last, 'key' => $key, 'missing' => false];
        }

        return ['cards' => $cards];
    }

    public function resync(string $command): void
    {
        $allowed = [
            'aemet:every-10m', 'aemet:every-30m', 'aemet:every-4h',
            'aemet:daily', 'aemet:daily-8', 'aemet:daily-12', 'aemet:daily-20',
        ];

        if (! in_array($command, $allowed, true)) {
            Notification::make()->title('Comando no permitido')->danger()->send();

            return;
        }

        try {
            Artisan::call($command);
            Notification::make()
                ->title('Sincronización AEMET ejecutada')
                ->body($command)
                ->success()
                ->send();
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Error de sincronización')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
}
