<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\BaseModels\BaseModel;
use Illuminate\Support\Carbon;

/**
 * Modelo para tipos de impresora disponibles.
 *
 * @property int $id
 * @property string $name Nombre del tipo de impresora
 * @property string $slug Nombre del tipo de impresora
 * @property string|null $description Descripción
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrinterAvailableType newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrinterAvailableType newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrinterAvailableType query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrinterAvailableType whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrinterAvailableType whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrinterAvailableType whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrinterAvailableType whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrinterAvailableType whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrinterAvailableType whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class PrinterAvailableType extends BaseModel
{
    protected $table = 'printer_available_types';

    protected $fillable = ['name', 'slug', 'description'];
}
