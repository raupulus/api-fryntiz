<?php

declare(strict_types=1);

namespace Tests\Unit\Api;

use App\Http\Api\CollectionQuery;
use App\Models\Content\Content;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Test;
use ReflectionMethod;
use Tests\TestCase;

/**
 * El orden de `CollectionQuery` con una columna que hunde sus filas (D52): sin
 * `?sort=`, delante del orden por defecto; con `?sort=`, detrás del pedido.
 */
class CollectionQuerySortTest extends TestCase
{
    private function orderClause(CollectionQuery $collection, string $query = ''): string
    {
        $builder = Content::query();
        $method = new ReflectionMethod($collection, 'applySorting');
        $method->invoke($collection, $builder, Request::create('/?'.$query));

        return substr($builder->toSql(), strpos($builder->toSql(), 'order by'));
    }

    #[Test]
    public function the_sink_column_goes_before_the_default_order_and_after_the_requested_one(): void
    {
        $collection = new CollectionQuery(sortable: ['title', 'published_at'], defaultSortColumn: 'published_at', sinkColumn: 'is_obsolete');

        $this->assertSame(
            'order by "contents"."is_obsolete" asc nulls last, "contents"."published_at" desc nulls last',
            $this->orderClause($collection),
        );
        $this->assertSame(
            'order by "contents"."title" asc nulls last, "contents"."is_obsolete" asc nulls last',
            $this->orderClause($collection, 'sort=title'),
        );
        $this->assertSame(
            'order by "contents"."is_obsolete" asc nulls last, "contents"."published_at" desc nulls last',
            $this->orderClause($collection, 'sort=-noexiste'),
            'Un orden que no vale es como no pedir ninguno.',
        );
    }

    #[Test]
    public function without_a_sink_column_the_order_does_not_change(): void
    {
        $collection = new CollectionQuery(sortable: ['title'], defaultSortColumn: 'published_at');

        $this->assertSame('order by "contents"."published_at" desc nulls last', $this->orderClause($collection));
        $this->assertSame('order by "contents"."title" asc nulls last', $this->orderClause($collection, 'sort=title'));
    }
}
