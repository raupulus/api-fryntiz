<?php

declare(strict_types=1);

namespace Tests\Feature\Web;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Verificación de correcciones de Fase 6 (design system, dark mode, contraste y responsive).
 */
class Fase6DesignSystemResponsiveTest extends TestCase
{
    #[Test]
    public function app_css_defines_custom_variant_dark_design_tokens_and_reduced_motion(): void
    {
        $css = file_get_contents(resource_path('css/app.css'));
        $this->assertIsString($css);

        $this->assertStringContainsString('@custom-variant dark (&:where(.dark, .dark *));', $css);
        $this->assertStringContainsString('color-scheme: light;', $css);
        $this->assertStringContainsString('color-scheme: dark;', $css);
        $this->assertStringContainsString('--font-sans:', $css);
        $this->assertStringContainsString('@media (prefers-reduced-motion: reduce)', $css);
        $this->assertStringContainsString('.r-table-container', $css);
        $this->assertStringContainsString('.r-checkbox-item-icon--checked', $css);
        $this->assertStringNotContainsString('.navbar-menu-element-current', $css);
    }

    #[Test]
    public function navbar_modal_and_components_use_x_cloak_and_accessible_targets(): void
    {
        $navbar = file_get_contents(resource_path('views/components/navbar.blade.php'));
        $modal = file_get_contents(resource_path('views/components/modal.blade.php'));
        $input = file_get_contents(resource_path('views/components/input.blade.php'));
        $pagination = file_get_contents(resource_path('views/vendor/pagination/tailwind.blade.php'));

        $this->assertIsString($navbar);
        $this->assertIsString($modal);
        $this->assertIsString($input);
        $this->assertIsString($pagination);

        $this->assertStringContainsString('x-cloak', $navbar);
        $this->assertStringContainsString('aria-current="page"', $navbar);
        $this->assertStringContainsString('x-cloak', $modal);
        $this->assertStringContainsString('max-h-[90dvh]', $modal);
        $this->assertStringContainsString('text-base sm:text-sm', $input);
        $this->assertStringContainsString('md:hidden', $pagination);
    }

    #[Test]
    public function public_home_and_keycounter_views_render_without_legacy_pastel_utilities(): void
    {
        $homeHtml = $this->get(route('home'))->assertOk()->getContent();
        $this->assertStringContainsString('Sobre Mí', $homeHtml);
        $this->assertStringNotContainsString('w-5/6 sm:w-1/2', $homeHtml);

        $keycounterHtml = $this->get(route('keycounter.index'))->assertOk()->getContent();
        $this->assertStringContainsString('keycounter-chart-data', $keycounterHtml);
        $this->assertStringNotContainsString('bg-amber-50', $keycounterHtml);
        $this->assertStringNotContainsString('bg-indigo-600', $keycounterHtml);
    }
}
