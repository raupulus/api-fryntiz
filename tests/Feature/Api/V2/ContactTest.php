<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V2;

use App\Models\Email;
use App\Services\CaptchaResult;
use App\Services\Contact\ContactService;
use App\Services\RecaptchaService;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Api\ApiTestCase;

class ContactTest extends ApiTestCase
{
    protected string $apiPrefix = 'api/v2';

    #[Test]
    public function contact_validates_required_fields(): void
    {
        config(['google.recaptcha.secret_key' => 'test-secret']);
        $response = $this->postJson($this->apiUrl('contact-messages'), []);
        $this->assertErrorResponse($response, 422);
        $response->assertJsonValidationErrors(['name', 'email', 'subject', 'message', 'g-recaptcha-response']);
    }

    #[Test]
    public function contact_omits_recaptcha_validation_when_secret_key_is_empty(): void
    {
        config(['google.recaptcha.secret_key' => null]);
        $response = $this->postJson($this->apiUrl('contact-messages'), []);
        $this->assertErrorResponse($response, 422);
        $response->assertJsonValidationErrors(['name', 'email', 'subject', 'message']);
        $response->assertJsonMissingValidationErrors(['g-recaptcha-response']);
    }

    #[Test]
    public function contact_validates_email_format(): void
    {
        $response = $this->postJson($this->apiUrl('contact-messages'), [
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
        $response = $this->postJson($this->apiUrl('contact-messages'), [
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
            $mock->shouldReceive('verify')
                ->andReturn(new CaptchaResult(valid: true, score: 0.9, configured: true));
        });
        $this->mock(ContactService::class, function ($mock) {
            $mock->shouldReceive('register')->once()->andReturn(new Email);
        });

        $response = $this->postJson($this->apiUrl('contact-messages'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'subject' => 'Test Subject',
            'message' => 'Este es un mensaje de prueba con longitud suficiente.',
            'g-recaptcha-response' => 'valid-token',
        ]);
        // El alta de un mensaje crea un recurso: 201, no 200.
        $this->assertSuccessResponse($response, 201);
        $response->assertJson(['message' => 'Mensaje recibido correctamente']);
    }

    #[Test]
    public function contact_fails_with_invalid_recaptcha(): void
    {
        $this->mock(RecaptchaService::class, function ($mock) {
            $mock->shouldReceive('verify')
                ->andReturn(new CaptchaResult(valid: false, score: null, configured: true));
        });

        $response = $this->postJson($this->apiUrl('contact-messages'), [
            'name' => 'Test', 'email' => 'test@example.com',
            'subject' => 'Test', 'message' => 'Mensaje largo suficiente para pasar la validacion de minimo.',
            'g-recaptcha-response' => 'invalid-token',
        ]);
        $this->assertErrorResponse($response, 422);
    }
}
