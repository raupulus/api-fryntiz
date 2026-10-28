<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V2;

use App\Models\Hardware\HardwareDevice;
use App\Models\KeyCounter\Keyboard;
use App\Models\KeyCounter\Mouse;
use App\Models\User;
use App\Support\Auth\TokenAbilities;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Api\ApiTestCase;

/**
 * `GET /keycounter/stats` — Estadísticas acumuladas de pulsaciones y clicks del usuario.
 */
class KeyCounterStatsTest extends ApiTestCase
{
    protected string $apiPrefix = 'api/v2';

    private User $user;

    private HardwareDevice $deviceA;

    private HardwareDevice $deviceB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(today()->setTime(14, 0));

        $this->user = $this->createAuthenticatedUser();
        $this->deviceA = HardwareDevice::create(['user_id' => $this->user->id, 'name' => 'Portatil']);
        $this->deviceB = HardwareDevice::create(['user_id' => $this->user->id, 'name' => 'Sobremesa']);
    }

    #[Test]
    public function it_returns_zero_stats_when_no_records_exist(): void
    {
        $response = $this->getJson(
            $this->apiUrl('keycounter/stats'),
            $this->moduleHeaders($this->user, TokenAbilities::KEYCOUNTER_READ)
        );

        $response->assertOk()
            ->assertJsonPath('data.user_id', $this->user->id)
            ->assertJsonPath('data.lifetime_pulsations', 0)
            ->assertJsonPath('data.today_pulsations', 0)
            ->assertJsonPath('data.last_hour_pulsations', 0)
            ->assertJsonPath('data.lifetime_clicks', 0)
            ->assertJsonPath('data.today_clicks', 0)
            ->assertJsonPath('data.last_hour_clicks', 0);
    }

    #[Test]
    public function it_aggregates_pulsations_across_all_user_devices_for_all_time_today_and_last_hour(): void
    {
        // Dispositivo A: hace 30 minutos (última hora y hoy) -> 100
        $this->createKeyboardStreak($this->deviceA, 100, now()->subMinutes(30));
        // Dispositivo A: hace 3 horas (hoy, pero no última hora) -> 200
        $this->createKeyboardStreak($this->deviceA, 200, now()->subHours(3));
        // Dispositivo A: hace 2 días (lifetime, no hoy) -> 500
        $this->createKeyboardStreak($this->deviceA, 500, now()->subDays(2));

        // Dispositivo B: hace 15 minutos (última hora y hoy) -> 50
        $this->createKeyboardStreak($this->deviceB, 50, now()->subMinutes(15));
        // Dispositivo B: hace 5 horas (hoy, pero no última hora) -> 150
        $this->createKeyboardStreak($this->deviceB, 150, now()->subHours(5));
        // Dispositivo B: hace 5 días (lifetime, no hoy) -> 1000
        $this->createKeyboardStreak($this->deviceB, 1000, now()->subDays(5));

        // Otro usuario: no debe sumarse
        $otherUser = User::factory()->create();
        $otherDevice = HardwareDevice::create(['user_id' => $otherUser->id, 'name' => 'Otro']);
        $this->createKeyboardStreakForUser($otherUser, $otherDevice, 9999, now()->subMinutes(10));

        $response = $this->getJson(
            $this->apiUrl('keycounter/stats'),
            $this->moduleHeaders($this->user, TokenAbilities::KEYCOUNTER_READ)
        );

        $response->assertOk()
            ->assertJsonPath('data.user_id', $this->user->id)
            // Lifetime: 100 + 200 + 500 + 50 + 150 + 1000 = 2000
            ->assertJsonPath('data.lifetime_pulsations', 2000)
            // Today: 100 + 200 + 50 + 150 = 500
            ->assertJsonPath('data.today_pulsations', 500)
            // Last hour: 100 + 50 = 150
            ->assertJsonPath('data.last_hour_pulsations', 150);
    }

    #[Test]
    public function it_aggregates_mouse_clicks_across_all_user_devices(): void
    {
        // Dispositivo A: hace 20 minutos (última hora y hoy) -> 30 clicks
        $this->createMouseStreak($this->deviceA, 30, now()->subMinutes(20));
        // Dispositivo B: hace 2 horas (hoy, no última hora) -> 70 clicks
        $this->createMouseStreak($this->deviceB, 70, now()->subHours(2));
        // Dispositivo A: hace 3 días (lifetime, no hoy) -> 200 clicks
        $this->createMouseStreak($this->deviceA, 200, now()->subDays(3));

        $response = $this->getJson(
            $this->apiUrl('keycounter/stats'),
            $this->moduleHeaders($this->user, TokenAbilities::KEYCOUNTER_READ)
        );

        $response->assertOk()
            ->assertJsonPath('data.lifetime_clicks', 300)
            ->assertJsonPath('data.today_clicks', 100)
            ->assertJsonPath('data.last_hour_clicks', 30);
    }

    #[Test]
    public function it_requires_authentication(): void
    {
        $this->getJson($this->apiUrl('keycounter/stats'), $this->guestHeaders())
            ->assertUnauthorized();
    }

    #[Test]
    public function it_requires_keycounter_read_ability(): void
    {
        $this->getJson(
            $this->apiUrl('keycounter/stats'),
            $this->moduleHeaders($this->user, TokenAbilities::KEYCOUNTER_WRITE)
        )->assertForbidden();
    }

    #[Test]
    public function it_rejects_inactive_users(): void
    {
        $this->user->update(['is_active' => false]);

        $this->getJson(
            $this->apiUrl('keycounter/stats'),
            $this->moduleHeaders($this->user, TokenAbilities::KEYCOUNTER_READ)
        )->assertUnauthorized();
    }

    private function createKeyboardStreak(HardwareDevice $device, int $pulsations, mixed $at): void
    {
        $this->createKeyboardStreakForUser($this->user, $device, $pulsations, $at);
    }

    private function createKeyboardStreakForUser(User $user, HardwareDevice $device, int $pulsations, mixed $at): void
    {
        $streak = Keyboard::create([
            'user_id' => $user->id,
            'hardware_device_id' => $device->id,
            'start_at' => $at,
            'end_at' => $at,
            'duration' => 60,
            'pulsations' => $pulsations,
            'pulsations_special_keys' => 0,
            'pulsation_average' => 1.5,
            'score' => 10,
            'weekday' => 1,
        ]);

        $streak->forceFill(['created_at' => $at])->save();
    }

    private function createMouseStreak(HardwareDevice $device, int $clicks, mixed $at): void
    {
        $streak = Mouse::create([
            'user_id' => $this->user->id,
            'hardware_device_id' => $device->id,
            'start_at' => $at,
            'end_at' => $at,
            'duration' => 60,
            'clicks_left' => $clicks,
            'clicks_right' => 0,
            'clicks_middle' => 0,
            'total_clicks' => $clicks,
            'clicks_average' => 1.0,
            'weekday' => 1,
        ]);

        $streak->forceFill(['created_at' => $at])->save();
    }
}
