<?php

namespace Tests\Feature\Api\V1;

use App\Http\Middleware\DomainCheckMiddleware;
use App\Models\Content\Content;
use App\Models\Platform;
use Tests\Feature\Api\ApiTestCase;

class ContentTest extends ApiTestCase
{
    protected string $apiPrefix = 'api/v1';

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(DomainCheckMiddleware::class);
    }

    /** @test */
    public function can_get_content_by_platform_and_slug(): void
    {
        $platform = Platform::factory()->create();
        $content = Content::factory()->create([
            'platform_id' => $platform->id,
            'is_active' => true,
            'published_at' => now()->subDay(),
        ]);

        $response = $this->getJson(
            $this->apiUrl("content/{$platform->slug}/{$content->slug}/get")
        );
        $response->assertStatus(200)
            ->assertJsonPath('status', 'ok');
    }

    /** @test */
    public function content_returns_404_for_nonexistent_slug(): void
    {
        $response = $this->getJson($this->apiUrl('content/no-existe/tampoco-existe/get'));
        $response->assertStatus(404);
    }

    /** @test */
    public function can_get_related_content(): void
    {
        $content = Content::factory()->create([
            'is_active' => true,
            'published_at' => now()->subDay(),
        ]);
        $response = $this->getJson($this->apiUrl("content/{$content->slug}/get/related"));
        $response->assertStatus(200)
            ->assertJsonPath('status', 'ok');
    }
}
