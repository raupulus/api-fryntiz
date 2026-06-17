<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\BaseModels\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Modelo para la cola de impresión.
 *
 * @property int $id
 * @property int $user_id Relación con el usuario que creó el registro
 * @property int $printer_id Relación la impresora
 * @property string|null $note Notas sobre la impresión
 * @property string|null $content content
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Printer $printer
 * @property-read User|null $user
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrinterStack newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrinterStack newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrinterStack query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrinterStack whereContent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrinterStack whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrinterStack whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrinterStack whereNote($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrinterStack wherePrinterId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrinterStack whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrinterStack whereUserId($value)
 *
 * @mixin \Eloquent
 */
class PrinterStack extends BaseModel
{
    protected $table = 'printer_stack';

    protected $fillable = [
        'user_id',
        'printer_id',
        'note',
        'content',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function printer(): BelongsTo
    {
        return $this->belongsTo(Printer::class, 'printer_id', 'id');
    }
}
