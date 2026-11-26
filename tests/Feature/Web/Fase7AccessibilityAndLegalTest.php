<?php

declare(strict_types=1);

namespace Tests\Feature\Web;

use App\Http\Middleware\NoIndex;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Verificación de correcciones de Fase 7 (accesibilidad WCAG, W3C, UX y RGPD web).
 */
class Fase7AccessibilityAndLegalTest extends TestCase
{
    #[Test]
    public function legal_pages_render_with_real_content_and_are_linked_from_footer(): void
    {
        foreach (['legal.notice' => 'Aviso Legal', 'legal.privacy' => 'Política de Privacidad', 'legal.cookies' => 'Política de Cookies'] as $route => $heading) {
            $this->get(route($route))
                ->assertOk()
                ->assertSee($heading)
                ->assertSee('public@raupulus.dev')
                ->assertDontSee('Lorem ipsum');
        }

        $home = $this->get(route('home'))->assertOk()->getContent();
        $this->assertStringContainsString(route('legal.notice'), $home);
        $this->assertStringContainsString(route('legal.privacy'), $home);
        $this->assertStringContainsString(route('legal.cookies'), $home);
    }

    #[Test]
    public function newsletter_management_routes_enforce_noindex_header_and_double_submit_guard(): void
    {
        $response = $this->get(route('newsletter.manage', ['token' => str_repeat('a', 64)]));
        $response->assertOk();
        $response->assertHeader('X-Robots-Tag', NoIndex::VALUE);
        $response->assertSee('Volver a la portada');
    }

    #[Test]
    public function missing_curriculum_pdf_renders_html_404_layout_with_navigation(): void
    {
        $response = $this->get(route('cv.pdf', ['slug' => 'inexistente-fase7']));
        $response->assertStatus(404);
        $response->assertSee('Ir a la portada');
    }

    #[Test]
    public function editor_paragraph_and_web_preview_partials_produce_valid_safe_markup(): void
    {
        $paragraphHtml = view('editor.fields._paragraph', [
            'id' => 'block-1',
            'text' => 'Contenido oculto',
            'tunes' => ['textVariant' => 'details'],
        ])->render();

        $this->assertStringNotContainsString('<p', $paragraphHtml);
        $this->assertStringContainsString('<details>', $paragraphHtml);

        $previewHtml = view('editor.fields._web_preview', [
            'id' => 'wp-1',
            'link' => 'javascript:alert(1)',
            'image' => 'https://example.test/cover.webp',
            'title' => 'Ejemplo',
            'description' => 'Descripción',
        ])->render();

        $this->assertStringNotContainsString('javascript:alert(1)', $previewHtml);
        $this->assertStringNotContainsString('style="background-image', $previewHtml);
        $this->assertStringContainsString('<img src="https://example.test/cover.webp"', $previewHtml);
    }

    #[Test]
    public function footer_renders_beating_heart_and_full_color_technology_icons(): void
    {
        $footerHtml = view('components.footer')->render();

        $this->assertStringContainsString('<animateTransform', $footerHtml);
        $this->assertStringContainsString('fill="tomato"', $footerHtml);
        $this->assertStringNotContainsString('grayscale', $footerHtml);
        $this->assertStringContainsString('fill="#FF2D20"', $footerHtml);
        $this->assertStringContainsString('fill="url(#footer-tw-grad)"', $footerHtml);
        $this->assertStringContainsString('fill="#336791"', $footerHtml);
    }
}
