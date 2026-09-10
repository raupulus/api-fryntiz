<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V2;

use App\Models\Hardware\HardwareDevice;
use App\Models\KeyCounter\Keyboard;
use App\Models\KeyCounter\Mouse;
use App\Models\User;
use App\Support\Auth\TokenAbilities;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Api\ApiTestCase;

/**
 * `GET /keycounter/summary` — el acumulado que pide un contador al arrancar.
 *
 * Apagarse o reiniciarse le borra lo que llevaba del día. Sin esto empieza de
 * cero y enseña un total falso hasta medianoche.
 */
class KeyCounterSummaryTest extends ApiTestCase
{
    protected string $apiPrefix = 'api/v2';

    private User $user;

    private HardwareDevice $keyboardDevice;

    private HardwareDevice $otherDevice;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = $this->createAuthenticatedUser();
        $this->keyboardDevice = HardwareDevice::create(['user_id' => $this->user->id, 'name' => 'Thinkpad']);
        $this->otherDevice = HardwareDevice::create(['user_id' => $this->user->id, 'name' => 'Sobremesa']);
    }

    #[Test]
    public function it_sums_todays_data_and_keeps_the_maximums(): void
    {
        $this->createStreak($this->keyboardDevice, pulsations: 1200, specialKeys: 50, score: 80, at: now());
        $this->createStreak($this->keyboardDevice, pulsations: 420, specialKeys: 12, score: 87, at: now()->subHours(2));
        // De ayer: no cuenta.
        $this->createStreak($this->keyboardDevice, pulsations: 9999, specialKeys: 999, score: 999, at: now()->subDay());

        $response = $this->summaryRequest();

        $response->assertOk()
            ->assertJsonPath('data.period', 'today')
            ->assertJsonPath('data.pulsations_total', 1620)
            ->assertJsonPath('data.pulsations_total_special_keys', 62)
            // El máximo del periodo, para no perder el récord al reiniciar.
            ->assertJsonPath('data.combo_score', 87)
            ->assertJsonPath('data.pulsation_high', 1200)
            ->assertJsonPath('data.sessions', 2);
    }

    #[Test]
    public function without_the_date_parameter_it_defaults_to_today(): void
    {
        $this->createStreak($this->keyboardDevice, pulsations: 500, specialKeys: 10, score: 40, at: now());

        $this->summaryRequest('')->assertOk()
            ->assertJsonPath('data.period', 'today')
            ->assertJsonPath('data.pulsations_total', 500);
    }

    #[Test]
    public function the_current_month_includes_previous_days(): void
    {
        $this->createStreak($this->keyboardDevice, pulsations: 500, specialKeys: 10, score: 40, at: now());
        $this->createStreak($this->keyboardDevice, pulsations: 700, specialKeys: 20, score: 50, at: now()->startOfMonth());

        $this->summaryRequest('date=month')->assertOk()
            ->assertJsonPath('data.period', 'month')
            ->assertJsonPath('data.pulsations_total', 1200);
    }

    #[Test]
    public function a_specific_day_of_a_specific_month(): void
    {
        $this->createStreak($this->keyboardDevice, pulsations: 333, specialKeys: 3, score: 30, at: '2026-03-15 10:00:00');
        $this->createStreak($this->keyboardDevice, pulsations: 111, specialKeys: 1, score: 10, at: '2026-03-16 10:00:00');

        $this->summaryRequest('date=2026-03-15')->assertOk()
            ->assertJsonPath('data.period', '2026-03-15')
            ->assertJsonPath('data.pulsations_total', 333);
    }

    #[Test]
    public function a_whole_specific_month(): void
    {
        $this->createStreak($this->keyboardDevice, pulsations: 333, specialKeys: 3, score: 30, at: '2026-03-01 00:30:00');
        $this->createStreak($this->keyboardDevice, pulsations: 111, specialKeys: 1, score: 10, at: '2026-03-31 23:30:00');
        $this->createStreak($this->keyboardDevice, pulsations: 999, specialKeys: 9, score: 90, at: '2026-04-01 00:30:00');

        $this->summaryRequest('date=2026-03')->assertOk()
            ->assertJsonPath('data.pulsations_total', 444);
    }

    /**
     * El resumen es de **un** cacharro: el que pregunta. Lo que cuente el de al
     * lado no es asunto suyo.
     */
    #[Test]
    public function it_returns_only_the_requesting_devices_data(): void
    {
        $this->createStreak($this->keyboardDevice, pulsations: 1000, specialKeys: 10, score: 50, at: now());
        $this->createStreak($this->otherDevice, pulsations: 2000, specialKeys: 20, score: 60, at: now());

        $this->summaryRequest()->assertOk()
            ->assertJsonPath('data.hardware_device_id', $this->keyboardDevice->id)
            ->assertJsonPath('data.pulsations_total', 1000);
    }

    #[Test]
    public function device_id_is_required(): void
    {
        $response = $this->getJson(
            $this->apiUrl('keycounter/summary?date=today'),
            $this->moduleHeaders($this->user, TokenAbilities::KEYCOUNTER_READ)
        );

        $this->assertErrorResponse($response, 422);
        $response->assertJsonValidationErrors(['device_id']);
    }

    #[Test]
    public function the_mouse_goes_in_its_own_block(): void
    {
        Mouse::create([
            'user_id' => $this->user->id,
            'hardware_device_id' => $this->keyboardDevice->id,
            'start_at' => now()->subMinutes(5),
            'end_at' => now(),
            'duration' => 300,
            'clicks_left' => 200,
            'clicks_right' => 40,
            'clicks_middle' => 10,
            'total_clicks' => 250,
            'clicks_average' => 0.8,
            'weekday' => 1,
        ]);

        $this->summaryRequest()->assertOk()
            ->assertJsonPath('data.mouse.clicks_total', 250)
            ->assertJsonPath('data.mouse.clicks_high', 250)
            ->assertJsonPath('data.mouse.sessions', 1);
    }

    /**
     * Un token ligado a un cacharro no puede preguntar por el de al lado.
     */
    #[Test]
    public function a_token_bound_to_a_device_cannot_reach_another(): void
    {
        $this->createStreak($this->keyboardDevice, pulsations: 1000, specialKeys: 10, score: 50, at: now());
        $this->createStreak($this->otherDevice, pulsations: 2000, specialKeys: 20, score: 60, at: now());

        $headers = $this->headersWithAbilities($this->user, [
            TokenAbilities::KEYCOUNTER_READ,
            TokenAbilities::forDevice($this->keyboardDevice),
        ]);

        $this->getJson($this->apiUrl('keycounter/summary?device_id='.$this->keyboardDevice->id.'&date=today'), $headers)
            ->assertOk()
            ->assertJsonPath('data.pulsations_total', 1000);

        $foreign = $this->getJson(
            $this->apiUrl('keycounter/summary?device_id='.$this->otherDevice->id.'&date=today'),
            $headers
        );

        $this->assertErrorResponse($foreign, 422);
        $foreign->assertJsonValidationErrors(['device_id']);
    }

    #[Test]
    public function a_period_that_does_not_exist_returns_422(): void
    {
        $this->assertErrorResponse($this->summaryRequest('date=basura'), 422);
        $this->assertErrorResponse($this->summaryRequest('date=2026-13-45'), 422);
    }

    #[Test]
    public function it_cannot_request_someone_elses_device(): void
    {
        $foreignDevice = HardwareDevice::create([
            'user_id' => $this->createAuthenticatedUser()->id,
            'name' => 'De otro',
        ]);

        $response = $this->getJson(
            $this->apiUrl('keycounter/summary?device_id='.$foreignDevice->id),
            $this->moduleHeaders($this->user, TokenAbilities::KEYCOUNTER_READ)
        );

        $this->assertErrorResponse($response, 422);
    }

    #[Test]
    public function it_requires_the_read_ability(): void
    {
        $url = 'keycounter/summary?device_id='.$this->keyboardDevice->id;

        $this->getJson($this->apiUrl($url), $this->guestHeaders())->assertUnauthorized();

        $this->getJson(
            $this->apiUrl($url),
            $this->moduleHeaders($this->user, TokenAbilities::KEYCOUNTER_WRITE)
        )->assertForbidden();
    }

    /**
     * `device_id` es obligatorio, así que va siempre salvo que la prueba sea
     * justamente que falta.
     */
    private function summaryRequest(string $query = 'date=today'): TestResponse
    {
        $url = 'keycounter/summary?device_id='.$this->keyboardDevice->id.($query === '' ? '' : '&'.$query);

        return $this->getJson(
            $this->apiUrl($url),
            $this->moduleHeaders($this->user, TokenAbilities::KEYCOUNTER_READ)
        );
    }

    private function createStreak(
        HardwareDevice $device,
        int $pulsations,
        int $specialKeys,
        int $score,
        mixed $at
    ): void {
        $streak = Keyboard::create([
            'user_id' => $this->user->id,
            'hardware_device_id' => $device->id,
            'start_at' => $at,
            'end_at' => $at,
            'duration' => 300,
            'pulsations' => $pulsations,
            'pulsations_special_keys' => $specialKeys,
            'pulsation_average' => 2.0,
            'score' => $score,
            'weekday' => 1,
        ]);

        // `created_at` no está en `$fillable`, así que `create()` lo ignora y
        // pone la hora actual. El resumen agrupa por esa columna —igual que la
        // web de KeyCounter—, así que aquí hay que forzarla.
        $streak->forceFill(['created_at' => $at])->save();
    }
}
