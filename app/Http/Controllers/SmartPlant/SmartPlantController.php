<?php

declare(strict_types=1);

namespace App\Http\Controllers\SmartPlant;

use App\Http\Controllers\Controller;
use App\Models\SmartPlant\SmartPlantPlant;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * Class SmartPlantController
 */
class SmartPlantController extends Controller
{
    /**
     * Sensores numéricos de un registro que tiene sentido resumir en
     * mínimo/máximo. `soil_humidity` es el único obligatorio en el hardware;
     * el resto depende del kit de sensores instalado en cada planta, así que
     * no todas las plantas tienen los cinco.
     *
     * @var array<string, array{label: string, unit: string}>
     */
    private const STAT_FIELDS = [
        'soil_humidity' => ['label' => 'Humedad Tierra', 'unit' => '%'],
        'temperature' => ['label' => 'Temperatura', 'unit' => '°C'],
        'humidity' => ['label' => 'Humedad Aire', 'unit' => '%'],
        'pressure' => ['label' => 'Presión', 'unit' => 'hPa'],
        'uv' => ['label' => 'Radiación UV', 'unit' => ''],
    ];

    /**
     * LLeva a la vista resumen con datos para depurar subidas.
     *
     * @return Application|Factory|View
     */
    public function index()
    {
        $smartplants = SmartPlantPlant::with(['registers' => function ($query) {
            $query->latest()->take(10);
        }])->get();

        return view('smartplant.index')->with([
            'smartplants' => $smartplants,
        ]);
    }

    /**
     * Muestra el perfil completo de una planta por su slug o redirige con 301
     * desde las URLs numéricas antiguas (`/smartplant/{id}`) para preservar SEO.
     */
    public function show(string $smartplant): Application|Factory|View|RedirectResponse
    {
        if (ctype_digit($smartplant)) {
            $plant = SmartPlantPlant::find((int) $smartplant);

            if (! $plant) {
                abort(404);
            }

            return redirect()->route('smartplant.show', ['smartplant' => $plant->slug], 301);
        }

        $plant = SmartPlantPlant::where('slug', $smartplant)->firstOrFail();

        $registers = $plant->registers()
            ->latest()
            ->take(50)
            ->get();

        return view('smartplant.show')->with([
            'plant' => $plant,
            'registers' => $registers,
            'statCards' => $this->buildStatCards($plant),
        ]);
    }

    /**
     * Mínimo/máximo de hoy, esta semana y este mes para cada sensor que la
     * planta tenga de verdad. Se calcula con agregados en base de datos y no
     * sobre `$registers` (las últimas 50 lecturas no bastan para cubrir un
     * mes, según la cadencia de subida del dispositivo).
     *
     * @return list<array{field: string, label: string, unit: string, stats: array<string, array{label: string, min: int|float|null, max: int|float|null}>}>
     */
    private function buildStatCards(SmartPlantPlant $smartplant): array
    {
        return Cache::remember(
            'smartplant:web:stat_cards:'.$smartplant->getKey(),
            300,
            function () use ($smartplant): array {
                $now = Carbon::now();

                $windows = [
                    'day' => ['label' => 'Hoy', 'from' => $now->copy()->startOfDay()],
                    'week' => ['label' => 'Semana', 'from' => $now->copy()->startOfWeek()],
                    'month' => ['label' => 'Mes', 'from' => $now->copy()->startOfMonth()],
                ];

                $cards = [];

                foreach (self::STAT_FIELDS as $field => $meta) {
                    if (! $smartplant->registers()->whereNotNull($field)->exists()) {
                        continue;
                    }

                    $stats = [];

                    foreach ($windows as $key => $window) {
                        // `toBase()` para que el resultado sea un stdClass y no un
                        // modelo a medio hidratar con dos columnas que no existen en
                        // la tabla.
                        $baseQuery = $smartplant->registers()
                            ->toBase()
                            ->where('created_at', '>=', $window['from'])
                            ->whereNotNull($field);

                        $wrappedField = $baseQuery->getGrammar()->wrap($field);

                        $aggregate = $baseQuery
                            ->selectRaw("MIN({$wrappedField}) as min_value, MAX({$wrappedField}) as max_value")
                            ->first();

                        $stats[$key] = [
                            'label' => $window['label'],
                            'min' => $aggregate?->min_value,
                            'max' => $aggregate?->max_value,
                        ];
                    }

                    $cards[] = [
                        'field' => $field,
                        'label' => $meta['label'],
                        'unit' => $meta['unit'],
                        'stats' => $stats,
                    ];
                }

                return $cards;
            }
        );
    }
}
