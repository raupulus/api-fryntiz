<?php

declare(strict_types=1);

namespace Tests\Feature\Web;

use App\Models\SmartPlant\SmartPlantPlant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class Fase8SeoAndAssetsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function home_emits_dynamic_lang_open_graph_twitter_canonical_favicons_and_json_ld(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk()
            ->assertSee('<html class="scroll-smooth" lang="es">', false)
            ->assertSee('<meta property="og:type" content="website"/>', false)
            ->assertSee('<meta property="og:locale" content="es_ES"/>', false)
            ->assertSee('<meta name="twitter:card" content="summary_large_image"/>', false)
            ->assertSee('<link rel="canonical" href="'.route('home').'"/>', false)
            ->assertSee('<link rel="manifest" href="'.asset('site.webmanifest').'"/>', false)
            ->assertSee('application/ld+json', false)
            ->assertSee('images/logo-fryntiz-512x512.webp', false)
            ->assertDontSee('fonts.googleapis.com/css2?family=Inter', false);
    }

    #[Test]
    public function noindex_pages_suppress_canonical_and_emit_noindex_robots_meta(): void
    {
        $this->get('/newsletter/manage?token=invalid')
            ->assertOk()
            ->assertSee('<meta name="robots" content="noindex, nofollow"/>', false)
            ->assertDontSee('<link rel="canonical"', false);

        $this->get('/ruta-que-no-existe-404')
            ->assertNotFound()
            ->assertSee('<meta name="robots" content="noindex, nofollow"/>', false)
            ->assertDontSee('<link rel="canonical"', false);
    }

    #[Test]
    public function favicon_is_under_20kb_and_manifest_is_valid_json(): void
    {
        $faviconSize = File::size(public_path('favicon.ico'));
        $this->assertLessThan(20 * 1024, $faviconSize);

        $manifestPath = public_path('site.webmanifest');
        $this->assertFileExists($manifestPath);

        $decoded = json_decode((string) File::get($manifestPath), true);
        $this->assertIsArray($decoded);
        $this->assertSame('Api Raupulus', $decoded['name'] ?? null);
        $this->assertNotEmpty($decoded['icons'] ?? []);
    }

    #[Test]
    public function keycounter_and_airflight_use_vite_bundles_without_legacy_cdn_or_ol3(): void
    {
        $keycounterHtml = (string) $this->get(route('keycounter.index'))->assertOk()->getContent();
        $this->assertStringNotContainsString('chart.js@2.9.3', $keycounterHtml);
        $this->assertStringContainsString('keycounter-chart-data', $keycounterHtml);

        $airflightHtml = (string) $this->get(route('airflight.index'))->assertOk()->getContent();
        $this->assertStringNotContainsString('jquery-3.0.0', $airflightHtml);
        $this->assertStringNotContainsString('jquery-ui-1.11.4', $airflightHtml);
        $this->assertStringNotContainsString('ol-3.17.1', $airflightHtml);
        $this->assertStringNotContainsString('ol3-layerswitcher', $airflightHtml);
        $this->assertStringContainsString('data-aircrafts-url="'.route('airflight.aircrafts').'"', $airflightHtml);
    }

    #[Test]
    public function sitemap_includes_smartplant_slug_and_legal_routes(): void
    {
        $plant = SmartPlantPlant::create([
            'name' => 'Arce japonés',
            'name_scientific' => 'Acer palmatum',
            'description' => 'Bonsái de hoja caduca',
            'details' => '<p>Cuidados</p>',
            'start_at' => now()->subMonths(3),
        ]);

        Artisan::call('sitemap:generate', ['--force' => true]);

        $xml = (string) File::get(public_path('sitemap.xml'));

        $this->assertStringContainsString(route('smartplant.show', ['smartplant' => $plant->slug]), $xml);
        $this->assertStringContainsString(route('legal.privacy'), $xml);
        $this->assertStringContainsString(route('legal.notice'), $xml);
        $this->assertStringContainsString(route('legal.cookies'), $xml);

        File::delete(public_path('sitemap.xml'));
    }
}
