<?php

declare(strict_types=1);

namespace Tests\Feature\Console;

use App\Enums\CurriculumVisibilityEnum;
use App\Models\CV\Curriculum;
use App\Models\User;
use Database\Seeders\RolesTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * El listado de currículums (`cv.index`) entra siempre en el sitemap, tenga o
 * no currículums públicos hoy —igual que `smartplant.index` o
 * `weather_station.index`—. Cada currículum público añade además su propia
 * `cv.show`: si no hay ninguno público, no hay ninguna `cv.show`; si hay
 * varios, entran todos. Los privados y los compartidos por token nunca deben
 * aparecer, porque no son indexables.
 */
class SitemapCurriculumUrlsTest extends TestCase
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
        ], $attributes));
    }

    private function generateSitemap(): string
    {
        $this->artisan('sitemap:generate', ['--force' => true])->assertExitCode(0);

        return File::get(public_path('sitemap.xml'));
    }

    #[Test]
    public function the_curriculum_index_is_always_in_the_sitemap(): void
    {
        $sitemap = $this->generateSitemap();

        $this->assertStringContainsString(route('cv.index'), $sitemap);
    }

    #[Test]
    public function no_curriculum_pages_appear_when_there_are_no_public_curricula(): void
    {
        $this->makeCurriculum(['slug' => 'cv-privado', 'visibility' => CurriculumVisibilityEnum::Private]);
        $this->makeCurriculum(['slug' => 'cv-compartido', 'visibility' => CurriculumVisibilityEnum::Shared]);

        $sitemap = $this->generateSitemap();

        $this->assertStringNotContainsString(route('cv.show', ['slug' => 'cv-privado']), $sitemap);
        $this->assertStringNotContainsString(route('cv.show', ['slug' => 'cv-compartido']), $sitemap);
    }

    #[Test]
    public function every_public_curriculum_gets_indexed(): void
    {
        $this->makeCurriculum(['slug' => 'cv-frontend', 'title' => 'CV Frontend']);
        $this->makeCurriculum(['slug' => 'cv-backend', 'title' => 'CV Backend']);
        $this->makeCurriculum(['slug' => 'cv-resumen', 'title' => 'CV Resumen']);

        $sitemap = $this->generateSitemap();

        $this->assertStringContainsString(route('cv.show', ['slug' => 'cv-frontend']), $sitemap);
        $this->assertStringContainsString(route('cv.show', ['slug' => 'cv-backend']), $sitemap);
        $this->assertStringContainsString(route('cv.show', ['slug' => 'cv-resumen']), $sitemap);
    }

    #[Test]
    public function inactive_public_curricula_are_not_indexed(): void
    {
        $this->makeCurriculum(['slug' => 'cv-baja', 'is_active' => false]);

        $sitemap = $this->generateSitemap();

        $this->assertStringNotContainsString(route('cv.show', ['slug' => 'cv-baja']), $sitemap);
    }
}
