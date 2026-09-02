<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Content\Content;
use App\Models\Content\ContentSeo;
use App\Models\Platform;
use Database\Seeders\ContentAvailableStatusSeeder;
use Database\Seeders\ContentAvailableTypesSeeder;
use Database\Seeders\RolesTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * `ContentSeo::getHtml*Metatags()` concatenaba `description`, `og_title`,
 * `keywords`... en HTML sin escapar: `'<meta property="'.$key.'" content="'.$value.'">'`.
 * Un valor con una comilla doble seguida de HTML rompía el atributo e
 * inyectaba código en la página pública (AD-S02, auditoría de datos
 * 2026-09-02). Hoy no lo llama ninguna vista, pero el método sigue existiendo
 * a propósito, así que tiene que ser seguro por sí mismo.
 */
class ContentSeoMetaTagsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        (new RolesTableSeeder)->run();
        (new ContentAvailableStatusSeeder)->run();
        (new ContentAvailableTypesSeeder)->run();
    }

    private function makeSeo(array $overrides = []): ContentSeo
    {
        $content = Content::factory()->create([
            'platform_id' => Platform::factory()->create()->id,
        ]);

        return ContentSeo::create(array_merge([
            'content_id' => $content->id,
        ], $overrides));
    }

    #[Test]
    public function a_quote_in_the_description_does_not_break_out_of_the_attribute(): void
    {
        $seo = $this->makeSeo([
            'description' => '"><script>alert(1)</script>',
        ]);

        $html = $seo->getHtmlGenericMetatags();

        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
        $this->assertStringContainsString('&quot;', $html);
    }

    #[Test]
    public function a_quote_in_the_og_title_does_not_break_out_of_the_attribute(): void
    {
        $seo = $this->makeSeo([
            'og_title' => '"><img src=x onerror=alert(1)>',
        ]);

        $html = $seo->getHtmlMetatagsOpenGraph();

        $this->assertStringNotContainsString('<img', $html);
        $this->assertStringContainsString('&lt;img', $html);
    }

    #[Test]
    public function get_html_metatags_runs_without_crashing_and_includes_every_block(): void
    {
        // Antes de AD-S02, esta llamada concatenaba una `Collection` como si
        // fuera un string (`$this->getGenericTags()` en vez de
        // `$this->getHtmlGenericMetatags()`) y reventaba con un TypeError.
        $seo = $this->makeSeo([
            'description' => 'Descripción normal',
            'og_title' => 'Título normal',
        ]);

        $html = $seo->getHtmlMetatags();

        $this->assertIsString($html);
        $this->assertStringContainsString('Descripción normal', $html);
        $this->assertStringContainsString('Título normal', $html);
    }
}
