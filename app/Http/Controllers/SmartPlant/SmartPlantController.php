<?php

declare(strict_types=1);

namespace App\Http\Controllers\SmartPlant;

use App\Http\Controllers\Controller;
use App\Models\SmartPlant\SmartPlantPlant;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;

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
     * Muestra el perfil completo de una planta con sus últimas 50 lecturas.
     *
     * @return Application|Factory|View
     */
    public function show(SmartPlantPlant $smartplant)
    {
        $registers = $smartplant->registers()
            ->latest()
            ->take(50)
            ->get();

        return view('smartplant.show')->with([
            'plant' => $smartplant,
            'registers' => $registers,
            'statCards' => $this->buildStatCards($smartplant),
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
                $aggregate = $smartplant->registers()
                    ->toBase()
                    ->where('created_at', '>=', $window['from'])
                    ->whereNotNull($field)
                    ->selectRaw("MIN({$field}) as min_value, MAX({$field}) as max_value")
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
}
