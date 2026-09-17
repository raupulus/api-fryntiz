<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PrintJobFormatEnum;
use App\Enums\PrintJobStatusEnum;
use App\Models\BaseModels\BaseModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Modelo para la cola de impresión física.
 *
 * @property int $id
 * @property int|null $user_id Relación con el usuario emisor (opcional para peticiones de sistema)
 * @property int $printer_id Relación con la impresora física
 * @property string|null $note Notas descriptivas sobre la impresión
 * @property string|null $content Contenido o payload a imprimir
 * @property PrintJobStatusEnum $status Estado actual del trabajo
 * @property PrintJobFormatEnum $format Formato del contenido
 * @property int $priority Prioridad (mayor número se imprime antes)
 * @property int $attempts Número de intentos de ejecución
 * @property int $print_count Veces que se ha confirmado la impresión física exitosa
 * @property bool $is_favorite Marcado como plantilla o favorito para reimpresión recurrente
 * @property string|null $error_message Mensaje de error en caso de fallo
 * @property Carbon|null $printed_at Fecha de la última impresión exitosa confirmada
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Printer $printer
 * @property-read User|null $user
 *
 * @method static Builder<static>|PrinterStack newModelQuery()
 * @method static Builder<static>|PrinterStack newQuery()
 * @method static Builder<static>|PrinterStack query()
 * @method static Builder<static>|PrinterStack pending()
 * @method static Builder<static>|PrinterStack favorites()
 * @method static Builder<static>|PrinterStack nextInQueue()
 *
 * @mixin \Eloquent
 */
class PrinterStack extends BaseModel
{
    use HasFactory;

    protected $table = 'printer_stack';

    protected $fillable = [
        'user_id',
        'printer_id',
        'note',
        'content',
        'status',
        'format',
        'priority',
        'attempts',
        'print_count',
        'is_favorite',
        'error_message',
        'printed_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => PrintJobStatusEnum::class,
            'format' => PrintJobFormatEnum::class,
            'priority' => 'integer',
            'attempts' => 'integer',
            'print_count' => 'integer',
            'is_favorite' => 'boolean',
            'printed_at' => 'datetime',
        ];
    }

    /**
     * Usuario que encoló el trabajo.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * Impresora a la que pertenece el trabajo.
     */
    public function printer(): BelongsTo
    {
        return $this->belongsTo(Printer::class, 'printer_id', 'id');
    }

    /**
     * Scope para trabajos pendientes.
     *
     * @param  Builder<PrinterStack>  $query
     * @return Builder<PrinterStack>
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', PrintJobStatusEnum::Pending);
    }

    /**
     * Scope para trabajos favoritos.
     *
     * @param  Builder<PrinterStack>  $query
     * @return Builder<PrinterStack>
     */
    public function scopeFavorites(Builder $query): Builder
    {
        return $query->where('is_favorite', true);
    }

    /**
     * Scope para seleccionar el siguiente trabajo disponible ordenado por prioridad y antigüedad.
     *
     * @param  Builder<PrinterStack>  $query
     * @return Builder<PrinterStack>
     */
    public function scopeNextInQueue(Builder $query): Builder
    {
        return $query->where('status', PrintJobStatusEnum::Pending)
            ->orderByDesc('priority')
            ->orderBy('created_at');
    }
}
