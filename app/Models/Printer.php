<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\BaseModels\BaseModel;
use App\Models\Hardware\HardwareDevice;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Modelo para impresoras.
 *
 * @property int $id
 * @property int $user_id Relación con el usuario propietario de la impresora
 * @property int|null $hardware_device_id Dispositivo asociado
 * @property int $printer_type_id Relación el tipo de impresora
 * @property string $name Nombre del tipo de impresora
 * @property string|null $code Código identificador de la impresora
 * @property string|null $description Descripción
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read HardwareDevice|null $hardwareDevice
 * @property-read Collection<int, PrinterStack> $printStack
 * @property-read int|null $print_stack_count
 * @property-read PrinterAvailableType $printerType
 * @property-read User|null $user
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Printer newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Printer newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Printer query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Printer whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Printer whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Printer whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Printer whereHardwareDeviceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Printer whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Printer whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Printer wherePrinterTypeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Printer whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Printer whereUserId($value)
 *
 * @mixin \Eloquent
 */
class Printer extends BaseModel
{
    protected $table = 'printers';

    protected $fillable = [
        'user_id',
        'hardware_device_id',
        'printer_type_id',
        'name',
        'code',
        'description',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function hardwareDevice(): BelongsTo
    {
        return $this->belongsTo(HardwareDevice::class, 'hardware_device_id', 'id');
    }

    public function printerType(): BelongsTo
    {
        return $this->belongsTo(PrinterAvailableType::class, 'printer_type_id', 'id');
    }

    public function printStack(): HasMany
    {
        return $this->hasMany(PrinterStack::class, 'printer_id', 'id');
    }
}
