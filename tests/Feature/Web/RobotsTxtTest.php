<?php

declare(strict_types=1);

namespace Tests\Feature\Web;

use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * `public/robots.txt` apuntaba su directiva `Sitemap:` a `raupulus.dev`, un
 * dominio que no sirve esta aplicación: los vhosts reales
 * (`docs/deploys/vhosts/`) sirven `api.raupulus.dev`. Corregido el
 * 2026-09-14.
 *
 * Es un fichero estático (no pasa por el router), así que se comprueba
 * leyéndolo del disco en vez de con una petición HTTP.
 */
class RobotsTxtTest extends TestCase
{
    #[Test]
    public function the_sitemap_directive_points_to_this_application_own_host(): void
    {
        $robots = File::get(public_path('robots.txt'));

        $this->assertStringContainsString('Sitemap: https://api.raupulus.dev/sitemap.xml', $robots);
        $this->assertStringNotContainsString('Sitemap: https://raupulus.dev', $robots);
    }

    #[Test]
    public function it_still_blocks_admin_panels_and_tokenized_links(): void
    {
        $robots = File::get(public_path('robots.txt'));

        $this->assertStringContainsString('Disallow: /admin', $robots);
        $this->assertStringContainsString('Disallow: /panel', $robots);
        $this->assertStringContainsString('Disallow: /cv/s/', $robots);
        $this->assertStringContainsString('Disallow: /newsletter/', $robots);
    }
}
