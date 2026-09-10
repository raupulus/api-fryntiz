<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Mail\NewsletterVerification;
use App\Models\Newsletter;
use App\Models\Platform;
use App\Services\Newsletter\NewsletterService;
use Database\Seeders\RolesTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class NewsletterServiceTest extends TestCase
{
    use RefreshDatabase;

    private NewsletterService $service;

    private Platform $platform;

    protected function setUp(): void
    {
        parent::setUp();

        // PlatformFactory crea su usuario, y users tiene FK a roles.
        (new RolesTableSeeder)->run();

        Mail::fake();

        $this->service = app(NewsletterService::class);
        $this->platform = Platform::factory()->create();
    }

    // ─── Alta ───

    #[Test]
    public function subscribing_leaves_the_subscription_unverified(): void
    {
        // Nadie queda suscrito por el mero hecho de escribir su correo: hasta
        // que no confirma, no cuenta.
        $newsletter = $this->service->subscribe('alguien@example.com', 'Alguien', $this->platform->id);

        $this->assertFalse((bool) $newsletter->is_verified);
        $this->assertSame(Newsletter::STATUS_INACTIVE, $newsletter->status);
    }

    #[Test]
    public function subscribing_generates_a_verification_token_and_sends_the_email(): void
    {
        $newsletter = $this->service->subscribe('alguien@example.com', 'Alguien', $this->platform->id);

        $this->assertNotEmpty($newsletter->verification_token);
        // El mailable es ShouldQueue, así que se encola en vez de enviarse.
        Mail::assertQueued(NewsletterVerification::class);
    }

    #[Test]
    public function subscribing_stores_the_request_context(): void
    {
        $newsletter = $this->service->subscribe('alguien@example.com', null, $this->platform->id, [
            'language' => 'en',
            'ip_address' => '10.0.0.1',
            'user_agent' => 'Un navegador',
        ]);

        $this->assertSame('en', $newsletter->language);
        $this->assertSame('10.0.0.1', $newsletter->ip_address);
    }

    // ─── Verificación ───

    #[Test]
    public function verifying_with_the_correct_token_activates_the_subscription(): void
    {
        $newsletter = $this->service->subscribe('alguien@example.com', 'Alguien', $this->platform->id);

        $this->assertTrue($this->service->verify($newsletter->verification_token));

        $newsletter->refresh();
        $this->assertTrue((bool) $newsletter->is_verified);
        $this->assertNotNull($newsletter->verified_at);
    }

    #[Test]
    public function verifying_with_an_unknown_token_returns_false(): void
    {
        $this->assertFalse($this->service->verify('token-que-no-existe'));
    }

    // ─── Baja ───

    #[Test]
    public function unsubscribing_with_the_correct_token_sets_the_timestamp(): void
    {
        $newsletter = $this->service->subscribe('alguien@example.com', 'Alguien', $this->platform->id);
        $newsletter->refresh();

        $this->assertTrue($this->service->unsubscribe($newsletter->unsubscribe_token));

        $newsletter->refresh();
        $this->assertNotNull($newsletter->unsubscribed_at);
    }

    #[Test]
    public function unsubscribing_with_an_unknown_token_returns_false(): void
    {
        $this->assertFalse($this->service->unsubscribe('token-que-no-existe'));
    }

    // ─── Reenvío ───

    #[Test]
    public function resending_to_someone_not_subscribed_returns_null(): void
    {
        // El controlador responde igual exista o no, para no convertir el
        // endpoint en un oráculo de qué direcciones están en la lista. Aquí,
        // en el servicio, sí se distingue.
        $this->assertNull($this->service->resendVerification('nadie@example.com', $this->platform->id));
    }

    #[Test]
    public function resending_to_someone_subscribed_returns_the_subscription(): void
    {
        $this->service->subscribe('alguien@example.com', 'Alguien', $this->platform->id);

        $this->assertNotNull($this->service->resendVerification('alguien@example.com', $this->platform->id));
    }

    // ─── Estadísticas ───

    #[Test]
    public function the_stats_count_the_subscriptions(): void
    {
        $this->service->subscribe('uno@example.com', 'Uno', $this->platform->id);
        $this->service->subscribe('dos@example.com', 'Dos', $this->platform->id);

        $stats = $this->service->stats($this->platform->id);

        $this->assertIsArray($stats);
        $this->assertNotEmpty($stats);
    }
}
