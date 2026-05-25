<?php

namespace Tests\Feature\Api\V2;

use App\Models\Content\Content;
use App\Models\Platform;
use Tests\Feature\Api\ApiTestCase;

class ContentTest extends ApiTestCase
{
    protected string $apiPrefix = 'api/v2';

    /** @test */
    public function can_get_content_by_platform_and_slug(): void
    {
        $platform = Platform::factory()->create();
        $content = Content::factory()->create([
            'platform_id' => $platform->id,
            'is_active' => true,
            'published_at' => now()->subDay(),
        ]);

        $response = $this->getJson($this->apiUrl("content/{$platform->slug}/{$content->slug}"));
        $this->assertSuccessResponse($response);
        $response->assertJsonStructure(['data']);
    }

    /** @test */
    public function content_show_returns_correct_resource_structure(): void
    {
        $platform = Platform::factory()->create();
        $content = Content::factory()->create([
            'platform_id' => $platform->id,
            'is_active' => true,
            'published_at' => now()->subDay(),
        ]);

        $response = $this->getJson($this->apiUrl("content/{$platform->slug}/{$content->slug}"));
        $this->assertSuccessResponse($response);
        $response->assertJsonStructure([
            'success', 'message',
            'data' => ['id', 'title', 'slug', 'excerpt', 'type', 'published_at', 'created_at'],
        ]);
    }

    /** @test */
    public function content_show_returns_404_for_nonexistent(): void
    {
        $response = $this->getJson($this->apiUrl('content/no-existe/tampoco'));
        $this->assertErrorResponse($response, 404);
    }

    /** @test */
    public function can_get_content_pages(): void
    {
        $content = Content::factory()->create([
            'is_active' => true,
            'published_at' => now()->subDay(),
        ]);
        $response = $this->getJson($this->apiUrl("content/{$content->slug}/pages"));
        $this->assertSuccessResponse($response);
        $response->assertJsonStructure(['data']);
    }

    /** @test */
    public function pages_returns_404_for_nonexistent_content(): void
    {
        $response = $this->getJson($this->apiUrl('content/slug-no-existe/pages'));
        $this->assertErrorResponse($response, 404);
    }

    /** @test */
    public function can_get_related_content(): void
    {
        $content = Content::factory()->create([
            'is_active' => true,
            'published_at' => now()->subDay(),
        ]);
        $response = $this->getJson($this->apiUrl("content/{$content->slug}/related"));
        $this->assertSuccessResponse($response);
        $response->assertJsonStructure(['data']);
    }
}
