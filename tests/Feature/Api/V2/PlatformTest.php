<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V2;

use App\Models\Platform;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Api\ApiTestCase;

class PlatformTest extends ApiTestCase
{
    protected string $apiPrefix = 'api/v2';

    #[Test]
    public function can_get_all_platforms(): void
    {
        Platform::factory()->count(2)->create();
        $response = $this->getJson($this->apiUrl('platforms'));
        $response->assertStatus(200)
            ->assertJsonStructure(['data']);
    }

    #[Test]
    public function can_get_platform_by_slug(): void
    {
        $platform = Platform::factory()->create();
        $response = $this->getJson($this->apiUrl("platforms/{$platform->slug}"));
        $this->assertSuccessResponse($response);
        $response->assertJsonStructure(['data']);
    }

    #[Test]
    public function platform_show_returns_correct_structure(): void
    {
        $platform = Platform::factory()->create();
        $response = $this->getJson($this->apiUrl("platforms/{$platform->slug}"));
        $this->assertSuccessResponse($response);
        $response->assertJsonStructure([
            'success', 'message',
            'data' => ['id', 'name', 'slug', 'domain', 'description', 'image', 'created_at'],
        ]);
    }

    #[Test]
    public function platform_show_returns_404_for_nonexistent(): void
    {
        $response = $this->getJson($this->apiUrl('platforms/slug-no-existe'));
        $this->assertErrorResponse($response, 404);
    }

    #[Test]
    public function can_get_featured_content(): void
    {
        $platform = Platform::factory()->create();
        $response = $this->getJson($this->apiUrl("platforms/{$platform->slug}/contents?featured=1"));
        $this->assertSuccessResponse($response);
    }

    #[Test]
    public function featured_returns_404_for_nonexistent_platform(): void
    {
        $response = $this->getJson($this->apiUrl('platforms/no-existe/contents?featured=1'));
        $this->assertErrorResponse($response, 404);
    }

    // ─── Categorías de una plataforma (GET /platforms/{slug}/categories) ───

    #[Test]
    public function can_get_categories_of_a_platform(): void
    {
        $platform = Platform::factory()->create();

        $response = $this->getJson($this->apiUrl('platforms/'.$platform->slug.'/categories'));

        $this->assertSuccessResponse($response);
        $response->assertJsonStructure(['success', 'message', 'data']);
    }

    #[Test]
    public function categories_of_an_unknown_platform_is_a_404(): void
    {
        $this->getJson($this->apiUrl('platforms/no-existe/categories'))->assertStatus(404);
    }
}
