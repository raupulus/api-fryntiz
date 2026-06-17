<?php

declare(strict_types=1);

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

/**
 * Trait para aplicar filtros dinámicos desde request a queries.
 */
trait Filterable
{
    /**
     * Scope para aplicar filtros desde un array asociativo.
     */
    public function scopeFilter(Builder $query, array $filters): Builder
    {
        foreach ($filters as $field => $value) {
            if ($value !== null && $value !== '') {
                if (is_array($value)) {
                    $query->whereIn($field, $value);
                } else {
                    $query->where($field, $value);
                }
            }
        }

        return $query;
    }

    /**
     * Scope para búsqueda parcial en campos de texto.
     */
    public function scopeSearch(Builder $query, ?string $term, array $columns = ['name', 'title']): Builder
    {
        if (empty($term)) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($term, $columns) {
            foreach ($columns as $column) {
                $q->orWhere($column, 'ILIKE', "%{$term}%");
            }
        });
    }
}
