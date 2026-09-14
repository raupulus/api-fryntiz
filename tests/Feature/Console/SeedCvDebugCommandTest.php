<?php

declare(strict_types=1);

namespace Tests\Feature\Console;

use App\Models\CV\Curriculum;
use Database\Seeders\RolesTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * `debug:seed-cv` rellenaba `is_public => true` a mano, pero
 * `Curriculum::booted()` recalcula ese campo a partir de `visibility` en
 * cada guardado —lo que se pase por mass assignment no tiene efecto— y el
 * comando nunca tocaba `visibility`. Con el valor por defecto de la columna
 * (`private`), el CV de prueba se quedaba marcado como privado: no aparecía
 * ni en `/cv` ni en el sitemap pese a que el comando decía que sí era
 * público. Arreglado el 2026-09-14 pasando `visibility` explícitamente.
 */
class SeedCvDebugCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesTableSeeder::class);
    }

    #[Test]
    public function the_seeded_curriculum_is_actually_public(): void
    {
        $this->artisan('debug:seed-cv')->assertExitCode(0);

        $cv = Curriculum::query()->firstOrFail();

        $this->assertTrue(
            Curriculum::query()->publicOnly()->whereKey($cv->id)->exists(),
            'El CV de prueba debe pasar el mismo filtro que usan cv.index y el sitemap.'
        );
        $this->assertTrue($cv->isVisibleTo(), 'Debe ser accesible por su slug sin token, igual que en cv.show.');
    }
}
