<?php

namespace Tests\Feature\Api\V1;

use App\Models\Newsletter;
use App\Models\Platform;
use Illuminate\Support\Facades\Mail;
use Tests\Feature\Api\ApiTestCase;

class NewsletterTest extends ApiTestCase
{
    protected string $apiPrefix = 'api/v1';

    /** @test */
    public function can_subscribe_with_valid_email(): void
    {
        Mail::fake();
        Platform::factory()->create();
        $response = $this->postJson($this->apiUrl('newsletter/subscribe'), [
            'email' => 'test@example.com',
        ]);
        $response->assertStatus(200)
            ->assertJsonPath('status', 'ok');
    }

    /** @test */
    public function subscribe_fails_without_email(): void
    {
        $response = $this->postJson($this->apiUrl('newsletter/subscribe'), []);
        $response->assertStatus(422);
    }

    /** @test */
    public function can_verify_with_valid_token(): void
    {
        $newsletter = Newsletter::factory()->unverified()->create();
        $response = $this->getJson($this->apiUrl("newsletter/verify/{$newsletter->verification_token}"));
        $response->assertStatus(200)
            ->assertJsonPath('status', 'ok');
    }

    /** @test */
    public function verify_fails_with_invalid_token(): void
    {
        $response = $this->getJson($this->apiUrl('newsletter/verify/token-invalido-xyz'));
        $response->assertStatus(404);
    }

    /** @test */
    public function can_unsubscribe_with_valid_token(): void
    {
        $newsletter = Newsletter::factory()->create();
        $response = $this->getJson($this->apiUrl("newsletter/unsubscribe/{$newsletter->unsubscribe_token}"));
        $response->assertStatus(200)
            ->assertJsonPath('status', 'ok');
    }

    /** @test */
    public function unsubscribe_fails_with_invalid_token(): void
    {
        $response = $this->getJson($this->apiUrl('newsletter/unsubscribe/token-invalido-xyz'));
        $response->assertStatus(404);
    }

    /** @test */
    public function can_get_stats(): void
    {
        $response = $this->getJson($this->apiUrl('newsletter/stats'));
        $response->assertStatus(200);
    }
}
