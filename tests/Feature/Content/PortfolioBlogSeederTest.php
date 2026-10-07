<?php

declare(strict_types=1);

namespace Tests\Feature\Content;

use App\Enums\ContentStatusEnum;
use App\Enums\UserRoleEnum;
use App\Models\Content\Content;
use App\Models\Platform;
use App\Models\User;
use Database\Seeders\CategoriesSeeder;
use Database\Seeders\ContentAvailablePageRawSeeder;
use Database\Seeders\ContentAvailableTypesSeeder;
use Database\Seeders\PortfolioBlogSeeder;
use Database\Seeders\RolesTableSeeder;
use Database\Seeders\TagsSeeder;
use Database\Seeders\TechnologiesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\SeedsProductionContentStatuses;

/**
 * Las 20 entradas de ejemplo del blog del portfolio (solo desarrollo).
 */
class PortfolioBlogSeederTest extends TestCase
{
    use RefreshDatabase;
    use SeedsProductionContentStatuses;

    private Platform $platform;

    protected function setUp(): void
    {
        parent::setUp();

        (new RolesTableSeeder)->run();
        $this->seedContentStatusesAsProduction();
        (new ContentAvailableTypesSeeder)->run();
        (new ContentAvailablePageRawSeeder)->run();
        (new CategoriesSeeder)->run();
        (new TagsSeeder)->run();
        (new TechnologiesSeeder)->run();

        $author = User::factory()->create(['role_id' => UserRoleEnum::SuperAdmin->value, 'is_active' => true]);
        $this->platform = Platform::factory()->create(['slug' => PortfolioBlogSeeder::PLATFORM_SLUG, 'user_id' => $author->id]);
    }

    private function blog()
    {
        return Content::query()->where('platform_id', $this->platform->id);
    }

    #[Test]
    public function crea_veinte_entradas_con_al_menos_500_caracteres_en_editorjs(): void
    {
        (new PortfolioBlogSeeder)->run();

        $this->assertSame(20, $this->blog()->count());

        $jsonRawTypeId = (int) DB::table('content_available_page_raw')->where('type', 'json')->value('id');

        foreach ($this->blog()->with('pages.raws')->get() as $content) {
            $this->assertNotEmpty($content->pages, "{$content->slug} sin páginas");

            $text = '';

            foreach ($content->pages as $page) {
                $json = $page->raws->firstWhere('available_page_raw_id', $jsonRawTypeId);
                $this->assertNotNull($json, "{$content->slug}: falta el JSON de Editor.js");

                foreach (json_decode($json->content, true)['blocks'] as $block) {
                    $text .= strip_tags((string) ($block['data']['text'] ?? $block['data']['message'] ?? ''));
                }
            }

            $this->assertGreaterThanOrEqual(500, mb_strlen($text), "{$content->slug} tiene menos de 500 caracteres");
        }
    }

    #[Test]
    public function deja_asociado_todo_lo_que_puede_tener_un_contenido(): void
    {
        (new PortfolioBlogSeeder)->run();

        $ids = $this->blog()->pluck('id');

        foreach (['content_categories', 'content_tags', 'content_technologies', 'content_seo', 'content_metadata', 'content_related', 'content_daily_views'] as $table) {
            $this->assertTrue(DB::table($table)->whereIn('content_id', $ids)->exists(), "Sin filas en {$table}");
        }

        $this->assertSame(20, DB::table('content_categories')->whereIn('content_id', $ids)->where('is_main', true)->count(), 'Cada entrada tiene su categoría principal');
        $this->assertTrue(DB::table('content_contributors')->whereIn('content_id', $ids)->doesntExist(), 'Sin usuarios que añadir como colaboradores, no hay ninguno');
        $this->assertTrue($this->blog()->where('is_featured', true)->exists());
        $this->assertTrue($this->blog()->has('pages', '>=', 2)->exists());
    }

    #[Test]
    public function incluye_una_oculta_un_borrador_y_una_programada_que_la_api_no_sirve(): void
    {
        (new PortfolioBlogSeeder)->run();

        $this->assertSame(17, $this->blog()->published()->count());
        $this->assertSame(1, $this->blog()->where('status_id', ContentStatusEnum::Draft->value)->count());
        $this->assertSame(1, $this->blog()->where('status_id', ContentStatusEnum::Scheduled->value)->whereNotNull('scheduled_at')->count());
        $this->assertSame(1, $this->blog()
            ->where('status_id', ContentStatusEnum::Published->value)->where('is_active', false)->count());

        $this->getJson('/api/v2/platforms/portfolio/contents?per_page=50')
            ->assertOk()
            ->assertJsonCount(17, 'data');
    }

    #[Test]
    public function es_idempotente_y_no_borra_nada(): void
    {
        (new PortfolioBlogSeeder)->run();
        $first = $this->blog()->orderBy('id')->pluck('id')->all();

        (new PortfolioBlogSeeder)->run();

        $this->assertSame($first, $this->blog()->orderBy('id')->pluck('id')->all());
        $this->assertSame(0, DB::table('contents')->whereNotNull('deleted_at')->count());
    }

    #[Test]
    public function sin_la_plataforma_no_hace_nada(): void
    {
        $this->platform->delete();

        (new PortfolioBlogSeeder)->run();

        $this->assertSame(0, Content::query()->count());
    }
}
