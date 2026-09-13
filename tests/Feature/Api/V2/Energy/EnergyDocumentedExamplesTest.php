<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V2\Energy;

use App\Models\Hardware\HardwareDevice;
use App\Models\Hardware\HardwareEnergy;
use App\Models\User;
use App\Support\Auth\TokenAbilities;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Api\ApiTestCase;

/**
 * Los ejemplos de `docs/info/api/v2/energy.md`, ejecutados de verdad.
 *
 * Ese documento es el que se copia a quien escribe el firmware, así que un
 * ejemplo que no funcione cuesta días de depuración en el lado equivocado. Aquí
 * se sacan los bloques ```json de la sección «Ejemplos completos», se montan los
 * elementos que cada uno necesita y se suben tal cual.
 *
 * Si alguien edita un ejemplo y se equivoca —un campo que no existe, una
 * estructura mal anidada, un canal que el servidor no sabe repartir— esto se
 * rompe antes de que el documento salga del repositorio.
 */
class EnergyDocumentedExamplesTest extends ApiTestCase
{
    protected string $apiPrefix = 'api/v2';

    /**
     * Los ejemplos de petición del contrato.
     *
     * Se filtran los bloques que no son peticiones —las respuestas llevan
     * `success`— y los que no traen bloque de energía.
     *
     * @return array<string, array{0: int, 1: array<string, mixed>}>
     */
    public static function ejemplosDelContrato(): array
    {
        $doc = file_get_contents(__DIR__.'/../../../../../docs/info/api/v2/energy.md');

        preg_match_all('/```json\n(.*?)```/s', $doc, $bloques);

        $casos = [];

        foreach ($bloques[1] as $indice => $json) {
            $payload = json_decode($json, true);

            if (! is_array($payload) || ! isset($payload['energy']) || isset($payload['success'])) {
                continue;
            }

            $casos["ejemplo #{$indice}"] = [$indice, $payload];
        }

        return $casos;
    }

    #[Test]
    public function the_contract_has_request_examples(): void
    {
        $this->assertGreaterThanOrEqual(
            4,
            count(self::ejemplosDelContrato()),
            'El contrato tiene que traer ejemplos de petición para los montajes que soporta.'
        );
    }

    /**
     * Monta los elementos que el propio payload da a entender que hacen falta,
     * **cada uno a su tensión**.
     *
     * La nominal de cada elemento sale de la tensión que trae el ejemplo, que es
     * como estaría configurada una instalación de verdad: un canal de 5 V se da
     * de alta a 5 V y no a 12. Poner a todos la misma tensión es justo el error
     * que el módulo existe para evitar.
     *
     * @param  array<string, mixed>  $payload
     */
    private function montarInstalacion(User $user, array $payload): HardwareDevice
    {
        $device = HardwareDevice::create(['user_id' => $user->id, 'name' => 'Aparato del ejemplo']);
        $energy = $payload['energy'];

        /** Tensión declarada en un bloque, o un respaldo razonable. */
        $tension = static fn (array $bloque, float $respaldo): float => isset($bloque['voltage'])
            ? (float) $bloque['voltage']
            : $respaldo;

        if (isset($energy['generator'])) {
            $nominal = $tension($energy['generator'], 24.0);

            HardwareEnergy::create([
                'hardware_device_id' => $device->id,
                'hardware_device_monitorized_id' => $device->id,
                'role' => HardwareEnergy::ROLE_GENERATOR,
                'sensor_position' => 0,
                'nominal_voltage' => $nominal,
                // Un panel va de 0 V de noche a su tensión de circuito abierto.
                'voltage_min' => 0.0,
                'voltage_max' => $nominal * 2,
                'is_active' => true,
            ]);
        }

        if (isset($energy['battery'])) {
            $nominal = $tension($energy['battery'], 12.0);

            HardwareEnergy::create([
                'hardware_device_id' => $device->id,
                'hardware_device_monitorized_id' => $device->id,
                'role' => HardwareEnergy::ROLE_BATTERY,
                'sensor_position' => 0,
                'nominal_voltage' => $nominal,
                // Las tensiones a 0 % y a 100 %, que es lo que calibran.
                'voltage_min' => round($nominal * 0.8, 2),
                'voltage_max' => round($nominal * 1.15, 2),
                'capacity_ah' => 250.0,
                'is_active' => true,
            ]);
        }

        foreach ($energy['loads'] ?? [] as $consumo) {
            $canal = (int) ($consumo['channel'] ?? $consumo['sensor_position'] ?? 0);
            $medido = HardwareDevice::create(['user_id' => $user->id, 'name' => "Medido {$canal}"]);

            HardwareEnergy::create([
                'hardware_device_id' => $device->id,
                'hardware_device_monitorized_id' => $medido->id,
                'role' => HardwareEnergy::ROLE_LOAD,
                'sensor_position' => $canal,
                'nominal_voltage' => $tension($consumo, 12.0),
                'is_active' => true,
            ]);
        }

        return $device;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    #[Test]
    #[DataProvider('ejemplosDelContrato')]
    public function a_documented_example_is_accepted_as_written(int $indice, array $payload): void
    {
        $user = $this->createAuthenticatedUser(3);
        $device = $this->montarInstalacion($user, $payload);

        $payload['hardware_device_id'] = $device->id;

        $respuesta = $this->postJson(
            $this->apiUrl('energy/readings'),
            $payload,
            $this->moduleHeaders($user, TokenAbilities::ENERGY_WRITE)
        );

        $respuesta->assertStatus(
            201,
            "El ejemplo #{$indice} del contrato no se acepta: ".json_encode($respuesta->json(), JSON_UNESCAPED_UNICODE)
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    #[Test]
    #[DataProvider('ejemplosDelContrato')]
    public function a_documented_example_stores_one_reading_per_block(int $indice, array $payload): void
    {
        $user = $this->createAuthenticatedUser(3);
        $device = $this->montarInstalacion($user, $payload);

        $payload['hardware_device_id'] = $device->id;

        $esperadas = (isset($payload['energy']['generator']) ? 1 : 0)
            + (isset($payload['energy']['battery']) ? 1 : 0)
            + count($payload['energy']['loads'] ?? []);

        $this->postJson(
            $this->apiUrl('energy/readings'),
            $payload,
            $this->moduleHeaders($user, TokenAbilities::ENERGY_WRITE)
        )->assertJsonCount($esperadas, 'data');
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    #[Test]
    #[DataProvider('ejemplosDelContrato')]
    public function a_documented_example_does_not_warn_about_anything(int $indice, array $payload): void
    {
        // Con la instalación bien montada, un ejemplo del contrato no debería
        // levantar un solo aviso. Si lo hace, o el ejemplo está mal o hay un
        // campo que el servidor no sabe colocar.
        $user = $this->createAuthenticatedUser(3);
        $device = $this->montarInstalacion($user, $payload);

        $payload['hardware_device_id'] = $device->id;

        $respuesta = $this->postJson(
            $this->apiUrl('energy/readings'),
            $payload,
            $this->moduleHeaders($user, TokenAbilities::ENERGY_WRITE)
        );

        $this->assertSame(
            [],
            $respuesta->json('warnings') ?? [],
            "El ejemplo #{$indice} levanta avisos que el documento no menciona."
        );
    }
}
