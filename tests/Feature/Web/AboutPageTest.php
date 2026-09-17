<?php

declare(strict_types=1);

namespace Tests\Feature\Web;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Hasta el 2026-09-14, `/about` era una simple redirección 302 a la portada.
 * Ahora es una página real con la información del proyecto, enlazada desde el
 * footer.
 */
class AboutPageTest extends TestCase
{
    #[Test]
    public function it_renders_a_real_page_instead_of_redirecting(): void
    {
        $this->get(route('about'))
            ->assertOk()
            ->assertSee('Sobre el proyecto')
            ->assertSee('Api Raupulus');
    }

    #[Test]
    public function it_lists_every_public_module(): void
    {
        $this->get(route('about'))
            ->assertOk()
            ->assertSee('Estación Meteorológica')
            ->assertSee('Smart Plant')
            ->assertSee('Key Counter')
            ->assertSee('Radar de vuelo')
            ->assertSee('Energía')
            ->assertSee('Hardware')
            ->assertSee('Currículum');
    }

    #[Test]
    public function the_footer_links_to_it(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee(route('about'), escape: false);
    }
}
