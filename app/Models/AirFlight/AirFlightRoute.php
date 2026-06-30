<?php

declare(strict_types=1);

namespace App\Models\AirFlight;

use App\Models\BaseModels\BaseModel;
use App\Models\Hardware\HardwareDevice;
use App\Models\User;
use App\Traits\BelongsToHardwareDevice;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Class AirFlightRoute
 *
 * Representa las rutas que recorren los aviones.
 *
 * @property int $id
 * @property int|null $user_id Usuario asociado
 * @property int $airplane_id
 * @property int|null $hardware_device_id Dispositivo asociado
 * @property string|null $squawk Código de transpondedor seleccionado (Señal squawk en representación octal)
 * @property string|null $flight Nombre del vuelo
 * @property float|null $lat Latitud
 * @property float|null $lon Longitud
 * @property float|null $altitude Altitud en metros
 * @property float|null $vert_rate Velocidad vertical en metros por segundo
 * @property int|null $track Track verdadero sobre el suelo en grados (0-359)
 * @property float|null $speed Velocidad en metros por segundos
 * @property string|null $seen_at Momento en el que recibió el último mensaje de este avión
 * @property int|null $messages Número total de mensajes de modo s recibidos desde esta aeronave
 * @property float|null $rssi rssi promedio reciente (potencia de señal), en dbfs; esto siempre será negativo.
 * @property string|null $emergency Indica si hay señal de emergencia
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read AirFlightAirPlane $airplane
 * @property-read HardwareDevice|null $hardwareDevice
 * @property-read User|null $user
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AirFlightRoute newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AirFlightRoute newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AirFlightRoute query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AirFlightRoute whereAirplaneId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AirFlightRoute whereAltitude($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AirFlightRoute whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AirFlightRoute whereEmergency($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AirFlightRoute whereFlight($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AirFlightRoute whereHardwareDeviceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AirFlightRoute whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AirFlightRoute whereLat($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AirFlightRoute whereLon($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AirFlightRoute whereMessages($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AirFlightRoute whereRssi($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AirFlightRoute whereSeenAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AirFlightRoute whereSpeed($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AirFlightRoute whereSquawk($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AirFlightRoute whereTrack($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AirFlightRoute whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AirFlightRoute whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AirFlightRoute whereVertRate($value)
 *
 * @mixin \Eloquent
 */
class AirFlightRoute extends BaseModel
{
    use BelongsToHardwareDevice;

    protected $table = 'airflight_routes';

    public const HISTORY_LENGTH = 30;

    protected $fillable = [
        'airplane_id',
        'hardware_device_id',
        'user_id',
        'squawk',
        'flight',
        'lat',
        'lon',
        'altitude',
        'vert_rate',
        'track',
        'speed',
        'seen_at',
        'messages',
        'rssi',
        'emergency',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function airplane(): BelongsTo
    {
        return $this->belongsTo(AirFlightAirPlane::class, 'airplane_id', 'id');
    }
}
