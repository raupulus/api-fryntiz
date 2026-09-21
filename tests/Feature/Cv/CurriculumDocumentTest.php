<?php

declare(strict_types=1);

namespace Tests\Feature\Cv;

use App\Enums\CurriculumVisibilityEnum;
use App\Models\CV\Curriculum;
use App\Models\CV\CurriculumAcademicComplementary;
use App\Models\CV\CurriculumExperienceAccredited;
use App\Models\CV\CurriculumExperienceAdditional;
use App\Models\CV\CurriculumExperienceSelfEmployed;
use App\Models\User;
use App\Services\Cv\CurriculumDocument;
use Database\Seeders\RolesTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * CurriculumDocument prepara el currículum para la vista web y el PDF, que
 * comparten la maquetación del CV de 2024.
 */
class CurriculumDocumentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        (new RolesTableSeeder)->run();
    }

    private ?User $owner = null;

    private function makeCurriculum(array $attributes = []): Curriculum
    {
        // Un solo dueño por test: el email es único en `users`.
        $this->owner ??= User::factory()->create(['name' => 'Raúl', 'surname' => 'Caro Pastorino', 'email' => 'privado@example.test']);

        return Curriculum::create(array_merge([
            'user_id' => $this->owner->id,
            'title' => 'Desarrollador Backend',
            'slug' => 'desarrollador-backend',
            'presentation' => 'Presentación corta.',
            'visibility' => CurriculumVisibilityEnum::Public,
            'is_active' => true,
            'is_downloadable' => true,
        ], $attributes));
    }

    /**
     * @return array<string, array{0: ?string, 1: ?string, 2: ?string}>
     */
    public static function periods(): array
    {
        return [
            'en curso' => ['2018-12-01 00:00:00', null, '12/2018 – Actualidad'],
            'meses' => ['2011-02-01 00:00:00', '2019-09-01 00:00:00', '02/2011 – 09/2019'],
            'años completos' => ['2007-01-01 00:00:00', '2009-12-31 00:00:00', '2007 – 2009'],
            'un solo año' => ['2008-01-01 00:00:00', '2008-12-31 00:00:00', '2008'],
            'un solo mes' => ['2015-10-01 00:00:00', '2015-10-01 00:00:00', '10/2015'],
            'enero con fin a mitad de año no es precisión de año' => ['2005-01-01 00:00:00', '2016-09-01 00:00:00', '01/2005 – 09/2016'],
            'sin fechas' => [null, null, null],
        ];
    }

    #[Test]
    #[DataProvider('periods')]
    public function it_formats_periods_with_month_or_year_precision(?string $start, ?string $end, ?string $expected): void
    {
        $this->assertSame($expected, CurriculumDocument::period($start, $end));
    }

    #[Test]
    public function a_single_date_on_december_31st_is_shown_as_a_year(): void
    {
        $this->assertSame('2017', CurriculumDocument::single('2017-12-31 00:00:00'));
        $this->assertSame('02/2021', CurriculumDocument::single('2021-02-01 00:00:00'));
    }

    #[Test]
    public function it_splits_descriptions_into_paragraphs_and_bullet_lists(): void
    {
        $blocks = CurriculumDocument::blocks("Intro del puesto.\n- Primera viñeta\n- Segunda viñeta\n\nTecnologías: PHP.");

        $this->assertSame([
            ['type' => 'text', 'text' => 'Intro del puesto.'],
            ['type' => 'list', 'items' => ['Primera viñeta', 'Segunda viñeta']],
            ['type' => 'text', 'text' => 'Tecnologías: PHP.'],
        ], $blocks);
    }

    #[Test]
    public function experience_merges_all_kinds_with_the_current_job_first(): void
    {
        $cv = $this->makeCurriculum();

        CurriculumExperienceSelfEmployed::create(['curriculum_id' => $cv->id, 'title' => 'Freelance', 'start_at' => '2011-02-01', 'end_at' => '2019-09-01']);
        CurriculumExperienceAdditional::create(['curriculum_id' => $cv->id, 'title' => 'Prácticas', 'start_at' => '2018-09-01', 'end_at' => '2018-12-01']);
        CurriculumExperienceAccredited::create(['curriculum_id' => $cv->id, 'title' => 'Actual', 'company' => 'Empresa', 'start_at' => '2018-12-01']);

        $titles = array_column(CurriculumDocument::for($cv->fresh())->experience(), 'title');

        $this->assertSame(['Actual', 'Freelance', 'Prácticas'], $titles);
    }

    #[Test]
    public function rows_without_any_date_go_last_instead_of_first(): void
    {
        $cv = $this->makeCurriculum();

        CurriculumAcademicComplementary::create(['curriculum_id' => $cv->id, 'title' => 'Sin fecha']);
        CurriculumAcademicComplementary::create(['curriculum_id' => $cv->id, 'title' => 'Con fecha', 'start_at' => '2016-04-01', 'end_at' => '2016-05-01']);

        $titles = array_column(CurriculumDocument::for($cv->fresh())->complementary(), 'title');

        $this->assertSame(['Con fecha', 'Sin fecha'], $titles);
    }

    #[Test]
    public function the_contact_comes_from_config_and_never_from_the_login_email(): void
    {
        config(['cv.contact.email' => 'public@raupulus.dev']);

        $contact = CurriculumDocument::for($this->makeCurriculum())->contact();

        $this->assertSame('public@raupulus.dev', $contact['email']);
        $this->assertNotContains('privado@example.test', $contact);
    }

    #[Test]
    public function a_short_profile_goes_to_the_sidebar_and_a_long_one_to_the_main_column(): void
    {
        $short = CurriculumDocument::for($this->makeCurriculum());
        $long = CurriculumDocument::for($this->makeCurriculum([
            'slug' => 'completo',
            'presentation' => str_repeat('Un párrafo bastante largo de presentación que no cabe. ', 60),
        ]));

        $this->assertTrue($short->inSidebar('profile'));
        $this->assertFalse($long->inSidebar('profile'));
    }

    #[Test]
    public function only_public_or_shared_curricula_get_a_qr_code(): void
    {
        $public = CurriculumDocument::for($this->makeCurriculum());
        $private = CurriculumDocument::for($this->makeCurriculum(['slug' => 'privado', 'visibility' => CurriculumVisibilityEnum::Private]));

        $this->assertSame(route('cv.show', ['slug' => 'desarrollador-backend']), $public->publicUrl());
        $this->assertStringStartsWith('data:image/svg+xml;base64,', (string) $public->qrCodeDataUri());
        $this->assertNull($private->qrCodeDataUri());
    }
}
