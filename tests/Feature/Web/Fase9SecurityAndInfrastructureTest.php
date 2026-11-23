<?php

declare(strict_types=1);

namespace Tests\Feature\Web;

use App\Models\User;
use Database\Seeders\RolesTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class Fase9SecurityAndInfrastructureTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function web_routes_emit_hardened_security_headers_including_csp_coop_and_corp(): void
    {
        foreach ([route('home'), route('about')] as $url) {
            $response = $this->get($url)->assertOk();

            $csp = (string) $response->headers->get('Content-Security-Policy');
            $this->assertNotEmpty($csp);
            $this->assertStringNotContainsString('cdn.jsdelivr.net', $csp);
            $this->assertStringNotContainsString('unpkg.com', $csp);
            $this->assertSame('same-origin', $response->headers->get('Cross-Origin-Opener-Policy'));
            $this->assertSame('same-site', $response->headers->get('Cross-Origin-Resource-Policy'));
            $this->assertSame('nosniff', $response->headers->get('X-Content-Type-Options'));
            $this->assertSame('SAMEORIGIN', $response->headers->get('X-Frame-Options'));
        }
    }

    #[Test]
    public function authenticated_scribe_docs_receives_dedicated_csp_allowing_its_cdn_assets(): void
    {
        $this->seed(RolesTableSeeder::class);
        $user = User::factory()->create(['is_active' => true]);

        $response = $this->actingAs($user)
            ->get(route('scribe'))
            ->assertOk();

        $csp = (string) $response->headers->get('Content-Security-Policy');
        $this->assertStringContainsString('https://cdn.jsdelivr.net', $csp);
        $this->assertStringContainsString('https://unpkg.com', $csp);
        $this->assertStringContainsString('https://cdnjs.cloudflare.com', $csp);
    }

    #[Test]
    public function api_fallback_returns_json_404_with_cross_origin_corp(): void
    {
        $response = $this->getJson('/api/v2/ruta-inexistente-fase9');

        $response->assertNotFound()
            ->assertJson(['success' => false]);

        $this->assertSame('cross-origin', $response->headers->get('Cross-Origin-Resource-Policy'));
    }
}
