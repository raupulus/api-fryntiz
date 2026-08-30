<?php

declare(strict_types=1);

namespace App\Http\Api;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Paginación, filtros y orden para las colecciones de la API V2.
 *
 * Antes cada módulo lo resolvía a su manera, y varios ni lo resolvían: por
 * ejemplo `GET /weatherstation/temperature` hacía un `->get()` sobre una tabla
 * de serie temporal, sin paginar y sin filtrar por estación. Con una estación y
 * pocos meses de datos pasa desapercibido; con 2,6 millones de filas no.
 *
 * Contrato (REST-API-V2.md §4):
 *
 *   ?page=1&per_page=25            paginación, máximo 100 por página
 *   ?campo=valor                   igualdad
 *   ?campo[gte]=x&campo[lte]=y     rango
 *   ?from=&to=                     alias de created_at[gte] / created_at[lte]
 *   ?sort=-created_at              orden; el guion es descendente
 *
 * Sólo se aceptan los campos que el controlador declara. Un parámetro que no
 * esté en la lista se ignora: ni filtra ni revienta, y sobre todo no deja al
 * cliente ordenar o filtrar por una columna arbitraria.
 */
class CollectionQuery
{
    public const DEFAULT_PER_PAGE = 25;

    public const MAX_PER_PAGE = 100;

    /** Operadores admitidos en los filtros de rango. */
    private const OPERATORS = [
        'gte' => '>=',
        'gt' => '>',
        'lte' => '<=',
        'lt' => '<',
        'ne' => '!=',
    ];

    /**
     * @param  array<int, string>  $filterable  Columnas por las que se puede filtrar.
     * @param  array<int, string>  $sortable  Columnas por las que se puede ordenar.
     * @param  string|null  $defaultSortColumn  Columna del orden por defecto.
     * @param  bool  $defaultSortDescending  Si el orden por defecto es descendente.
     */
    public function __construct(
        private readonly array $filterable = [],
        private readonly array $sortable = [],
        private readonly ?string $defaultSortColumn = 'created_at',
        private readonly bool $defaultSortDescending = true,
    ) {}

    /**
     * Aplica filtros y orden, y devuelve la página pedida.
     *
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  Builder<TModel>  $query
     * @return LengthAwarePaginator<int, TModel>
     */
    public function paginate(Builder $query, Request $request): LengthAwarePaginator
    {
        $this->applyFilters($query, $request);
        $this->applySorting($query, $request);

        return $query->paginate($this->perPage($request))->withQueryString();
    }

    /**
     * Tamaño de página pedido, acotado.
     */
    public function perPage(Request $request): int
    {
        $requested = (int) $request->query('per_page', (string) self::DEFAULT_PER_PAGE);

        if ($requested < 1) {
            return self::DEFAULT_PER_PAGE;
        }

        return min($requested, self::MAX_PER_PAGE);
    }

    /**
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query
     */
    private function applyFilters(Builder $query, Request $request): void
    {
        // `from`/`to` son el alias histórico del rango sobre created_at. Se
        // conservan porque las webs ya los usan.
        $alias = [
            'from' => ['created_at', '>='],
            'to' => ['created_at', '<='],
        ];

        foreach ($alias as $parameter => [$column, $operator]) {
            $value = $request->query($parameter);

            if (is_string($value) && $value !== '' && in_array($column, $this->filterable, true)) {
                $query->where($column, $operator, $value);
            }
        }

        foreach ($this->filterable as $field) {
            if (! $request->has($field)) {
                continue;
            }

            $value = $request->query($field);

            // ?campo[gte]=...&campo[lte]=...
            if (is_array($value)) {
                foreach ($value as $key => $limit) {
                    if (isset(self::OPERATORS[$key]) && $limit !== '' && $limit !== null) {
                        $query->where($field, self::OPERATORS[$key], $limit);
                    }
                }

                continue;
            }

            if ($value === null || $value === '') {
                continue;
            }

            // ?campo=a,b,c  →  WHERE campo IN (a, b, c)
            if (is_string($value) && str_contains($value, ',')) {
                $query->whereIn($field, array_filter(array_map('trim', explode(',', $value)), fn ($v) => $v !== ''));

                continue;
            }

            $query->where($field, $value);
        }
    }

    /**
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query
     */
    private function applySorting(Builder $query, Request $request): void
    {
        $sort = $request->query('sort');

        if (is_string($sort) && $sort !== '') {
            $applied = false;

            foreach (explode(',', $sort) as $field) {
                $field = trim($field);
                $descending = str_starts_with($field, '-');
                $column = ltrim($field, '-+');

                if ($column === '' || ! in_array($column, $this->sortable, true)) {
                    continue;
                }

                $this->orderByColumn($query, $column, $descending);
                $applied = true;
            }

            if ($applied) {
                return;
            }
        }

        if ($this->defaultSortColumn !== null) {
            $this->orderByColumn($query, $this->defaultSortColumn, $this->defaultSortDescending);
        }
    }

    /**
     * Orden con `NULLS LAST` explícito.
     *
     * En PostgreSQL `ORDER BY columna DESC` pone los NULL **primero**, que es lo
     * contrario de lo que se espera: pedir «lo más reciente» devolvía arriba las
     * filas sin fecha.
     *
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query
     */
    private function orderByColumn(Builder $query, string $column, bool $descending): void
    {
        $direction = $descending ? 'desc' : 'asc';
        $table = $query->getModel()->getTable();
        $safeColumn = $query->getGrammar()->wrap($table.'.'.$column);

        $query->orderByRaw("{$safeColumn} {$direction} nulls last");
    }
}
