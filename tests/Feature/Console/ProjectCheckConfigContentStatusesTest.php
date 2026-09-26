<?php

declare(strict_types=1);

namespace Tests\Feature\Console;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\SeedsProductionContentStatuses;

/**
 * `project:check-config` avisa si los estados de contenido de la base no
 * tienen los ids de `ContentStatusEnum` (el fallo que dejó la API sin servir
 * ningún contenido, sin un solo error).
 */
class ProjectCheckConfigContentStatusesTest extends TestCase
{
    use RefreshDatabase;
    use SeedsProductionContentStatuses;

    #[Test]
    public function with_the_production_order_it_does_not_complain(): void
    {
        $this->seedContentStatusesAsProduction();

        Artisan::call('project:check-config');

        $this->assertStringNotContainsString('Estados de contenido', Artisan::output());
    }

    #[Test]
    public function with_published_and_scheduled_swapped_it_fails(): void
    {
        $this->seedContentStatusesAsProduction();

        // El orden que tuvo el seeder de la v2: 2 = publicado, 3 = programado.
        DB::table('content_available_status')->where('id', 2)->update(['slug' => 'tmp']);
        DB::table('content_available_status')->where('id', 3)->update(['slug' => 'programmed']);
        DB::table('content_available_status')->where('id', 2)->update(['slug' => 'published']);

        $exitCode = Artisan::call('project:check-config');
        $output = Artisan::output();

        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString('Estados de contenido con otros ids', $output);
        $this->assertStringContainsString('el 3 tendría que ser «published» y es «programmed»', $output);
    }
}
