<?php

namespace Tests\Feature\Api\V1;

use App\Http\Middleware\DomainCheckMiddleware;
use App\Models\Platform;
use Tests\Feature\Api\ApiTestCase;

class PlatformTest extends ApiTestCase
{
    protected string $apiPrefix = 'api/v1';

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(DomainCheckMiddleware::class);
    }

    /** @test */
    public function can_get_all_platforms(): void
    {
        Platform::factory()->count(2)->create();
        $response = $this->getJson($this->apiUrl('platform/all'));
        $response->assertStatus(200)
            ->assertJsonPath('status', 'ok')
            ->assertJsonStructure(['data' => ['platforms', 'total']]);
    }

    /** @test */
    public function can_get_platform_info_by_slug(): void
    {
        $platform = Platform::factory()->create();
        $response = $this->getJson($this->apiUrl("platform/{$platform->slug}/info"));
        $response->assertStatus(200)
            ->assertJsonPath('status', 'ok')
            ->assertJsonPath('data.slug', $platform->slug);
    }

    /** @test */
    public function platform_info_returns_404_for_nonexistent(): void
    {
        $response = $this->getJson($this->apiUrl('platform/slug-no-existe/info'));
        $response->assertStatus(404);
    }

    /** @test */
    public function can_get_content_by_type(): void
    {
        $platform = Platform::factory()->create();
        $response = $this->getJson($this->apiUrl("platform/{$platform->slug}/content/type/article"));
        $this->assertContains($response->status(), [200, 422]);
    }

    /** @test */
    public function can_get_platform_categories(): void
    {
        $platform = Platform::factory()->create();
        $response = $this->getJson($this->apiUrl("platform/{$platform->slug}/get/categories"));
        $response->assertStatus(200)
            ->assertJsonPath('status', 'ok');
    }
}
