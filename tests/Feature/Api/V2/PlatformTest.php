<?php

namespace Tests\Feature\Api\V2;

use App\Models\Platform;
use Tests\Feature\Api\ApiTestCase;

class PlatformTest extends ApiTestCase
{
    protected string $apiPrefix = 'api/v2';

    /** @test */
    public function can_get_all_platforms(): void
    {
        Platform::factory()->count(2)->create();
        $response = $this->getJson($this->apiUrl('platform'));
        $response->assertStatus(200)
                 ->assertJsonStructure(['data']);
    }

    /** @test */
    public function can_get_platform_by_slug(): void
    {
        $platform = Platform::factory()->create();
        $response = $this->getJson($this->apiUrl("platform/{$platform->slug}"));
        $this->assertSuccessResponse($response);
        $response->assertJsonStructure(['data']);
    }

    /** @test */
    public function platform_show_returns_correct_structure(): void
    {
        $platform = Platform::factory()->create();
        $response = $this->getJson($this->apiUrl("platform/{$platform->slug}"));
        $this->assertSuccessResponse($response);
        $response->assertJsonStructure([
            'success', 'message',
            'data' => ['id', 'name', 'slug', 'domain', 'description', 'image', 'created_at'],
        ]);
    }

    /** @test */
    public function platform_show_returns_404_for_nonexistent(): void
    {
        $response = $this->getJson($this->apiUrl('platform/slug-no-existe'));
        $this->assertErrorResponse($response, 404);
    }

    /** @test */
    public function can_get_featured_content(): void
    {
        $platform = Platform::factory()->create();
        $response = $this->getJson($this->apiUrl("platform/{$platform->slug}/featured"));
        $this->assertSuccessResponse($response);
    }

    /** @test */
    public function featured_returns_404_for_nonexistent_platform(): void
    {
        $response = $this->getJson($this->apiUrl('platform/no-existe/featured'));
        $this->assertErrorResponse($response, 404);
    }
}
