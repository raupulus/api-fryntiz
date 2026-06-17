<?php

namespace App\Models\Content;

use App\Models\BaseModels\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Class ContentAvailableStatus
 *
 * @property int $id
 * @property string $name Nombre del formato de edición
 * @property string|null $description Descripción del formato de edición
 * @property string $type Tipo de formato de edición. Ej: texto plano, markdown, html, latex, json, hoja de cálculo...
 * @property string $extension Extensión del formato de edición. Ej md, html, txt, doc, docx, xls, xlsx, json...
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentAvailablePageRaw newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentAvailablePageRaw newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentAvailablePageRaw query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentAvailablePageRaw whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentAvailablePageRaw whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentAvailablePageRaw whereExtension($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentAvailablePageRaw whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentAvailablePageRaw whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentAvailablePageRaw whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentAvailablePageRaw whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class ContentAvailablePageRaw extends BaseModel
{
    use HasFactory;

    protected $table = 'content_available_page_raw';
}
