<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V2;

use App\Models\Category;
use App\Models\Content\ContentCategory;
use App\Models\Platform;
use App\Models\PlatformCategory;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Api\ApiTestCase;
use Tests\Traits\BuildsApiContents;
use Tests\Traits\SeedsProductionContentStatuses;
use Tests\Traits\UsesTemporaryStorage;

/**
 * El menú por categorías de una plataforma: `GET /platforms/{slug}/categories`
 * (raíces con subcategorías y cuántos contenidos publicados tiene cada una) y
 * `?category=` de los contenidos, que en una raíz incluye a sus subcategorías.
 */
class PlatformCategoriesMenuTest extends ApiTestCase
{
    use BuildsApiContents;
    use SeedsProductionContentStatuses;
    use UsesTemporaryStorage;

    private Category $electronics;

    private Category $arduino;

    private Category $linux;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareApiContents();
        $this->electronics = $this->category('Electrónica');
        $this->arduino = $this->category('Arduino', $this->electronics);
        $this->linux = $this->category('Linux');
    }

    private function categories(?Platform $platform = null): array
    {
        return $this->getJson('/api/v2/platforms/'.($platform ?? $this->platform)->slug.'/categories')
            ->assertOk()
            ->json('data');
    }

    #[Test]
    public function the_menu_nests_subcategories_under_their_root_and_counts_published_contents(): void
    {
        $onlyRoot = $this->published(['title' => 'Solo raíz']);
        $onlySub = $this->published(['title' => 'Solo sub']);
        $both = $this->published(['title' => 'Las dos']);
        $this->linkCategory($onlyRoot, $this->electronics);
        $this->linkCategory($onlySub, $this->arduino);
        $this->linkCategory($both, $this->electronics);
        $this->linkCategory($both, $this->arduino);

        $menu = collect($this->categories())->keyBy('slug');

        $this->assertSame(['electronica', 'linux'], $menu->keys()->sort()->values()->all());
        // Tres contenidos distintos: el que tiene las dos no se cuenta dos veces.
        $this->assertSame(3, $menu['electronica']['contents_count']);
        $this->assertSame(0, $menu['linux']['contents_count']);
        $this->assertSame([], $menu['linux']['subcategories']);

        $sub = $menu['electronica']['subcategories'][0];
        $this->assertSame('arduino', $sub['slug']);
        $this->assertSame('electronica', $sub['parent']);
        $this->assertSame(2, $sub['contents_count']);
        $this->assertArrayNotHasKey('id', $menu['electronica']);
        $this->assertArrayNotHasKey('parent_id', $menu['electronica']);
        $this->assertNotEmpty($menu['electronica']['urlImageMicro']);
    }

    #[Test]
    public function drafts_inactive_and_removed_links_do_not_count(): void
    {
        $this->linkCategory($this->published(), $this->linux);
        $this->linkCategory($this->published(['status_id' => 1]), $this->linux);
        // Al crear un publicado el modelo lo activa: se desactiva después.
        $hidden = $this->published();
        $hidden->update(['is_active' => false]);
        $this->linkCategory($hidden, $this->linux);
        $this->linkCategory($this->published(), $this->linux)->delete();

        $menu = collect($this->categories())->keyBy('slug');

        $this->assertSame(1, $menu['linux']['contents_count']);
    }

    #[Test]
    public function a_subcategory_that_is_not_in_the_platform_does_not_show_up(): void
    {
        // Las categorías son globales: la hija existe pero esta plataforma no la usa.
        Category::query()->create(['name' => 'Raspberry', 'slug' => 'raspberry', 'parent_id' => $this->electronics->id, 'priority' => 0]);

        $electronics = collect($this->categories())->firstWhere('slug', 'electronica');

        $this->assertSame(['arduino'], array_column($electronics['subcategories'], 'slug'));
    }

    #[Test]
    public function a_platform_only_sees_its_own_categories(): void
    {
        $other = Platform::factory()->create(['user_id' => $this->admin->id]);
        PlatformCategory::query()->create(['platform_id' => $other->id, 'category_id' => $this->linux->id]);

        $this->assertSame(['linux'], array_column($this->categories($other), 'slug'));
    }

    #[Test]
    public function the_menu_follows_the_categories_the_platform_gets_and_loses(): void
    {
        $this->assertCount(2, $this->categories());

        PlatformCategory::query()->where('platform_id', $this->platform->id)->where('category_id', $this->linux->id)->first()->delete();
        $this->assertSame(['electronica'], array_column($this->categories(), 'slug'));
    }

    #[Test]
    public function filtering_by_a_root_includes_the_contents_of_its_subcategories(): void
    {
        $root = $this->published(['title' => 'Raíz']);
        $sub = $this->published(['title' => 'Sub']);
        $other = $this->published(['title' => 'Otra']);
        $this->linkCategory($root, $this->electronics);
        $this->linkCategory($sub, $this->arduino);
        $this->linkCategory($other, $this->linux);

        $titles = fn (string $category): array => collect($this->getJson("/api/v2/platforms/{$this->platform->slug}/contents?category={$category}")->assertOk()->json('data'))
            ->pluck('title')->sort()->values()->all();

        $this->assertSame(['Raíz', 'Sub'], $titles('electronica'));
        $this->assertSame(['Sub'], $titles('arduino'));
        $this->assertSame(['Otra'], $titles('linux'));
    }

    #[Test]
    public function the_count_matches_what_the_filter_returns(): void
    {
        $this->linkCategory($this->published(), $this->electronics);
        $this->linkCategory($this->published(), $this->arduino);
        $this->linkCategory($this->published(), $this->arduino);

        $count = collect($this->categories())->firstWhere('slug', 'electronica')['contents_count'];
        $listed = $this->getJson("/api/v2/platforms/{$this->platform->slug}/contents?category=electronica")->assertOk()->json('meta.total');

        $this->assertSame($listed, $count);
    }

    #[Test]
    public function removing_a_category_from_the_platform_removes_it_from_its_contents(): void
    {
        $content = $this->published();
        $this->linkCategory($content, $this->linux);
        $row = PlatformCategory::query()->where('platform_id', $this->platform->id)->where('category_id', $this->linux->id)->first();

        $row->delete();

        $this->assertSame(0, ContentCategory::withTrashed()->where('content_id', $content->id)->count());
    }
}
