<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V2;

use App\Models\Newsletter;
use App\Models\Platform;
use App\Support\Auth\TokenAbilities;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Api\ApiTestCase;

class NewsletterTest extends ApiTestCase
{
    protected string $apiPrefix = 'api/v2';

    private Platform $platform;

    protected function setUp(): void
    {
        parent::setUp();
        $this->platform = Platform::factory()->create();
    }

    #[Test]
    public function can_subscribe_with_valid_email(): void
    {
        Mail::fake();
        $response = $this->postJson($this->apiUrl('newsletter/subscriptions'), [
            'email' => 'subscriber@example.com',
            'platform_id' => $this->platform->id,
        ]);
        $this->assertSuccessResponse($response, 201);
        $response->assertJson(['message' => 'Suscripcion creada. Revisa tu email para verificar.']);
    }

    #[Test]
    public function subscribe_succeeds_with_name(): void
    {
        Mail::fake();
        $response = $this->postJson($this->apiUrl('newsletter/subscriptions'), [
            'email' => 'test@example.com',
            'name' => 'Nombre Test',
            'platform_id' => $this->platform->id,
        ]);
        $this->assertSuccessResponse($response, 201);
    }

    #[Test]
    public function subscribe_fails_without_email(): void
    {
        $response = $this->postJson($this->apiUrl('newsletter/subscriptions'), []);
        $this->assertErrorResponse($response, 422);
        $response->assertJsonValidationErrors(['email']);
    }

    #[Test]
    public function subscribe_fails_with_invalid_email(): void
    {
        $response = $this->postJson($this->apiUrl('newsletter/subscriptions'), [
            'email' => 'invalid',
            'platform_id' => $this->platform->id,
        ]);
        $this->assertErrorResponse($response, 422);
        $response->assertJsonValidationErrors(['email']);
    }

    #[Test]
    public function verify_fails_with_invalid_token(): void
    {
        $response = $this->postJson($this->apiUrl('newsletter/subscriptions/token-invalido/confirmation'));
        $this->assertErrorResponse($response, 404);
        $response->assertJson(['message' => 'Token inválido']);
    }

    #[Test]
    public function unsubscribe_fails_with_invalid_token(): void
    {
        $response = $this->deleteJson($this->apiUrl('newsletter/subscriptions/token-invalido'));
        $this->assertErrorResponse($response, 404);
        $response->assertJson(['message' => 'Token inválido']);
    }

    #[Test]
    public function a_nonexistent_platform_returns_422_and_not_500(): void
    {
        // `platform_id` entraba por `request()` dentro del servicio sin ninguna
        // regla, así que un id inexistente reventaba contra la clave foránea
        // (auditoría A7).
        $response = $this->postJson($this->apiUrl('newsletter/subscriptions'), [
            'email' => 'alguien@example.com',
            'platform_id' => 999999,
        ]);

        $this->assertErrorResponse($response, 422);
        $response->assertJsonValidationErrors(['platform_id']);
    }

    #[Test]
    public function resending_verification_answers_the_same_whether_the_subscription_exists_or_not(): void
    {
        // Antes devolvía 404 si el email no estaba suscrito y 200 si sí: un
        // oráculo público para comprobar si una dirección está en la lista, y
        // de paso una forma de inundar el buzón de un tercero (auditoría A6).
        Mail::fake();

        $this->postJson($this->apiUrl('newsletter/subscriptions'), [
            'email' => 'existe@example.com',
            'platform_id' => $this->platform->id,
        ])->assertStatus(201);

        $existente = $this->postJson($this->apiUrl('newsletter/subscriptions/verification'), [
            'email' => 'existe@example.com',
            'platform_id' => $this->platform->id,
        ]);

        $inexistente = $this->postJson($this->apiUrl('newsletter/subscriptions/verification'), [
            'email' => 'no-existe@example.com',
            'platform_id' => $this->platform->id,
        ]);

        $existente->assertStatus(200);
        $inexistente->assertStatus(200);
        $this->assertSame(
            $existente->json('message'),
            $inexistente->json('message'),
            'La respuesta debe ser idéntica para no revelar si el email está suscrito.'
        );
    }

    #[Test]
    public function a_device_token_cannot_see_the_statistics(): void
    {
        // El gate `view-statistics` existía desde el principio y esta ruta no
        // lo llamaba (auditoría A2).
        $user = $this->createAuthenticatedUser();

        $this->getJson($this->apiUrl('newsletter/subscriptions/stats'), $this->guestHeaders())->assertStatus(401);
        $this->getJson(
            $this->apiUrl('newsletter/subscriptions/stats'),
            $this->moduleHeaders($user, TokenAbilities::WEATHERSTATION_WRITE)
        )->assertStatus(403);
        $this->getJson($this->apiUrl('newsletter/subscriptions/stats'), $this->authenticatedHeaders($user))->assertStatus(403);
        $this->getJson($this->apiUrl('newsletter/subscriptions/stats'), $this->asAdmin())->assertStatus(200);
    }

    // ─── Baja de un clic (POST /newsletter/subscriptions/{token}/unsubscription) ───

    #[Test]
    public function can_unsubscribe_with_a_valid_token(): void
    {
        // Es la URL de la cabecera List-Unsubscribe (RFC 8058), así que va por
        // POST: en GET la abrían los antivirus y los clientes de correo al
        // hacer prefetch, dando de baja a quien no lo había pedido.
        $suscripcion = $this->makeSubscription();

        $response = $this->postJson(
            $this->apiUrl('newsletter/subscriptions/'.$suscripcion->unsubscribe_token.'/unsubscription')
        );

        $this->assertSuccessResponse($response);

        $suscripcion->refresh();
        $this->assertNotNull($suscripcion->unsubscribed_at);
    }

    #[Test]
    public function unsubscribing_with_an_unknown_token_is_a_404(): void
    {
        $this->postJson($this->apiUrl('newsletter/subscriptions/token-que-no-existe/unsubscription'))
            ->assertStatus(404);
    }

    #[Test]
    public function unsubscribing_is_not_available_over_get(): void
    {
        // Lo que importa no es el código exacto —la API convierte el 405 en un
        // 404 por su fallback— sino que un GET NO dé de baja a nadie: era el
        // motivo de pasar esta ruta a POST.
        $suscripcion = $this->makeSubscription();

        $response = $this->getJson(
            $this->apiUrl('newsletter/subscriptions/'.$suscripcion->unsubscribe_token.'/unsubscription')
        );

        $this->assertContains($response->getStatusCode(), [404, 405]);

        $suscripcion->refresh();
        $this->assertNull($suscripcion->unsubscribed_at);
    }

    private function makeSubscription(): Newsletter
    {
        $platform = Platform::factory()->create();

        return Newsletter::create([
            'platform_id' => $platform->id,
            'email' => 'suscriptor@example.com',
            'name' => 'Suscriptor',
            'is_verified' => true,
            'verified_at' => now(),
            'verification_token' => Str::random(40),
            'unsubscribe_token' => Str::random(40),
            'status' => 'active',
            'language' => 'es',
        ]);
    }
}
