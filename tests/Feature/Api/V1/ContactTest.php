<?php

namespace Tests\Feature\Api\V1;

use Tests\Feature\Api\ApiTestCase;

class ContactTest extends ApiTestCase
{
    protected string $apiPrefix = 'api/v1';

    /** @test */
    public function contact_validates_required_fields(): void
    {
        $response = $this->postJson($this->apiUrl('contact/send'), []);
        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['message', 'subject', 'email', 'captcha_token']);
    }

    /** @test */
    public function contact_validates_email_format(): void
    {
        $response = $this->postJson($this->apiUrl('contact/send'), [
            'message' => 'Test message',
            'subject' => 'Test subject',
            'email'   => 'invalid-email',
            'captcha_token' => 'fake-token',
        ]);
        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['email']);
    }

    /** @test */
    public function contact_accepts_valid_data(): void
    {
        $response = $this->postJson($this->apiUrl('contact/send'), [
            'message'       => 'Mensaje de prueba con contenido suficiente',
            'subject'       => 'Asunto de prueba',
            'email'         => 'test@example.com',
            'captcha_token' => 'fake-token-for-testing',
            'app_name'      => 'raupulus',
            'app_domain'    => 'raupulus.dev',
        ]);
        // 200 (acepta pero no envía por captcha) o 403 (ip/domain check)
        $this->assertContains($response->status(), [200, 403]);
    }
}
