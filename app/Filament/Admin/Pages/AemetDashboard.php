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
use App\Support\WeatherStation\AemetApiKey;
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
    /**
     * Una tarjeta por tabla, y cada tarjeta con **el comando que llena esa
     * tabla y ninguna otra**.
     *
     * Antes no era así: cinco de las ocho tarjetas apuntaban a
     * `aemet:update-daily`, un comando cuyo `handle()` estaba vacío, y «Alta
     * mar» llamaba a `aemet:update-every4h`, que traía la predicción horaria.
     * Los botones existían, se pulsaban, decían que todo había ido bien y no
     * traían nada. Por eso ahora hay ocho comandos, uno por producto.
     *
     * @return array<string, array{label:string, description:string, model:class-string, command:string}>
     */
    public function describeTables(): array
    {
        return [
            'adverse_events' => [
                'label' => 'Eventos adversos',
                'description' => 'Avisos de fenómenos meteorológicos adversos. AEMET los emite cuando los hay; se consulta cada 30 min.',
                'model' => AEMETAdverseEvents::class,
                'command' => 'aemet:adverse-events',
            ],
            'contamination' => [
                'label' => 'Contaminación',
                'description' => 'Mediciones de contaminación atmosférica. Publicación horaria.',
                'model' => AEMETContamination::class,
                'command' => 'aemet:contamination',
            ],
            'predictions' => [
                'label' => 'Predicción horaria',
                'description' => 'Predicción horaria del municipio. AEMET la rehace cada 3 h.',
                'model' => AEMETPrediction::class,
                'command' => 'aemet:hourly-prediction',
            ],
            'prediction_beachs' => [
                'label' => 'Predicción playas',
                'description' => 'La Regla y La Cruz del Mar. Publicación diaria.',
                'model' => AEMETPredictionBeach::class,
                'command' => 'aemet:beaches',
            ],
            'prediction_coasts' => [
                'label' => 'Predicción costas',
                'description' => 'Predicción costera. Dos emisiones al día, mediodía y tarde.',
                'model' => AEMETCoast::class,
                'command' => 'aemet:coast',
            ],
            'high_seas' => [
                'label' => 'Alta mar',
                'description' => 'Avisos y predicciones de alta mar. Publicación diaria.',
                'model' => AEMETHighSea::class,
                'command' => 'aemet:high-sea',
            ],
            'ozone' => [
                'label' => 'Ozono',
                'description' => 'Ozono en superficie. Publicación diaria.',
                'model' => AEMETOzone::class,
                'command' => 'aemet:ozone',
            ],
            'sun_radiation' => [
                'label' => 'Radiación solar',
                'description' => 'Radiación solar acumulada diaria.',
                'model' => AEMETSunRadiation::class,
                'command' => 'aemet:sun-radiation',
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

        return [
            'cards' => $cards,
            // La clave caduca a los ~100 días y su caducidad no da error: AEMET
            // responde 200 con el cuerpo vacío. Si no se ve aquí, no se ve en
            // ningún sitio hasta que alguien echa de menos un dato.
            'apiKey' => AemetApiKey::status(),
            // Citar a AEMET no es cortesía: lo exige su nota legal, y la
            // Ley 18/2015 trae régimen sancionador. Los textos son los suyos.
            'attribution' => config('aemet.attribution'),
        ];
    }

    /**
     * N265: los 8 botones llamaban a comandos que NO EXISTEN — `aemet:daily` en
     * vez de `aemet:update-daily`, y así los siete. La lista blanca comparaba
     * contra los mismos nombres inventados, así que la comprobación pasaba y el
     * fallo salía después, al ejecutar. **Fallaban siempre, los ocho.**
     *
     * La lista blanca se construye ahora desde `describeTables()` para que no
     * puedan volver a divergir: si se añade una tarjeta, su comando queda
     * permitido solo, y si se escribe mal, falla en el único sitio donde está
     * escrito.
     */
    public function resync(string $command): void
    {
        $allowed = array_values(array_unique(
            array_column($this->describeTables(), 'command')
        ));

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
