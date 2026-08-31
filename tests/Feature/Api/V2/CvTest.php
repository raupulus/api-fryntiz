<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V2;

use App\Enums\CurriculumVisibilityEnum;
use App\Models\CV\Curriculum;
use App\Models\User;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Api\ApiTestCase;

/**
 * `/api/v2/curricula`.
 *
 * Antes eran diez rutas planas (`/cv/experience`, `/cv/skills`…) que daban por
 * hecho que existe un único currículum y devolvían el del superadmin. Los tests
 * de entonces sólo comprobaban que la respuesta tuviera forma de éxito, así que
 * pasaban igual con la base vacía.
 */
class CvTest extends ApiTestCase
{
    protected string $apiPrefix = 'api/v2';

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = $this->createAuthenticatedUser(1);
    }

    #[Test]
    public function the_listing_only_shows_public_ones(): void
    {
        $publico = $this->createCurriculum('Perfil público', CurriculumVisibilityEnum::Public);
        $this->createCurriculum('Perfil privado', CurriculumVisibilityEnum::Private);
        $this->createCurriculum('Perfil compartido', CurriculumVisibilityEnum::Shared);

        $response = $this->getJson($this->apiUrl('curricula'));

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.slug', $publico->slug);
    }

    #[Test]
    public function a_public_curriculum_is_read_by_its_slug(): void
    {
        $cv = $this->createCurriculum('Backend senior', CurriculumVisibilityEnum::Public);

        $response = $this->getJson($this->apiUrl("curricula/{$cv->slug}"));

        $this->assertSuccessResponse($response);
        $response->assertJsonPath('data.slug', $cv->slug);
    }

    #[Test]
    public function a_private_curriculum_is_not_read_by_its_slug(): void
    {
        $cv = $this->createCurriculum('Sólo para mí', CurriculumVisibilityEnum::Private);

        $this->getJson($this->apiUrl("curricula/{$cv->slug}"))->assertStatus(404);
    }

    #[Test]
    public function a_shared_curriculum_is_not_read_by_slug_but_it_is_by_token(): void
    {
        // Es el caso que justifica los tres estados: mandarle a alguien un CV
        // hecho a medida sin que salga en internet.
        $cv = $this->createCurriculum('Para la oferta X', CurriculumVisibilityEnum::Shared);

        $this->getJson($this->apiUrl("curricula/{$cv->slug}"))->assertStatus(404);

        $response = $this->getJson($this->apiUrl("curricula/shared/{$cv->share_token}"));

        $this->assertSuccessResponse($response);
        $response->assertJsonPath('data.slug', $cv->slug);
        $response->assertHeader('X-Robots-Tag', 'noindex, nofollow');
    }

    #[Test]
    public function the_share_token_is_generated_automatically(): void
    {
        $cv = $this->createCurriculum('Compartido', CurriculumVisibilityEnum::Shared);

        $this->assertNotNull($cv->share_token);
        $this->assertSame(64, strlen($cv->share_token));
    }

    #[Test]
    public function a_token_that_belongs_to_nobody_returns_404(): void
    {
        $this->getJson($this->apiUrl('curricula/shared/'.str_repeat('a', 64)))->assertStatus(404);
    }

    #[Test]
    #[DataProvider('sections')]
    public function each_section_can_be_requested_on_its_own(string $section): void
    {
        $cv = $this->createCurriculum('Con secciones', CurriculumVisibilityEnum::Public);

        $this->getJson($this->apiUrl("curricula/{$cv->slug}/{$section}"))->assertStatus(200);
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function sections(): array
    {
        return [
            'experiencia' => ['experiences'],
            'formación' => ['educations'],
            'habilidades' => ['skills'],
            'proyectos' => ['projects'],
            'repositorios' => ['repositories'],
            'servicios' => ['services'],
            'colaboraciones' => ['collaborations'],
            'aficiones' => ['hobbies'],
            'trabajos' => ['jobs'],
        ];
    }

    #[Test]
    public function a_made_up_section_returns_404(): void
    {
        $cv = $this->createCurriculum('Con secciones', CurriculumVisibilityEnum::Public);

        $this->getJson($this->apiUrl("curricula/{$cv->slug}/inventada"))->assertStatus(404);
    }

    private function createCurriculum(string $title, CurriculumVisibilityEnum $visibilidad): Curriculum
    {
        $cv = new Curriculum;
        $cv->forceFill([
            'user_id' => $this->user->id,
            'title' => $title,
            'slug' => Str::slug($title),
            'visibility' => $visibilidad->value,
            'is_active' => true,
            'is_downloadable' => true,
            'is_default' => false,
        ])->save();

        return $cv->fresh();
    }
}
