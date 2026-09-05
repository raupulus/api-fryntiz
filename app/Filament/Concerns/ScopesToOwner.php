<?php

declare(strict_types=1);

namespace App\Filament\Concerns;

use Illuminate\Database\Eloquent\Builder;

use function auth;

/**
 * Limita la tabla de un recurso de Filament a los registros del usuario que
 * navega. Un administrador sigue viéndolo todo.
 *
 * ## Por qué hace falta, si ya hay policies
 *
 * Porque la policy y la tabla responden a preguntas distintas. `viewAny()`
 * decide si el recurso **existe** para ti; `view()` decide si puedes abrir
 * **una** fila. Filament no ejecuta `view()` fila a fila al pintar el listado:
 * lo que aparece en la tabla es, literalmente, lo que devuelva la consulta.
 *
 * Así que un `Editor` con `viewAny() === true` veía en `/admin` el listado
 * completo de currículums, teclados, ratones, plantas, dispositivos e
 * impresoras de **todos** los usuarios. Podía no llegar a abrir la ficha, pero
 * la columna ya le contaba el nombre del dispositivo, las horas de actividad y
 * a quién pertenece cada cosa (AR-SEC-02). Una fuga de datos no necesita que la
 * ficha abra.
 *
 * El recurso que lo use debe tener columna `user_id`; si la propiedad vive en
 * otra tabla, redefine {@see self::scopeOwnerQuery()}.
 */
trait ScopesToOwner
{
    /**
     * @return Builder<covariant \Illuminate\Database\Eloquent\Model>
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if ($user === null) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->isAdmin()) {
            return $query;
        }

        return static::scopeOwnerQuery($query, (int) $user->id);
    }

    /**
     * Cómo se filtra por propietario en este recurso.
     *
     * Por defecto, la columna `user_id` del propio modelo. Los recursos cuya
     * propiedad cuelga de una relación sobrescriben este método.
     *
     * @param  Builder<covariant \Illuminate\Database\Eloquent\Model>  $query
     * @return Builder<covariant \Illuminate\Database\Eloquent\Model>
     */
    protected static function scopeOwnerQuery(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }
}
