<?php

namespace Tests\Feature\Api\V2;

use PHPUnit\Framework\Attributes\Test;

use App\Models\Platform;
use Illuminate\Support\Facades\Mail;
use Tests\Feature\Api\ApiTestCase;

class NewsletterTest extends ApiTestCase
{
    protected string $apiPrefix = 'api/v2';

    protected function setUp(): void
    {
        parent::setUp();
        Platform::factory()->create();
    }

    #[Test]
    public function can_subscribe_with_valid_email(): void
    {
        Mail::fake();
        $response = $this->postJson($this->apiUrl('newsletter/subscribe'), [
            'email' => 'subscriber@example.com',
        ]);
        $this->assertSuccessResponse($response, 201);
        $response->assertJson(['message' => 'Suscripcion creada. Revisa tu email para verificar.']);
    }

    #[Test]
    public function subscribe_succeeds_with_name(): void
    {
        Mail::fake();
        $response = $this->postJson($this->apiUrl('newsletter/subscribe'), [
            'email' => 'test@example.com',
            'name' => 'Nombre Test',
        ]);
        $this->assertSuccessResponse($response, 201);
    }

    #[Test]
    public function subscribe_fails_without_email(): void
    {
        $response = $this->postJson($this->apiUrl('newsletter/subscribe'), []);
        $this->assertErrorResponse($response, 422);
        $response->assertJsonValidationErrors(['email']);
    }

    #[Test]
    public function subscribe_fails_with_invalid_email(): void
    {
        $response = $this->postJson($this->apiUrl('newsletter/subscribe'), ['email' => 'invalid']);
        $this->assertErrorResponse($response, 422);
        $response->assertJsonValidationErrors(['email']);
    }

    #[Test]
    public function verify_fails_with_invalid_token(): void
    {
        $response = $this->getJson($this->apiUrl('newsletter/verify/token-invalido'));
        $this->assertErrorResponse($response, 404);
        $response->assertJson(['message' => 'Token invalido']);
    }

    #[Test]
    public function unsubscribe_fails_with_invalid_token(): void
    {
        $response = $this->getJson($this->apiUrl('newsletter/unsubscribe/token-invalido'));
        $this->assertErrorResponse($response, 404);
        $response->assertJson(['message' => 'Token invalido']);
    }
}
