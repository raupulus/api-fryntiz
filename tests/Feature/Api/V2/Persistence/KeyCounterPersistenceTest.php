<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V2\Persistence;

use App\Models\Hardware\HardwareDevice;
use App\Models\KeyCounter\Keyboard;
use App\Models\KeyCounter\Mouse;
use App\Models\User;
use App\Support\Auth\TokenAbilities;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Api\ApiTestCase;
use Tests\Traits\AssertsPersistence;

/**
 * POST /api/v2/keycounter/keyboard y /mouse.
 *
 * Hasta ahora sólo tenían tests de 401 y de 422. Ninguno comprobaba que la
 * sesión de trabajo quedase guardada (N279).
 *
 * Ojo al `prepareForValidation()` de estos dos FormRequests: hace
 * `(int) $this->pulsations`, y `(int) null` es `0`. Un campo que no llega
 * queda indistinguible de un cero real. Los tests de "campo ausente" de abajo
 * dejan ese comportamiento por escrito.
 */
class KeyCounterPersistenceTest extends ApiTestCase
{
    use AssertsPersistence;

    protected string $apiPrefix = 'api/v2';

    private User $user;

    private HardwareDevice $device;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = $this->createAuthenticatedUser(3);
        $this->device = HardwareDevice::create([
            'user_id' => $this->user->id,
            'name' => 'Portátil de pruebas',
        ]);
    }

    /** @return array<string,mixed> */
    private function keyboardPayload(): array
    {
        return [
            'hardware_device_id' => $this->device->id,
            'user_id' => $this->user->id,
            'start_at' => '2026-08-24 09:00:00',
            'end_at' => '2026-08-24 11:30:00',
            'duration' => 9000,
            'pulsations' => 14837,
            'pulsations_special_keys' => 2104,
            'pulsation_average' => 1.65,
            'score' => 742,
            'weekday' => 0,
        ];
    }

    /** @return array<string,mixed> */
    private function mousePayload(): array
    {
        return [
            'hardware_device_id' => $this->device->id,
            'user_id' => $this->user->id,
            'start_at' => '2026-08-24 09:00:00',
            'end_at' => '2026-08-24 11:30:00',
            'duration' => 9000,
            'clicks_left' => 3120,
            'clicks_right' => 486,
            'clicks_middle' => 71,
            'total_clicks' => 3677,
            'clicks_average' => 1,
            'weekday' => 0,
        ];
    }

    #[Test]
    public function the_keyboard_session_is_stored_with_all_its_fields(): void
    {
        $payload = $this->keyboardPayload();

        $this->postJson($this->apiUrl('keycounter/keyboard-sessions'), $payload, $this->moduleHeaders($this->user, TokenAbilities::KEYCOUNTER_WRITE))
            ->assertStatus(201);

        $row = Keyboard::query()->latest('id')->first();

        $this->assertNotNull($row, 'La API respondió 201 y no hay ninguna fila en keycounter_keyboard.');
        $this->assertPersisted($row, $payload);
    }

    #[Test]
    public function the_mouse_session_is_stored_with_all_its_fields(): void
    {
        $payload = $this->mousePayload();

        $this->postJson($this->apiUrl('keycounter/mouse-sessions'), $payload, $this->moduleHeaders($this->user, TokenAbilities::KEYCOUNTER_WRITE))
            ->assertStatus(201);

        $row = Mouse::query()->latest('id')->first();

        $this->assertNotNull($row, 'La API respondió 201 y no hay ninguna fila en keycounter_mouse.');
        $this->assertPersisted($row, $payload);
    }

    #[Test]
    public function the_mouse_resource_does_not_return_a_score_that_does_not_exist(): void
    {
        // R-6: `MouseResource` declara `score`, y `keycounter_mouse` no tiene esa
        // columna (sí la tiene `keycounter_keyboard`). Sale NULL en cada respuesta.
        $response = $this->postJson(
            $this->apiUrl('keycounter/mouse-sessions'),
            $this->mousePayload(),
            $this->moduleHeaders($this->user, TokenAbilities::KEYCOUNTER_WRITE)
        );

        $response->assertStatus(201);

        $this->assertArrayNotHasKey(
            'score',
            $response->json('data') ?? [],
            'MouseResource sigue devolviendo `score`, que no es columna de keycounter_mouse: siempre NULL.'
        );
    }

    #[Test]
    public function a_missing_field_must_not_be_stored_as_zero(): void
    {
        // `prepareForValidation()` hace `(int) $this->score`, y `(int) null` es 0.
        // Con `score` obligatorio en las reglas, la petición debería ser un 422 —
        // no un 201 con un 0 inventado.
        $payload = $this->keyboardPayload();
        unset($payload['score']);

        $response = $this->postJson(
            $this->apiUrl('keycounter/keyboard-sessions'),
            $payload,
            $this->moduleHeaders($this->user, TokenAbilities::KEYCOUNTER_WRITE)
        );

        $this->assertErrorResponse($response, 422);
        $response->assertJsonValidationErrors(['score']);
        $this->assertSame(0, Keyboard::query()->count(), 'Se guardó una sesión con un `score` inventado a 0.');
    }

    #[Test]
    public function cannot_write_on_someone_elses_device(): void
    {
        $other = $this->createAuthenticatedUser(3);
        $foreign = HardwareDevice::create(['user_id' => $other->id, 'name' => 'Portátil ajeno']);

        $payload = array_merge($this->keyboardPayload(), ['hardware_device_id' => $foreign->id]);

        $this->assertErrorResponse(
            $this->postJson($this->apiUrl('keycounter/keyboard-sessions'), $payload, $this->moduleHeaders($this->user, TokenAbilities::KEYCOUNTER_WRITE)),
            422
        );

        $this->assertSame(0, Keyboard::query()->count());
    }

    /**
     * KeyCounter era uno de los siete endpoints IoT sin `hardware_device_info`
     * (AUDITORIA-HARDWARE-DEVICE-INFO.md): el dispositivo que sube pulsaciones
     * no podía reportar su propio estado (batería, temperatura, uptime...) en
     * la misma petición.
     */
    #[Test]
    public function keyboard_session_updates_the_device_status_when_hardware_device_info_is_sent(): void
    {
        $payload = array_merge($this->keyboardPayload(), [
            'hardware_device_info' => ['temp' => 39.5, 'battery_level' => 61, 'uptime' => 4200],
        ]);

        $this->postJson($this->apiUrl('keycounter/keyboard-sessions'), $payload, $this->moduleHeaders($this->user, TokenAbilities::KEYCOUNTER_WRITE))
            ->assertStatus(201);

        $this->device->refresh();

        $this->assertEqualsWithDelta(39.5, (float) $this->device->temp, 0.001);
        $this->assertSame(61, $this->device->battery_level);
        $this->assertSame(4200, $this->device->uptime);
        $this->assertNotNull($this->device->last_seen_at);
    }

    #[Test]
    public function mouse_session_updates_the_device_status_when_hardware_device_info_is_sent(): void
    {
        $payload = array_merge($this->mousePayload(), [
            'hardware_device_info' => ['voltage' => 4.9, 'battery_level' => 88],
        ]);

        $this->postJson($this->apiUrl('keycounter/mouse-sessions'), $payload, $this->moduleHeaders($this->user, TokenAbilities::KEYCOUNTER_WRITE))
            ->assertStatus(201);

        $this->device->refresh();

        $this->assertEqualsWithDelta(4.9, (float) $this->device->voltage, 0.001);
        $this->assertSame(88, $this->device->battery_level);
    }

    #[Test]
    public function keyboard_session_without_hardware_device_info_does_not_touch_the_device_status(): void
    {
        $this->postJson($this->apiUrl('keycounter/keyboard-sessions'), $this->keyboardPayload(), $this->moduleHeaders($this->user, TokenAbilities::KEYCOUNTER_WRITE))
            ->assertStatus(201);

        $this->device->refresh();

        $this->assertNull($this->device->last_seen_at, 'Omitir `hardware_device_info` no debe tocar el estado del dispositivo.');
    }
}
