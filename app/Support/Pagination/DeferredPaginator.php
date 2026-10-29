<?php

declare(strict_types=1);

namespace App\Support\Pagination;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Cache;

/**
 * Paginador con Deferred Join (Late Row Lookup) y conteo cacheado para tablas
 * de series temporales con millones de registros.
 *
 * Resuelve los dos cuellos de botella de `->paginate()` sobre tablas masivas en
 * PostgreSQL sin recortar ni bloquear el acceso a ninguna página:
 *
 *  1. **`COUNT(*)` cacheado:** en PostgreSQL, `COUNT(*)` escanea el mapa de
 *     visibilidad de toda la tabla en cada petición (~350–450 ms en 3,4M de
 *     filas). Aquí el total se guarda en caché durante `$countTtlSeconds`
 *     (1 hora por defecto) bajo una clave específica para la consulta.
 *  2. **Deferred Join (Late Row Lookup):** en vez de ordenar y desplazar filas
 *     anchas en memoria/disco (`OFFSET N`), una primera subconsulta selecciona
 *     únicamente `id` ordenada por `($orderColumn DESC, id DESC)` aprovechando
 *     el índice compuesto mediante un *Index-Only Scan*, y una segunda consulta
 *     hidrata las columnas completas solo para los `$perPage` identificadores
 *     de esa página (`WHERE id IN (...)`).
 */
final class DeferredPaginator
{
    /**
     * Pagina una consulta Eloquent o Query Builder mediante Deferred Join y
     * conteo cacheado, devolviendo un `LengthAwarePaginator` estándar con
     * todos los enlaces numéricos de página.
     *
     * @param  EloquentBuilder<*>|QueryBuilder  $baseQuery
     * @param  int  $perPage  Registros por página.
     * @param  string  $cacheKey  Clave única para cachear el total de registros.
     * @param  list<string>  $columns  Columnas a recuperar en la consulta final.
     * @param  string  $orderColumn  Columna temporal principal de ordenación descendente.
     * @param  int  $countTtlSeconds  Segundos de caché para el conteo total (por defecto 3600).
     */
    public static function paginate(
        EloquentBuilder|QueryBuilder $baseQuery,
        int $perPage,
        string $cacheKey,
        array $columns = ['*'],
        string $orderColumn = 'created_at',
        int $countTtlSeconds = 3600,
    ): LengthAwarePaginator {
        $page = max(1, Paginator::resolveCurrentPage('page'));

        $total = (int) Cache::remember(
            $cacheKey,
            $countTtlSeconds,
            static fn (): int => (clone $baseQuery)->count()
        );

        $offset = ($page - 1) * $perPage;

        if ($total === 0 || $offset >= $total) {
            return (new LengthAwarePaginator(
                collect(),
                $total,
                $perPage,
                $page,
                [
                    'path' => Paginator::resolveCurrentPath(),
                    'pageName' => 'page',
                ]
            ))->withQueryString();
        }

        // Fase 1: Index-Only Scan sobre (orderColumn, id) para obtener solo los IDs de la página.
        $ids = (clone $baseQuery)
            ->select('id')
            ->orderByDesc($orderColumn)
            ->orderByDesc('id')
            ->offset($offset)
            ->limit($perPage)
            ->pluck('id')
            ->all();

        if ($ids === []) {
            $items = collect();
        } else {
            // Fase 2: Hidratación de las columnas completas únicamente para esos IDs.
            $items = (clone $baseQuery)
                ->select($columns)
                ->whereIn('id', $ids)
                ->orderByDesc($orderColumn)
                ->orderByDesc('id')
                ->get();
        }

        return (new LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $page,
            [
                'path' => Paginator::resolveCurrentPath(),
                'pageName' => 'page',
            ]
        ))->withQueryString();
    }
}
