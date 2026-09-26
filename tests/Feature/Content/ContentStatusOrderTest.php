<?php

declare(strict_types=1);

namespace Tests\Feature\Content;

use App\Enums\ContentStatusEnum;
use Database\Seeders\ContentAvailableStatusSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\SeedsProductionContentStatuses;

/**
 * Los ids de los estados de contenido: enum, seeder y producción, iguales.
 *
 * En mayo de 2026 la v2 cambió el orden en el seeder (2 = publicado) y el
 * código se escribió encima; producción conserva el de la v1 (2 = programado,
 * 3 = publicado) y la API no servía ningún contenido. Si alguien vuelve a
 * tocar uno de los tres, esto falla.
 */
class ContentStatusOrderTest extends TestCase
{
    use RefreshDatabase;
    use SeedsProductionContentStatuses;

    #[Test]
    public function the_enum_has_the_production_ids(): void
    {
        foreach (self::PRODUCTION_CONTENT_STATUSES as $id => [$slug]) {
            $this->assertSame($slug, ContentStatusEnum::from($id)->slug(), "El id {$id} no es «{$slug}» en el enum.");
        }

        $this->assertCount(count(self::PRODUCTION_CONTENT_STATUSES), ContentStatusEnum::cases());
    }

    #[Test]
    public function the_seeder_inserts_each_status_with_the_id_of_the_enum(): void
    {
        (new ContentAvailableStatusSeeder)->run();

        foreach (ContentStatusEnum::cases() as $status) {
            $this->assertSame(
                $status->slug(),
                DB::table('content_available_status')->where('id', $status->value)->value('slug'),
                "El seeder no pone «{$status->slug()}» en el id {$status->value}.",
            );
        }
    }
}
