<?php

declare(strict_types=1);

namespace Tests\Feature\Cv;

use App\Enums\CurriculumVisibilityEnum;
use App\Models\CV\Curriculum;
use App\Models\User;
use App\Services\Cv\CurriculumPdfService;
use Database\Seeders\RolesTableSeeder;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Si el PDF no se puede escribir (permisos), el CV no puede quedar marcado como
 * regenerado: en producción se siguió sirviendo el PDF viejo mientras
 * `cv:regenerate-pdfs` decía «PDF regenerados: 6» (2026-09-21).
 */
class CurriculumPdfServiceTest extends TestCase
{
    use RefreshDatabase;

    private function makeCurriculum(): Curriculum
    {
        (new RolesTableSeeder)->run();

        return Curriculum::create([
            'user_id' => User::factory()->create()->id,
            'title' => 'CV de prueba',
            'slug' => 'cv-de-prueba',
            'visibility' => CurriculumVisibilityEnum::Public,
            'is_active' => true,
            'is_downloadable' => true,
        ]);
    }

    #[Test]
    public function it_generates_and_stores_the_pdf(): void
    {
        Storage::fake('public');
        $cv = $this->makeCurriculum();

        $path = app(CurriculumPdfService::class)->generate($cv);

        Storage::disk('public')->assertExists($path);
        $this->assertFalse($cv->fresh()->pdf_needs_regeneration);
    }

    #[Test]
    public function a_failed_write_throws_and_keeps_the_curriculum_pending(): void
    {
        $cv = $this->makeCurriculum();
        $cv->forceFill(['pdf_needs_regeneration' => true])->saveQuietly();

        $disk = Mockery::mock(Filesystem::class);
        $disk->shouldReceive('put')->andReturnFalse();
        Storage::shouldReceive('disk')->with('public')->andReturn($disk);

        try {
            app(CurriculumPdfService::class)->generate($cv);
            $this->fail('Se esperaba una excepción al no poder escribir el PDF.');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('No se pudo escribir el PDF', $e->getMessage());
        }

        $this->assertTrue($cv->fresh()->pdf_needs_regeneration);
    }
}
