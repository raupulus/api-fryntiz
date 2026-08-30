<?php

declare(strict_types=1);

namespace Tests\Feature\Cv;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * `routes/cv/web.php` apuntaba a métodos que no existen en
 * `CurriculumController` (`pdfPorDefecto`, `pdfCompartido`), así que las tres
 * rutas de descarga de PDF respondían siempre 500 (`BadMethodCallException`).
 * Arreglado el 2026-08-30.
 */
class CurriculumWebRoutesTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function default_pdf_route_does_not_error(): void
    {
        $response = $this->get(route('cv.pdf.default'));
        $response->assertStatus(404);
    }

    #[Test]
    public function shared_pdf_route_does_not_error(): void
    {
        $response = $this->get(route('cv.shared.pdf', ['shareToken' => str_repeat('a', 64)]));
        $response->assertStatus(404);
    }

    #[Test]
    public function slug_pdf_route_does_not_error(): void
    {
        $response = $this->get(route('cv.pdf', ['slug' => 'no-existe']));
        $response->assertStatus(404);
    }
}
