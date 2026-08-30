<?php

declare(strict_types=1);

namespace Tests\Feature\WeatherStation;

use App\Events\WeatherStation\ReadingsReceived;
use App\Models\Hardware\HardwareDevice;
use App\Models\User;
use App\Support\Auth\TokenAbilities;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Api\ApiTestCase;

/**
 * El aviso por WebSocket de que una estación ha subido lecturas.
 *
 * Lo que se comprueba aquí es justo lo que fallaba antes sin que nadie se
 * enterara: que **se emite**. Los nueve eventos anteriores colgaban de
 * `$dispatchesEvents['created']` de cada modelo y el controlador inserta el
 * lote con `insert()` del query builder, que no dispara eventos de Eloquent.
 * Estaban ahí y no se emitían nunca.
 */
class ReadingsBroadcastTest extends ApiTestCase
{
    protected string $apiPrefix = 'api/v2';

    private User $user;

    private HardwareDevice $station;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = $this->createAuthenticatedUser(3);
        $this->station = HardwareDevice::create([
            'user_id' => $this->user->id,
            'name' => 'Estación de pruebas',
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function headerCases(): array
    {
        return $this->moduleHeaders($this->user, TokenAbilities::WEATHERSTATION_WRITE);
    }

    #[Test]
    public function uploading_a_reading_emits_the_event(): void
    {
        Event::fake([ReadingsReceived::class]);

        $this->postJson(
            $this->apiUrl("weather-stations/{$this->station->id}/temperatures"),
            ['value' => 21.4],
            $this->headerCases()
        )->assertStatus(201);

        Event::assertDispatched(
            ReadingsReceived::class,
            fn (ReadingsReceived $event): bool => $event->station === $this->station->id
                && array_keys($event->sensors) === ['temperatures']
                && count($event->sensors['temperatures']) === 1
        );
    }

    /**
     * Once sensores en una subida son once lecturas, pero **un** cambio de
     * estado. Emitir uno por sensor multiplicaba por once los mensajes sin
     * decirle nada nuevo a nadie.
     */
    #[Test]
    public function the_multisensor_batch_emits_a_single_event_with_everything_in_it(): void
    {
        Event::fake([ReadingsReceived::class]);

        $this->postJson(
            $this->apiUrl("weather-stations/{$this->station->id}/readings"),
            ['data' => [
                'temperature' => [['value' => 21.4]],
                'humidity' => [['value' => 63.2]],
                'pressure' => [['value' => 1013.7]],
            ]],
            $this->headerCases()
        )->assertStatus(201);

        Event::assertDispatchedTimes(ReadingsReceived::class, 1);

        Event::assertDispatched(ReadingsReceived::class, function (ReadingsReceived $event): bool {
            $sensors = array_keys($event->sensors);
            sort($sensors);

            return $event->station === $this->station->id
                && $sensors === ['humidities', 'pressures', 'temperatures'];
        });
    }

    /**
     * El canal es público y su nombre es contrato con las webs que consumen la
     * API: si cambia, dejan de recibir sin que nada aquí avise.
     */
    #[Test]
    public function the_channel_and_event_name_are_the_agreed_ones(): void
    {
        $event = new ReadingsReceived(7, ['temperatures' => [['value' => 21.4]]]);

        $this->assertSame('weather-station.7', $event->broadcastOn()->name);
        $this->assertSame('readings.received', $event->broadcastAs());

        $payload = $event->broadcastWith();
        $this->assertSame(7, $payload['station_id']);
        $this->assertSame(['temperatures'], array_keys($payload['sensors']));
        $this->assertArrayHasKey('at', $payload);
    }

    /**
     * Si el INSERT se deshace nadie debe enterarse de una lectura que no
     * existe: por eso el evento se emite después de la transacción, y una
     * petición rechazada no emite nada.
     */
    #[Test]
    public function a_rejected_request_emits_nothing(): void
    {
        Event::fake([ReadingsReceived::class]);

        $this->postJson(
            $this->apiUrl("weather-stations/{$this->station->id}/temperatures"),
            [],
            $this->headerCases()
        )->assertStatus(422);

        Event::assertNotDispatched(ReadingsReceived::class);
    }
}
