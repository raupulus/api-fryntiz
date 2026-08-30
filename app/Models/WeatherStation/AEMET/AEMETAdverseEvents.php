<?php

declare(strict_types=1);

namespace App\Models\WeatherStation\AEMET;

use App\Models\BaseModels\BaseModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;

/**
 * Un aviso de fenómeno meteorológico adverso de AEMET (CAP 1.2).
 *
 * Una fila por **aviso y zona**: un mismo aviso cubre varias comarcas y cada una
 * es una fila, porque lo que se pregunta es «¿hay aviso en mi zona?».
 *
 * Reglas que no son opcionales, todas verificadas sobre el paquete nacional
 * (`docs/apis/aemet/04-avisos-y-riesgos.md`):
 *
 * - **Filtrar siempre por `expires_at`.** AEMET no emite `Cancel`: para retirar
 *   un aviso manda otro **que nace caducado** (`expires` = `sent`). Fiarse de
 *   `msg_type` enseña un aviso que ya no existe. Para eso está `scopeCurrent()`.
 * - **El nivel verde no es un aviso.** No llega hasta aquí: se descarta al leer
 *   el paquete. Si algún día se quisiera guardar, `severity = Minor`.
 * - **`event` ya viene redactado en español y con el nivel dentro**: es la
 *   cadena que conviene enseñar, no el código.
 *
 * @property int $id
 * @property string|null $identifier Identificador único del aviso en CAP
 * @property string $name Nombre de la zona
 * @property string $slug Slug del nombre de la zona
 * @property string|null $geocode Código de zona: CCAA+provincia INE+comarca
 * @property string|null $language Idioma del bloque <info> guardado
 * @property string|null $status Actual | Test
 * @property string|null $msg_type Alert | Update | Cancel
 * @property string|null $event Texto del aviso, con el nivel dentro
 * @property string|null $event_code Fenómeno, «PR;Lluvias»
 * @property string|null $severity Minor | Moderate | Severe | Extreme
 * @property string|null $level amarillo | naranja | rojo
 * @property string|null $urgency Immediate | Expected | Future
 * @property string|null $certainty Observed | Likely | Possible
 * @property string|null $response_type Monitor | None
 * @property string|null $probability 10%-40% | 40%-70% | mayor 70%
 * @property string|null $parameter «código;descripción;umbral»
 * @property string|null $headline
 * @property string|null $description
 * @property string|null $instruction
 * @property array|null $polygons Lista de polígonos, pares «lat,lon»
 * @property string|null $others_fields_json Etiquetas de <info> sin columna propia
 * @property Carbon|null $effective_at
 * @property Carbon|null $onset_at
 * @property Carbon|null $expires_at
 * @property Carbon $read_at Emisión del aviso (`sent`), en UTC
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static Builder<static>|AEMETAdverseEvents newModelQuery()
 * @method static Builder<static>|AEMETAdverseEvents newQuery()
 * @method static Builder<static>|AEMETAdverseEvents query()
 *
 * @mixin \Eloquent
 */
class AEMETAdverseEvents extends BaseModel
{
    use HasFactory;

    /**
     * Gravedad CAP ordenada de menos a más, para poder comparar y ordenar.
     *
     * La correlación con el nivel de color de AEMET es exacta y está verificada
     * sobre los 252 ficheros del paquete nacional.
     *
     * @var array<string,int>
     */
    public const SEVERITY = [
        'Minor' => 0,     // verde — no es un aviso
        'Moderate' => 1,  // amarillo
        'Severe' => 2,    // naranja
        'Extreme' => 3,   // rojo
    ];

    protected $table = 'meteorology_aemet_adverse_events';

    protected $fillable = [
        'identifier', 'name', 'slug', 'geocode', 'language', 'status', 'msg_type',
        'event', 'event_code', 'severity', 'level', 'urgency', 'certainty',
        'response_type', 'probability', 'parameter',
        'headline', 'description', 'instruction',
        'polygons', 'others_fields_json',
        'effective_at', 'onset_at', 'expires_at', 'read_at',
    ];

    protected $casts = [
        'polygons' => 'array',
        'effective_at' => 'datetime',
        'onset_at' => 'datetime',
        'expires_at' => 'datetime',
        'read_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Los que siguen vigentes.
     *
     * Un aviso sin `expires_at` se considera vigente: es preferible enseñar de
     * más que esconder un aviso rojo por no traer fecha de fin.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeCurrent(Builder $query): Builder
    {
        return $query->where(fn (Builder $q) => $q
            ->whereNull('expires_at')
            ->orWhere('expires_at', '>', now())
        );
    }

    /**
     * Los de una zona, aceptando el código exacto o un prefijo: `6111` es toda
     * la provincia de Cádiz.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeInZone(Builder $query, string $zone): Builder
    {
        return $query->where('geocode', 'like', $zone.'%');
    }

    /**
     * De naranja para arriba, que es lo que justifica avisar a alguien.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeSevere(Builder $query): Builder
    {
        return $query->whereIn('severity', ['Severe', 'Extreme']);
    }

    /**
     * ¿Está ocurriendo ya, o es una previsión?
     */
    public function isRunning(): bool
    {
        return $this->onset_at !== null && $this->onset_at->isPast()
            && ($this->expires_at === null || $this->expires_at->isFuture());
    }

    /**
     * Comprueba que los datos coinciden con lo que puede guardarse.
     *
     * @param  array<string,mixed>  $datas
     */
    public static function validation(array $datas): bool
    {
        $validator = Validator::make($datas, [
            'name' => 'required|max:255',
            'slug' => 'required|max:255',
            'geocode' => 'nullable|string|max:16',
            'severity' => 'nullable|string|max:32',
            'polygons' => 'nullable|array',
            'others_fields_json' => 'nullable|string',
            'read_at' => 'required',
        ]);

        return ! $validator->fails();
    }

    /**
     * Guarda lo que devuelve el lector del paquete CAP.
     *
     * La clave natural es `identifier` + `geocode`. Antes era zona y fecha de
     * emisión: dos avisos distintos de la misma zona emitidos en el mismo
     * segundo —viento y lluvia a la vez, que es lo normal en un temporal— se
     * machacaban entre ellos y sólo sobrevivía uno.
     *
     * @param  array<int,array<string,mixed>>  $apiResponse
     * @return array<int,self>|null
     */
    public static function saveFromApi(array $apiResponse): ?array
    {
        $result = [];

        foreach ($apiResponse as $register) {
            if (! self::validation($register)) {
                continue;
            }

            // Sin `identifier` no hay clave natural y se cae a lo que hay: zona,
            // fenómeno y emisión. No debería pasar, pero un aviso sin id es
            // mejor guardarlo mal que perderlo.
            $key = ! empty($register['identifier'])
                ? [
                    'identifier' => $register['identifier'],
                    'geocode' => $register['geocode'] ?? null,
                ]
                : [
                    'slug' => $register['slug'],
                    'event_code' => $register['event_code'] ?? null,
                    'read_at' => $register['read_at'],
                ];

            $result[] = self::updateOrCreate($key, $register);
        }

        return $result === [] ? null : $result;
    }
}
