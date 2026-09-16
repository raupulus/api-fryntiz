<?php

declare(strict_types=1);

namespace Tests\Unit\WeatherStation;

use App\Support\WeatherStation\SeaStateExtractor;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Los dos textos de este test son respuestas reales de
 * `/prediccion/maritima/costera/costa/42` (2026-09-16) para las subzonas
 * "Del Guadalquivir al Cabo Roche" y "De Punta Carnero a Punta Chullera".
 */
class SeaStateExtractorTest extends TestCase
{
    #[Test]
    public function picks_the_first_scale_term_over_a_later_one(): void
    {
        $text = 'Componente W 3 o 4, ocasionalmente 5, arreciando a SW 4 o 5 al final. '
            .'Marejadilla o marejada. Mar de fondo del W en torno a 1 m mar adentro.';

        $this->assertSame('Marejadilla', SeaStateExtractor::extract($text));
    }

    #[Test]
    public function does_not_let_a_compound_term_get_cut_by_its_root(): void
    {
        $text = 'Componente 4 a 6, amainando a W o NW 2 a 4 desde la madrugada y a Variable 1 a 3 al final. '
            .'Marejada o fuerte marejada, disminuyendo a marejadilla.';

        $this->assertSame('Marejada', SeaStateExtractor::extract($text));
    }

    #[Test]
    public function returns_null_without_any_known_term(): void
    {
        $this->assertNull(SeaStateExtractor::extract('No hay avisos.'));
    }

    #[Test]
    public function returns_null_for_empty_or_missing_text(): void
    {
        $this->assertNull(SeaStateExtractor::extract(null));
        $this->assertNull(SeaStateExtractor::extract('   '));
    }
}
