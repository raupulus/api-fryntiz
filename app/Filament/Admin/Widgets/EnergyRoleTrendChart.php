<?php

declare(strict_types=1);

namespace App\Filament\Admin\Widgets;

use App\Models\Hardware\HardwareEnergy;
use App\Models\Hardware\HardwareEnergyReading;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

/**
 * Cómo ha ido un papel del aparato **hora a hora durante la última semana**.
 *
 * Una gráfica por papel: la del generador enseña cuándo y cuánto produce el
 * panel, la de la batería cuándo se carga y cuándo se descarga —ahí la potencia
 * es negativa— y la del consumo cuánto se gasta.
 *
 * Se dibuja la **potencia media de cada hora** y no la energía acumulada porque
 * lo que se viene a mirar es la forma del día: a qué hora arranca el panel,
 * cuándo pega el pico, si el consumo es plano o tiene picos.
 */
class EnergyRoleTrendChart extends ChartWidget
{
    /**
     * No se monta en el Dashboard general: es un widget de contexto que
     * requiere `$deviceId` y se monta exclusivamente en la ficha de cada
     * aparato (`ManageEnergyDevice`).
     */
    protected static bool $isDiscovered = false;

    /**
     * El aparato del que se pintan las lecturas.
     */
    public ?int $deviceId = null;

    /**
     * Qué papel de ese aparato: `generator`, `battery` o `load`.
     */
    public string $papel = HardwareEnergy::ROLE_GENERATOR;

    protected int|string|array $columnSpan = 'full';

    protected ?string $maxHeight = '260px';

    private const DIAS = 7;

    public static function canView(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public function getHeading(): string
    {
        $etiqueta = HardwareEnergy::ROLE_LABELS[$this->papel] ?? $this->papel;

        return $etiqueta.' — potencia media por hora, últimos 7 días';
    }

    public function getDescription(): ?string
    {
        return match ($this->papel) {
            HardwareEnergy::ROLE_GENERATOR => 'Lo que ha entrado del generador. Los valles de la noche son normales.',
            HardwareEnergy::ROLE_BATTERY => 'Positivo mientras se carga, negativo mientras se descarga.',
            default => 'Lo que han gastado los consumos de este aparato.',
        };
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        $desde = Carbon::now('UTC')->startOfHour()->subHours(self::DIAS * 24 - 1);

        $medido = $this->potenciaPorHora($desde);

        $horas = [];
        $valores = [];

        for ($hora = $desde->copy(); $hora <= Carbon::now('UTC')->startOfHour(); $hora->addHour()) {
            $clave = $hora->format('Y-m-d H');

            $horas[] = $hora->format('H:00') === '00:00'
                ? $hora->format('d/m')
                : $hora->format('H').'h';

            // `null` y no 0: una hora sin lecturas es un hueco en la serie, no
            // una hora en la que se midieron cero vatios. Con 0 la gráfica
            // dibujaría un desplome que nunca pasó.
            $valores[] = array_key_exists($clave, $medido) ? round($medido[$clave], 1) : null;
        }

        [$borde, $fondo] = match ($this->papel) {
            HardwareEnergy::ROLE_GENERATOR => ['rgb(34,197,94)', 'rgba(34,197,94,0.15)'],
            HardwareEnergy::ROLE_BATTERY => ['rgb(245,158,11)', 'rgba(245,158,11,0.15)'],
            default => ['rgb(59,130,246)', 'rgba(59,130,246,0.15)'],
        };

        return [
            'datasets' => [[
                'label' => 'Potencia media (W)',
                'data' => $valores,
                'borderColor' => $borde,
                'backgroundColor' => $fondo,
                'fill' => true,
                'tension' => 0.3,
                'pointRadius' => 0,
                'borderWidth' => 2,
                'spanGaps' => false,
            ]],
            'labels' => $horas,
        ];
    }

    /**
     * Potencia media de cada hora, sumando los elementos que tenga el aparato
     * con ese papel: un medidor con tres consumos enseña el total de los tres.
     *
     * Se agrupa en la base de datos y no en PHP: son miles de lecturas por
     * semana y traerlas todas para sumarlas aquí no tiene sentido.
     *
     * @return array<string, float>
     */
    private function potenciaPorHora(Carbon $desde): array
    {
        if ($this->deviceId === null) {
            return [];
        }

        $elementos = HardwareEnergy::query()
            ->where('hardware_device_id', $this->deviceId)
            ->where('role', $this->papel)
            ->pluck('id');

        if ($elementos->isEmpty()) {
            return [];
        }

        /** @var array<string, float> $filas */
        $filas = HardwareEnergyReading::query()
            ->whereIn('hardware_energy_id', $elementos)
            ->where('created_at', '>=', $desde)
            ->reliable()
            ->whereNotNull('power')
            ->toBase()
            ->selectRaw("to_char(date_trunc('hour', created_at), 'YYYY-MM-DD HH24') AS hora, avg(power) AS media")
            ->groupBy('hora')
            ->pluck('media', 'hora')
            ->map(static fn ($media): float => (float) $media)
            ->all();

        return $filas;
    }
}
