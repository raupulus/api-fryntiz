<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PrinterStatusEnum;
use App\Enums\PrinterTypeEnum;
use App\Enums\PrintJobFormatEnum;
use App\Models\BaseModels\BaseModel;
use App\Models\Hardware\HardwareDevice;
use App\Traits\BelongsToHardwareDevice;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Support\Carbon;

/**
 * Modelo para periféricos de impresión física.
 *
 * La propiedad y autenticación se vinculan al hardware host (HardwareDevice).
 *
 * @property int $id
 * @property int $hardware_device_id Dispositivo asociado
 * @property string $name Nombre descriptivo de la impresora
 * @property string|null $code Código identificador de la impresora
 * @property string|null $description Descripción
 * @property PrinterTypeEnum $printer_type Tipo de tecnología
 * @property bool $is_active Habilitada para recibir trabajos
 * @property PrinterStatusEnum $status Último estado reportado
 * @property array<string> $supported_formats Formatos de payload aceptados
 * @property PrintJobFormatEnum $default_format Formato predeterminado
 * @property int $max_payload_kb Tamaño máximo de payload en KB
 * @property int $total_prints_count Odómetro de impresiones confirmadas
 * @property Carbon|null $last_seen_at Último contacto del microcontrolador
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property int|null $pending_jobs_count Cantidad de trabajos pendientes
 * @property-read HardwareDevice $hardwareDevice
 * @property-read Collection<int, PrinterStack> $printStack
 * @property-read int|null $print_stack_count
 * @property-read User|null $user
 *
 * @method static Builder<static>|Printer newModelQuery()
 * @method static Builder<static>|Printer newQuery()
 * @method static Builder<static>|Printer query()
 * @method static Builder<static>|Printer active()
 * @method static Builder<static>|Printer forDevice(int $deviceId)
 *
 * @mixin \Eloquent
 */
class Printer extends BaseModel
{
    use BelongsToHardwareDevice;
    use HasFactory;

    protected $table = 'printers';

    protected $fillable = [
        'hardware_device_id',
        'name',
        'code',
        'description',
        'printer_type',
        'is_active',
        'status',
        'supported_formats',
        'default_format',
        'max_payload_kb',
        'total_prints_count',
        'last_seen_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'printer_type' => PrinterTypeEnum::class,
            'status' => PrinterStatusEnum::class,
            'default_format' => PrintJobFormatEnum::class,
            'supported_formats' => 'array',
            'is_active' => 'boolean',
            'max_payload_kb' => 'integer',
            'total_prints_count' => 'integer',
            'last_seen_at' => 'datetime',
        ];
    }

    /**
     * Propietario de la impresora a través del dispositivo anfitrión.
     */
    public function user(): HasOneThrough
    {
        return $this->hasOneThrough(
            User::class,
            HardwareDevice::class,
            'id',
            'id',
            'hardware_device_id',
            'user_id'
        );
    }

    /**
     * Trabajos en cola de la impresora.
     */
    public function printStack(): HasMany
    {
        return $this->hasMany(PrinterStack::class, 'printer_id', 'id');
    }

    /**
     * Scope para impresoras activas.
     *
     * @param  Builder<Printer>  $query
     * @return Builder<Printer>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Comprueba si la impresora soporta un formato determinado.
     */
    public function supportsFormat(PrintJobFormatEnum|string $format): bool
    {
        $value = $format instanceof PrintJobFormatEnum ? $format->value : $format;
        $supported = is_array($this->supported_formats) ? $this->supported_formats : [];

        return in_array($value, $supported, true);
    }
}
