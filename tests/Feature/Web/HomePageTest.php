<?php

declare(strict_types=1);

namespace Tests\Feature\Web;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * La tarjeta de «Más información» que enlazaba a `/panel` (Panel de gestión)
 * pasó a enlazar al listado público de currículums el 2026-09-14: el panel de
 * administración no tiene nada que hacer en la portada pública, y el CV es lo
 * que de verdad interesa enseñar ahí.
 */
class HomePageTest extends TestCase
{
    #[Test]
    public function it_links_to_the_curriculum_listing_instead_of_the_admin_panel(): void
    {
        $response = $this->get(route('home'))->assertOk();

        $response->assertSee(route('cv.index'), escape: false);
        $response->assertDontSee('Panel de gestión');
    }
}
