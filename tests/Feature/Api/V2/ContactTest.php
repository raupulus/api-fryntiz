<?php

namespace Tests\Feature\Api\V2;

use PHPUnit\Framework\Attributes\Test;

use App\Services\Contact\ContactService;
use App\Services\RecaptchaService;
use Tests\Feature\Api\ApiTestCase;

class ContactTest extends ApiTestCase
{
    protected string $apiPrefix = 'api/v2';

    #[Test]
    public function contact_validates_required_fields(): void
    {
        $response = $this->postJson($this->apiUrl('contact/send'), []);
        $this->assertErrorResponse($response, 422);
        $response->assertJsonValidationErrors(['name', 'email', 'subject', 'message', 'g-recaptcha-response']);
    }

    #[Test]
    public function contact_validates_email_format(): void
    {
        $response = $this->postJson($this->apiUrl('contact/send'), [
            'name' => 'Test', 'email' => 'invalid',
            'subject' => 'Test', 'message' => 'Mensaje largo suficiente para pasar validación mínima',
            'g-recaptcha-response' => 'fake',
        ]);
        $this->assertErrorResponse($response, 422);
        $response->assertJsonValidationErrors(['email']);
    }

    #[Test]
    public function contact_validates_message_min_length(): void
    {
        $response = $this->postJson($this->apiUrl('contact/send'), [
            'name' => 'Test', 'email' => 'test@test.com',
            'subject' => 'Test', 'message' => 'Corto',
            'g-recaptcha-response' => 'fake',
        ]);
        $this->assertErrorResponse($response, 422);
        $response->assertJsonValidationErrors(['message']);
    }

    #[Test]
    public function contact_sends_successfully_with_valid_recaptcha(): void
    {
        $this->mock(RecaptchaService::class, function ($mock) {
            $mock->shouldReceive('verify')->andReturn(true);
        });
        $this->mock(ContactService::class, function ($mock) {
            $mock->shouldReceive('sendContactForm')->once();
        });

        $response = $this->postJson($this->apiUrl('contact/send'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'subject' => 'Test Subject',
            'message' => 'Este es un mensaje de prueba con longitud suficiente.',
            'g-recaptcha-response' => 'valid-token',
        ]);
        $this->assertSuccessResponse($response);
        $response->assertJson(['message' => 'Mensaje enviado correctamente']);
    }

    #[Test]
    public function contact_fails_with_invalid_recaptcha(): void
    {
        $this->mock(RecaptchaService::class, function ($mock) {
            $mock->shouldReceive('verify')->andReturn(false);
        });

        $response = $this->postJson($this->apiUrl('contact/send'), [
            'name' => 'Test', 'email' => 'test@example.com',
            'subject' => 'Test', 'message' => 'Mensaje largo suficiente para pasar la validacion de minimo.',
            'g-recaptcha-response' => 'invalid-token',
        ]);
        $this->assertErrorResponse($response, 422);
    }
}
