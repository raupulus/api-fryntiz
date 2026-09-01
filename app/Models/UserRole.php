<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\BaseModels\BaseModel;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name Nombre para control del role en permisos.
 * @property string $display_name Nombre a mostrar
 * @property string $slug Nombre interno del role.
 * @property string|null $description Descripción del funcionamiento del role.
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserRole newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserRole newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserRole query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserRole whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserRole whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserRole whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserRole whereDisplayName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserRole whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserRole whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserRole whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserRole whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class UserRole extends BaseModel
{
    protected $table = 'user_roles';

    /**
     * @var list<string> Campos que admiten asignación masiva.
     *
     * Sin esta lista el modelo NO admitía asignación masiva en absoluto:
     * sin `$fillable` ni `$guarded` propios, Eloquent aplica su
     * `$guarded = ['*']` por defecto y descarta —o rechaza con
     * MassAssignmentException— cualquier `create()` o `fill()`.
     */
    protected $fillable = [
        'name',
        'display_name',
        'slug',
        'description',
    ];
}
