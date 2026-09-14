<?php

declare(strict_types=1);

namespace Tests\Feature\Cv;

use App\Enums\CurriculumVisibilityEnum;
use App\Models\CV\Curriculum;
use App\Models\User;
use Database\Seeders\RolesTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * `routes/cv/web.php` apuntaba a métodos que no existen en
 * `CurriculumController` (`pdfPorDefecto`, `pdfCompartido`), así que las tres
 * rutas de descarga de PDF respondían siempre 500 (`BadMethodCallException`).
 * Arreglado el 2026-08-30.
 *
 * Desde el 2026-09-14 el módulo tiene además vistas públicas (`cv.index`,
 * `cv.show`), no sólo descarga de PDF.
 */
class CurriculumWebRoutesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        (new RolesTableSeeder)->run();
    }

    private function makeCurriculum(array $attributes = []): Curriculum
    {
        return Curriculum::create(array_merge([
            'user_id' => User::factory()->create()->id,
            'title' => 'CV de prueba',
            'slug' => 'cv-de-prueba',
            'visibility' => CurriculumVisibilityEnum::Public,
            'is_active' => true,
            'is_downloadable' => true,
        ], $attributes));
    }

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

    #[Test]
    public function the_index_shows_an_empty_message_when_there_are_no_public_curricula(): void
    {
        $this->get(route('cv.index'))
            ->assertOk()
            ->assertSee('Todavía no hay currículums públicos');
    }

    #[Test]
    public function the_index_lists_only_public_and_active_curricula(): void
    {
        $this->makeCurriculum(['title' => 'CV Backend', 'slug' => 'cv-backend']);
        $this->makeCurriculum(['title' => 'CV Privado', 'slug' => 'cv-privado', 'visibility' => CurriculumVisibilityEnum::Private]);
        $this->makeCurriculum(['title' => 'CV Compartido', 'slug' => 'cv-compartido', 'visibility' => CurriculumVisibilityEnum::Shared]);
        $this->makeCurriculum(['title' => 'CV Inactivo', 'slug' => 'cv-inactivo', 'is_active' => false]);

        $this->get(route('cv.index'))
            ->assertOk()
            ->assertSee('CV Backend')
            ->assertDontSee('CV Privado')
            ->assertDontSee('CV Compartido')
            ->assertDontSee('CV Inactivo');
    }

    #[Test]
    public function a_public_curriculum_shows_its_page_with_a_download_button(): void
    {
        $this->makeCurriculum(['title' => 'CV Frontend', 'slug' => 'cv-frontend', 'is_downloadable' => true]);

        $this->get(route('cv.show', ['slug' => 'cv-frontend']))
            ->assertOk()
            ->assertSee('CV Frontend')
            ->assertSee(route('cv.pdf', ['slug' => 'cv-frontend']), escape: false);
    }

    #[Test]
    public function the_download_button_is_hidden_when_the_curriculum_is_not_downloadable(): void
    {
        $this->makeCurriculum(['title' => 'CV Sin PDF', 'slug' => 'cv-sin-pdf', 'is_downloadable' => false]);

        $this->get(route('cv.show', ['slug' => 'cv-sin-pdf']))
            ->assertOk()
            ->assertDontSee(route('cv.pdf', ['slug' => 'cv-sin-pdf']), escape: false);
    }

    #[Test]
    public function a_private_curriculum_is_not_reachable_by_its_slug(): void
    {
        $this->makeCurriculum(['title' => 'CV Privado', 'slug' => 'cv-privado', 'visibility' => CurriculumVisibilityEnum::Private]);

        $this->get(route('cv.show', ['slug' => 'cv-privado']))->assertStatus(404);
    }

    #[Test]
    public function a_shared_curriculum_is_not_reachable_by_its_slug_without_the_token(): void
    {
        $this->makeCurriculum(['title' => 'CV Compartido', 'slug' => 'cv-compartido', 'visibility' => CurriculumVisibilityEnum::Shared]);

        $this->get(route('cv.show', ['slug' => 'cv-compartido']))->assertStatus(404);
    }

    #[Test]
    public function an_unknown_slug_shows_the_page_returns_404(): void
    {
        $this->get(route('cv.show', ['slug' => 'no-existe']))->assertStatus(404);
    }
}
