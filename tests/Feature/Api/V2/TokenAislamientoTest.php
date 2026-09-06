<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V2;

use App\Models\Hardware\HardwareDevice;
use App\Models\Hardware\HardwareType;
use App\Models\User;
use App\Support\Auth\TokenAbilities;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Api\ApiTestCase;

/**
 * Aislamiento entre los tokens de dos cacharros del mismo dueño.
 *
 * El escenario real: un controlador solar en el campo (token de subida de
 * lecturas) y un portátil que sube pulsaciones de KeyCounter. Si roban el
 * primero —que está físicamente accesible— no puede tocar ni leer nada del
 * segundo.
 *
 * Quien pone el límite es la ability `device:{id}`, no la de módulo: sin ella,
 * `hardware:write` alcanzaría a cualquier dispositivo de la misma cuenta.
 */
class TokenAislamientoTest extends ApiTestCase
{
    protected string $apiPrefix = 'api/v2';

    private User $usuario;

    private HardwareDevice $placas;

    private HardwareDevice $portatil;

    protected function setUp(): void
    {
        parent::setUp();

        $this->usuario = $this->createAuthenticatedUser();
        $tipo = HardwareType::create(['name' => 'Controlador Solar', 'description' => 'Tipo de prueba']);

        $this->placas = HardwareDevice::create([
            'user_id' => $this->usuario->id,
            'hardware_type_id' => $tipo->id,
            'name' => 'renogy',
            'name_friendly' => 'Renogy',
        ]);

        $this->portatil = HardwareDevice::create([
            'user_id' => $this->usuario->id,
            'hardware_type_id' => $tipo->id,
            'name' => 'thinkpad',
            'name_friendly' => 'Thinkpad',
        ]);
    }

    #[Test]
    public function el_token_de_las_placas_sube_sus_propias_lecturas(): void
    {
        $response = $this->postJson(
            $this->apiUrl('hardware/solar-readings'),
            $this->lecturaSolar($this->placas),
            $this->tokenDeLasPlacas()
        );

        $response->assertSuccessful();
    }

    #[Test]
    public function el_token_de_las_placas_no_escribe_lecturas_de_otro_dispositivo(): void
    {
        $response = $this->postJson(
            $this->apiUrl('hardware/solar-readings'),
            $this->lecturaSolar($this->portatil),
            $this->tokenDeLasPlacas()
        );

        $this->assertErrorResponse($response, 422);
        $response->assertJsonValidationErrors(['hardware_device_id']);
    }

    #[Test]
    public function el_token_de_las_placas_no_toca_el_estado_de_ningun_dispositivo(): void
    {
        // Ni el del portátil ni el suyo propio: subir vatios y reescribir el
        // último estado conocido del aparato son permisos distintos desde que
        // energía es su propio módulo.
        foreach ([$this->portatil, $this->placas] as $dispositivo) {
            $response = $this->putJson(
                $this->apiUrl('hardware/devices/'.$dispositivo->id.'/status'),
                ['uptime' => 120],
                $this->tokenDeLasPlacas()
            );

            $this->assertErrorResponse($response, 403);
        }
    }

    #[Test]
    public function el_token_de_las_placas_lee_sus_lecturas_pero_no_las_del_otro(): void
    {
        $this->postJson(
            $this->apiUrl('hardware/solar-readings'),
            $this->lecturaSolar($this->placas),
            $this->tokenDeLasPlacas()
        )->assertSuccessful();

        // Con `hardwareenergy:read` sólo alcanza las de los dispositivos que
        // declara su token.
        $headers = $this->headersWithAbilities($this->usuario, [
            TokenAbilities::HARDWAREENERGY_READ,
            TokenAbilities::forDevice($this->placas),
        ]);

        $response = $this->getJson($this->apiUrl('hardware/solar-readings'), $headers);

        $response->assertSuccessful();

        foreach ($response->json('data') as $lectura) {
            $this->assertSame($this->placas->id, $lectura['hardware_device_id']);
        }
    }

    #[Test]
    public function el_token_de_energia_no_lee_el_inventario(): void
    {
        $headers = $this->headersWithAbilities($this->usuario, [
            TokenAbilities::HARDWAREENERGY_READ,
            TokenAbilities::forDevice($this->placas),
        ]);

        $this->assertErrorResponse($this->getJson($this->apiUrl('hardware/devices'), $headers), 403);
    }

    #[Test]
    public function el_token_de_las_placas_no_lista_el_parque_de_su_dueno(): void
    {
        // Sin `hardware:read` no llega ni a la ruta del inventario.
        $response = $this->getJson($this->apiUrl('hardware/devices'), $this->tokenDeLasPlacas());

        $this->assertErrorResponse($response, 403);
    }

    #[Test]
    public function el_token_de_las_placas_no_lee_las_sesiones_de_keycounter(): void
    {
        $response = $this->getJson($this->apiUrl('keycounter/keyboard-sessions'), $this->tokenDeLasPlacas());

        $this->assertErrorResponse($response, 403);
    }

    #[Test]
    public function el_token_de_las_placas_no_sube_pulsaciones_del_portatil(): void
    {
        $response = $this->postJson(
            $this->apiUrl('keycounter/keyboard-sessions'),
            $this->sesionDeTeclado($this->portatil),
            $this->tokenDeLasPlacas()
        );

        $this->assertErrorResponse($response, 403);
    }

    #[Test]
    public function el_token_del_portatil_sube_sus_pulsaciones_pero_no_las_del_otro(): void
    {
        $headers = $this->headersWithAbilities($this->usuario, [
            TokenAbilities::KEYCOUNTER_WRITE,
            TokenAbilities::forDevice($this->portatil),
        ]);

        $propia = $this->postJson(
            $this->apiUrl('keycounter/keyboard-sessions'),
            $this->sesionDeTeclado($this->portatil),
            $headers
        );
        $propia->assertSuccessful();

        $ajena = $this->postJson(
            $this->apiUrl('keycounter/keyboard-sessions'),
            $this->sesionDeTeclado($this->placas),
            $headers
        );
        $this->assertErrorResponse($ajena, 422);
        $ajena->assertJsonValidationErrors(['hardware_device_id']);
    }

    #[Test]
    public function el_token_del_portatil_no_escribe_lecturas_solares(): void
    {
        $headers = $this->headersWithAbilities($this->usuario, [
            TokenAbilities::KEYCOUNTER_WRITE,
            TokenAbilities::forDevice($this->portatil),
        ]);

        $response = $this->postJson(
            $this->apiUrl('hardware/solar-readings'),
            $this->lecturaSolar($this->portatil),
            $headers
        );

        $this->assertErrorResponse($response, 403);
    }

    /**
     * Token tal y como se emite para un controlador solar: escritura del
     * módulo Hardware Energy y nada más, ligado a ese aparato.
     *
     * Hasta el 2026-09-06 llevaba `hardware:write`, que además de las lecturas
     * le abría el estado del aparato. Son dos permisos distintos y se conceden
     * por separado.
     *
     * @return array<string, string>
     */
    private function tokenDeLasPlacas(): array
    {
        return $this->headersWithAbilities($this->usuario, [
            TokenAbilities::HARDWAREENERGY_WRITE,
            TokenAbilities::forDevice($this->placas),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function lecturaSolar(HardwareDevice $device): array
    {
        return [
            'hardware_device_id' => $device->id,
            'date' => now()->toDateString(),
            'read_at' => now()->format('Y-m-d H:i:s'),
            'battery_voltage' => 13.2,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function sesionDeTeclado(HardwareDevice $device): array
    {
        return [
            'hardware_device_id' => $device->id,
            'user_id' => $this->usuario->id,
            'start_at' => now()->subMinutes(5)->format('Y-m-d H:i:s'),
            'end_at' => now()->format('Y-m-d H:i:s'),
            'duration' => 300,
            'pulsations' => 500,
            'pulsations_special_keys' => 20,
            'pulsation_average' => 1.7,
            'score' => 50,
            'weekday' => 1,
        ];
    }
}
