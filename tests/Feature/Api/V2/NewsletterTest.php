<?php

namespace Tests\Feature\Api\V2;

use Tests\Feature\Api\ApiTestCase;

class NewsletterTest extends ApiTestCase
{
    protected string $apiPrefix = 'api/v2';

    /** @test */
    public function can_subscribe_with_valid_email(): void
    {
        \Illuminate\Support\Facades\Mail::fake();
        $response = $this->postJson($this->apiUrl('newsletter/subscribe'), [
            'email' => 'subscriber@example.com',
        ]);
        $this->assertSuccessResponse($response, 201);
        $response->assertJson(['message' => 'Suscripcion creada. Revisa tu email para verificar.']);
    }

    /** @test */
    public function subscribe_succeeds_with_name(): void
    {
        \Illuminate\Support\Facades\Mail::fake();
        $response = $this->postJson($this->apiUrl('newsletter/subscribe'), [
            'email' => 'test@example.com',
            'name' => 'Nombre Test',
        ]);
        $this->assertSuccessResponse($response, 201);
    }

    /** @test */
    public function subscribe_fails_without_email(): void
    {
        $response = $this->postJson($this->apiUrl('newsletter/subscribe'), []);
        $this->assertErrorResponse($response, 422);
        $response->assertJsonValidationErrors(['email']);
    }

    /** @test */
    public function subscribe_fails_with_invalid_email(): void
    {
        $response = $this->postJson($this->apiUrl('newsletter/subscribe'), ['email' => 'invalid']);
        $this->assertErrorResponse($response, 422);
        $response->assertJsonValidationErrors(['email']);
    }

    /** @test */
    public function verify_fails_with_invalid_token(): void
    {
        $response = $this->getJson($this->apiUrl('newsletter/verify/token-invalido'));
        $this->assertErrorResponse($response, 404);
        $response->assertJson(['message' => 'Token invalido']);
    }

    /** @test */
    public function unsubscribe_fails_with_invalid_token(): void
    {
        $response = $this->getJson($this->apiUrl('newsletter/unsubscribe/token-invalido'));
        $this->assertErrorResponse($response, 404);
        $response->assertJson(['message' => 'Token invalido']);
    }
}
