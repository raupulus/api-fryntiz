<?php

declare(strict_types=1);

namespace App\Http\Api;

use App\Exceptions\JsonValidationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Validator;

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
 *
 * Y el VALOR también se comprueba (AR-E01). La lista blanca de columnas evita
 * que se filtre por una columna cualquiera, pero durante un tiempo el valor
 * llegaba tal cual a `where()`, y PostgreSQL es estricto con los tipos: un
 * texto donde espera un `timestamp` no devuelve cero filas, **lanza
 * `SQLSTATE 22007`**. O sea un 500 que provocaba cualquiera, sin autenticar,
 * en todas las colecciones públicas:
 *
 *     ?created_at=abc            → 500
 *     ?created_at[gte]=zzz       → 500
 *     ?created_at[gte][]=1       → 500  (un array como tercer argumento de where)
 *
 * Ahora un valor que no case con el tipo de su columna responde **422 con el
 * envelope de la API**, igual que cualquier otro error de validación. Es
 * información útil para quien llama y deja de ser una palanca para tumbar el
 * servidor.
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
     * Reglas de validación por tipo de columna.
     *
     * `date` acepta lo que acepte `strtotime()`, que es lo que luego entiende
     * PostgreSQL: ISO 8601, `2026-09-02`, `2026-09-02 14:30:00`.
     */
    private const TYPE_RULES = [
        'date' => 'date',
        'integer' => 'integer',
        'boolean' => 'boolean',
        'string' => 'string',
    ];

    /**
     * @param  array<int, string>  $filterable  Columnas por las que se puede filtrar.
     * @param  array<int, string>  $sortable  Columnas por las que se puede ordenar.
     * @param  string|null  $defaultSortColumn  Columna del orden por defecto.
     * @param  bool  $defaultSortDescending  Si el orden por defecto es descendente.
     * @param  array<string, string>  $types  Tipo de una columna cuando el nombre no lo delata.
     */
    public function __construct(
        private readonly array $filterable = [],
        private readonly array $sortable = [],
        private readonly ?string $defaultSortColumn = 'created_at',
        private readonly bool $defaultSortDescending = true,
        private readonly array $types = [],
    ) {}

    /**
     * Tipo de una columna filtrable.
     *
     * Se deduce del nombre, que en este esquema es fiable y evita tener que
     * repetir el tipo en los once sitios donde se construye un
     * `CollectionQuery`. Si algún día una columna no encaja en la convención,
     * se declara en `$types` y manda eso.
     *
     *   *_at                        → fecha    (created_at, last_seen_at, published_at…)
     *   id, *_id                    → entero   (type_id, hardware_device_id…)
     *   is_*, has_*                 → booleano (is_featured…)
     *   el resto                    → texto    (name, slug, domain, icao…)
     */
    private function typeOf(string $field): string
    {
        if (isset($this->types[$field])) {
            return $this->types[$field];
        }

        return match (true) {
            str_ends_with($field, '_at') => 'date',
            $field === 'id' || str_ends_with($field, '_id') => 'integer',
            str_starts_with($field, 'is_') || str_starts_with($field, 'has_') => 'boolean',
            default => 'string',
        };
    }

    /**
     * Comprueba los valores de los filtros antes de tocar la consulta.
     *
     * Se valida ANTES de construir nada: si un valor no vale, la petición se
     * responde con un 422 y no llega a la base de datos.
     *
     * @throws JsonValidationException
     */
    private function validateFilters(Request $request): void
    {
        $data = [];
        $rules = [];

        // `from`/`to` son el alias histórico del rango sobre created_at.
        foreach (['from', 'to'] as $parameter) {
            if ($this->present($request, $parameter) && in_array('created_at', $this->filterable, true)) {
                $data[$parameter] = $request->query($parameter);
                $rules[$parameter] = ['date'];
            }
        }

        foreach ($this->filterable as $field) {
            if (! $this->present($request, $field)) {
                continue;
            }

            $value = $request->query($field);
            $rule = self::TYPE_RULES[$this->typeOf($field)];

            // ?campo[gte]=…&campo[lte]=…
            //
            // El array se pasa al validador tal cual y las reglas se declaran
            // con la notación de punto, que es como Laravel baja un nivel.
            if (is_array($value)) {
                $operadores = array_intersect_key($value, self::OPERATORS);

                if ($operadores === []) {
                    // Sólo operadores desconocidos: se ignora entero, igual que
                    // un campo que no está en la lista blanca.
                    continue;
                }

                $data[$field] = $operadores;

                foreach (array_keys($operadores) as $operador) {
                    // `?campo[gte][]=1` metía un array como tercer argumento de
                    // `where()`, que revienta antes incluso de llegar a los
                    // tipos de PostgreSQL. `prohibited` da un 422 con un mensaje
                    // en vez de un 500 con un stack trace.
                    $rules["{$field}.{$operador}"] = is_array($operadores[$operador])
                        ? ['prohibited']
                        : [$rule];
                }

                continue;
            }

            // ?campo=a,b,c  →  WHERE campo IN (a, b, c)
            if (is_string($value) && str_contains($value, ',')) {
                $data[$field] = $this->splitList($value);
                $rules["{$field}.*"] = [$rule];

                continue;
            }

            $data[$field] = $value;
            $rules[$field] = [$rule];
        }

        // La paginación también: `?per_page=abc` no debe pasar por bueno en
        // silencio y devolver la página por defecto como si nada.
        foreach (['page', 'per_page'] as $parameter) {
            if ($this->present($request, $parameter)) {
                $data[$parameter] = $request->query($parameter);
                $rules[$parameter] = ['integer', 'min:1'];
            }
        }

        if ($rules === []) {
            return;
        }

        // «El campo created at.gte está prohibido» no le dice nada a nadie. El
        // caso real es `?campo[gte][]=1`, o sea una lista donde va un valor.
        $messages = [];

        foreach (array_keys($rules) as $atributo) {
            $messages[$atributo.'.prohibited'] = 'El filtro «:attribute» admite un solo valor, no una lista.';
        }

        $validator = Validator::make($data, $rules, $messages);

        if ($validator->fails()) {
            throw new JsonValidationException($validator);
        }
    }

    /**
     * ¿Viene el parámetro y con algo dentro?
     *
     * Un parámetro vacío (`?created_at=`) se ignora en vez de dar 422: las webs
     * mandan campos de formulario en blanco continuamente y no es un error.
     */
    private function present(Request $request, string $parameter): bool
    {
        $value = $request->query($parameter);

        return $value !== null && $value !== '' && $value !== [];
    }

    /**
     * Trocea una lista separada por comas, sin huecos.
     *
     * @return list<string>
     */
    private function splitList(string $value): array
    {
        return array_values(array_filter(
            array_map('trim', explode(',', $value)),
            static fn (string $element): bool => $element !== ''
        ));
    }

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
        $this->validateFilters($request);
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
                    if (isset(self::OPERATORS[$key]) && is_scalar($limit) && $limit !== '') {
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
                $query->whereIn($field, $this->splitList($value));

                continue;
            }

            // Un valor no escalar no llega aquí: `validateFilters()` lo ha
            // rechazado con un 422. Se comprueba igualmente porque este método
            // es el que toca la consulta y no debe fiarse de que alguien haya
            // validado antes.
            if (! is_scalar($value)) {
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
