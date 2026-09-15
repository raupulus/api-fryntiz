<?php

declare(strict_types=1);

namespace App\Models\Gdacs;

use App\Enums\GdacsAlertLevelEnum;
use App\Enums\GdacsEventTypeEnum;
use App\Models\BaseModels\BaseModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property GdacsEventTypeEnum $event_type Código de desastre (EQ, TC, FL, VO, DR, WF)
 * @property int $event_id Id del suceso según GDACS (único por event_type)
 * @property int|null $episode_id Último episodio conocido
 * @property string|null $name Título corto tal cual lo da GDACS
 * @property GdacsAlertLevelEnum $alert_level Nivel de alerta
 * @property bool $is_current Si GDACS lo sigue marcando activo
 * @property Carbon $from_date Inicio del suceso
 * @property Carbon|null $to_date Fin o última fecha conocida
 * @property Carbon $last_modified_at Campo "datemodified" de GDACS
 * @property float $lat Latitud del centro del suceso
 * @property float $lon Longitud del centro del suceso
 * @property float $distance_km Distancia al punto de referencia, calculada al guardar
 * @property float|null $severity_value Valor numérico de severidad
 * @property string|null $severity_unit Unidad de severidad
 * @property string|null $severity_text Texto descriptivo de severidad
 * @property int|null $affected_population Población estimada afectada
 * @property string|null $report_url Enlace al informe de GDACS
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static Builder<static>|GdacsEvent active()
 * @method static Builder<static>|GdacsEvent newModelQuery()
 * @method static Builder<static>|GdacsEvent newQuery()
 * @method static Builder<static>|GdacsEvent query()
 *
 * @mixin \Eloquent
 */
class GdacsEvent extends BaseModel
{
    use HasFactory;

    protected $table = 'gdacs_events';

    protected $fillable = [
        'event_type',
        'event_id',
        'episode_id',
        'name',
        'alert_level',
        'is_current',
        'from_date',
        'to_date',
        'last_modified_at',
        'lat',
        'lon',
        'distance_km',
        'severity_value',
        'severity_unit',
        'severity_text',
        'affected_population',
        'report_url',
    ];

    protected $casts = [
        'event_type' => GdacsEventTypeEnum::class,
        'alert_level' => GdacsAlertLevelEnum::class,
        'is_current' => 'boolean',
        'from_date' => 'datetime',
        'to_date' => 'datetime',
        'last_modified_at' => 'datetime',
        'lat' => 'float',
        'lon' => 'float',
        'distance_km' => 'float',
        'severity_value' => 'float',
        'affected_population' => 'integer',
    ];

    /**
     * Sucesos que GDACS sigue marcando como activos.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_current', true);
    }
}
