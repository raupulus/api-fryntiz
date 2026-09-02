<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V2;

use App\Models\Hardware\HardwareDevice;
use App\Models\Hardware\HardwareType;
use App\Models\SmartPlant\SmartPlantPlant;
use App\Models\User;
use App\Support\Auth\TokenAbilities;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Api\ApiTestCase;

class SmartPlantTest extends ApiTestCase
{
    protected string $apiPrefix = 'api/v2';

    #[Test]
    public function cannot_store_register_unauthenticated(): void
    {
        $response = $this->postJson($this->apiUrl('smartplant/plants/1/readings'), [], $this->guestHeaders());
        $this->assertErrorResponse($response, 401);
    }

    #[Test]
    public function store_register_validates_required_fields(): void
    {
        $headers = $this->moduleHeaders($this->createAuthenticatedUser(), TokenAbilities::SMARTPLANT_WRITE);
        $response = $this->postJson($this->apiUrl('smartplant/plants/1/readings'), [], $headers);
        $this->assertErrorResponse($response, 422);
    }

    #[Test]
    public function store_register_validates_soil_humidity_required(): void
    {
        $headers = $this->moduleHeaders($this->createAuthenticatedUser(), TokenAbilities::SMARTPLANT_WRITE);
        $response = $this->postJson($this->apiUrl('smartplant/plants/1/readings'), [
            'plant_id' => 1,
            'hardware_device_id' => 1,
        ], $headers);
        $this->assertErrorResponse($response, 422);
        $response->assertJsonValidationErrors(['soil_humidity']);
    }

    // ─── Lectura (GET /smartplant/plants y sus lecturas) ───

    #[Test]
    public function can_list_own_plants(): void
    {
        $user = $this->createAuthenticatedUser();
        $this->makePlantFor($user, 'Albahaca');

        $response = $this->getJson(
            $this->apiUrl('smartplant/plants'),
            $this->moduleHeaders($user, TokenAbilities::SMARTPLANT_READ)
        );

        $this->assertPaginatedResponse($response);
        $this->assertSame(1, $response->json('meta.total'));
    }

    #[Test]
    public function the_plant_list_does_not_show_someone_elses_plants(): void
    {
        $user = $this->createAuthenticatedUser();
        $otro = $this->createAuthenticatedUser();
        $this->makePlantFor($otro, 'De otro');

        $response = $this->getJson(
            $this->apiUrl('smartplant/plants'),
            $this->moduleHeaders($user, TokenAbilities::SMARTPLANT_READ)
        );

        $this->assertSame(0, $response->json('meta.total'));
    }

    #[Test]
    public function cannot_list_plants_unauthenticated(): void
    {
        $this->assertErrorResponse(
            $this->getJson($this->apiUrl('smartplant/plants'), $this->guestHeaders()),
            401
        );
    }

    #[Test]
    public function can_read_the_readings_of_own_plant(): void
    {
        // Este test destapó que el endpoint devolvía 404 a todo el mundo: la
        // policy comprobaba una columna hardware_device_id que la tabla de
        // plantas no tiene. Ver SmartPlantPolicy::isOwnedBy().
        $user = $this->createAuthenticatedUser();
        $plant = $this->makePlantFor($user, 'Albahaca');

        $response = $this->getJson(
            $this->apiUrl('smartplant/plants/'.$plant->id.'/readings'),
            $this->moduleHeaders($user, TokenAbilities::SMARTPLANT_READ)
        );

        $this->assertPaginatedResponse($response);
    }

    #[Test]
    public function reading_someone_elses_plant_is_a_404_and_not_a_403(): void
    {
        // El mismo 404 si no existe que si es de otro: un 403 confirmaría que
        // esa planta existe, que es justo lo que no se quiere decir.
        $user = $this->createAuthenticatedUser();
        $otro = $this->createAuthenticatedUser();
        $ajena = $this->makePlantFor($otro, 'De otro');

        $this->getJson(
            $this->apiUrl('smartplant/plants/'.$ajena->id.'/readings'),
            $this->moduleHeaders($user, TokenAbilities::SMARTPLANT_READ)
        )->assertStatus(404);
    }

    #[Test]
    public function reading_a_plant_that_does_not_exist_is_a_404(): void
    {
        $user = $this->createAuthenticatedUser();

        $this->getJson(
            $this->apiUrl('smartplant/plants/999999/readings'),
            $this->moduleHeaders($user, TokenAbilities::SMARTPLANT_READ)
        )->assertStatus(404);
    }

    private function makeDeviceFor(User $user): HardwareDevice
    {
        $type = HardwareType::firstOrCreate(['name' => 'SmartPlant']);

        return HardwareDevice::create([
            'hardware_type_id' => $type->id,
            'user_id' => $user->id,
            'name' => 'Placa de pruebas '.uniqid(),
        ]);
    }

    private function makePlantFor(User $user, string $name, ?int $deviceId = null): SmartPlantPlant
    {
        return SmartPlantPlant::create([
            'user_id' => $user->id,
            'name' => $name,
            'name_scientific' => 'Ocimum basilicum',
            'description' => 'Planta de pruebas',
            'details' => 'Sin detalles',
            'start_at' => now()->subMonth(),
        ]);
    }

    #[Test]
    public function un_token_de_escritura_no_puede_leer(): void
    {
        // AR-S02: mismo caso que KeyCounter. El token de la planta sólo tiene
        // que subir lecturas, no listar el jardín entero.
        $headers = $this->moduleHeaders($this->createAuthenticatedUser(), TokenAbilities::SMARTPLANT_WRITE);

        $this->getJson($this->apiUrl('smartplant/plants'), $headers)->assertForbidden();
    }

    #[Test]
    public function un_token_de_lectura_no_puede_escribir(): void
    {
        $headers = $this->moduleHeaders($this->createAuthenticatedUser(), TokenAbilities::SMARTPLANT_READ);

        $this->postJson($this->apiUrl('smartplant/plants/1/readings'), [], $headers)->assertForbidden();
    }
}
