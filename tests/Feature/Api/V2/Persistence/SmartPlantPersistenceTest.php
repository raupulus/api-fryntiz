<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V2\Persistence;

use App\Models\Hardware\HardwareDevice;
use App\Models\SmartPlant\SmartPlantPlant;
use App\Models\SmartPlant\SmartPlantRegister;
use App\Models\User;
use App\Support\Auth\TokenAbilities;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Api\ApiTestCase;
use Tests\Traits\AssertsPersistence;

/**
 * POST /api/v2/smartplant/register.
 *
 * `SmartPlantTest` tenía 3 tests, todos de 401 y 422 (N279).
 *
 * Este módulo es el único cuyo FormRequest usa `$this->has(...)` en lugar de
 * castear a la brava, así que un campo ausente NO se convierte en 0. Los tests
 * lo dejan por escrito, porque es el patrón que hay que copiar en los demás.
 */
class SmartPlantPersistenceTest extends ApiTestCase
{
    use AssertsPersistence;

    protected string $apiPrefix = 'api/v2';

    private User $user;

    private HardwareDevice $device;

    private SmartPlantPlant $plant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = $this->createAuthenticatedUser(3);
        $this->device = HardwareDevice::create([
            'user_id' => $this->user->id,
            'name' => 'Sensor de bonsái',
        ]);
        $this->plant = $this->createPlant($this->user);
    }

    /**
     * `smartplant_plants` tiene cuatro columnas NOT NULL sin default
     * (name_scientific, description, details, start_at). Crear una planta sólo
     * con `name` revienta con 23502, así que va aquí y no suelto por los tests.
     */
    private function createPlant(User $duenyo, string $name = 'Bonsái de pruebas'): SmartPlantPlant
    {
        return SmartPlantPlant::create([
            'user_id' => $duenyo->id,
            'name' => $name,
            'name_scientific' => 'Ficus retusa',
            'description' => 'Planta de pruebas',
            'details' => 'Creada por el test de persistencia.',
            'start_at' => now(),
        ]);
    }

    /**
     * `uv`, `soil_humidity` y `soil_humidity_raw` son columnas `integer`: se
     * mandan enteros a propósito para que un fallo signifique "el campo se
     * perdió" y no "PostgreSQL redondeó un decimal".
     *
     * @return array<string,mixed>
     */
    private function payload(): array
    {
        return [
            'plant_id' => $this->plant->id,
            'hardware_device_id' => $this->device->id,
            'uv' => 3,
            'pressure' => 1011.4,
            'temperature' => 22.7,
            'humidity' => 58.0,
            'soil_humidity' => 41,
            'soil_humidity_raw' => 2380,
            'full_water_tank' => true,
            'waterpump_enabled' => false,
            'vaporizer_enabled' => false,
        ];
    }

    #[Test]
    public function the_plant_reading_is_stored_with_all_its_fields(): void
    {
        $payload = $this->payload();

        $this->postJson($this->apiUrl("smartplant/plants/{$this->plant->id}/readings"), $payload, $this->moduleHeaders($this->user, TokenAbilities::SMARTPLANT_WRITE))
            ->assertStatus(201);

        $row = SmartPlantRegister::query()->latest('id')->first();

        $this->assertNotNull($row, 'La API respondió 201 y no hay ninguna fila de registro.');
        $this->assertPersisted($row, $payload);
    }

    #[Test]
    public function the_reading_is_linked_to_the_plant(): void
    {
        // H5: en V1 se comprobaba la propiedad de la planta y v2 dejó de hacerlo.
        // Como mínimo, la lectura tiene que saber de qué planta es.
        $this->postJson($this->apiUrl("smartplant/plants/{$this->plant->id}/readings"), $this->payload(), $this->moduleHeaders($this->user, TokenAbilities::SMARTPLANT_WRITE))
            ->assertStatus(201);

        $row = SmartPlantRegister::query()->latest('id')->first();

        $this->assertSame($this->plant->id, (int) $row->plant_id, 'La lectura no quedó ligada a la planta.');
    }

    #[Test]
    public function a_missing_optional_field_is_stored_as_null_and_not_as_zero(): void
    {
        // Este FormRequest usa `$this->has(...)`, así que un sensor que no
        // reporta debe dejar NULL, no un 0 que contamine las medias.
        // Es el patrón correcto que le falta a Solar y a KeyCounter.
        $payload = $this->payload();
        unset($payload['uv']);

        $this->postJson($this->apiUrl("smartplant/plants/{$this->plant->id}/readings"), $payload, $this->moduleHeaders($this->user, TokenAbilities::SMARTPLANT_WRITE))
            ->assertStatus(201);

        $row = SmartPlantRegister::query()->latest('id')->first();

        $this->assertNull(
            $row->uv,
            'Un sensor UV ausente se guardó como 0: "sin dato" y "cero real" dejan de distinguirse.'
        );
    }

    /**
     * H5: la planta es la de la URL, y sólo la de la URL.
     *
     * Un `plant_id` en el cuerpo apuntando a la planta de otro no es un error
     * del cliente: es ruido, o un intento. En los dos casos se ignora, porque
     * `prepareForValidation()` reescribe `plant_id` con el de la ruta antes de
     * validar nada. Lo que se comprueba aquí es que la lectura acaba en la
     * planta propia y **no** en la ajena.
     */
    #[Test]
    public function cannot_write_on_someone_elses_plant(): void
    {
        $other = $this->createAuthenticatedUser(3);
        $suPlanta = $this->createPlant($other, 'Planta ajena');

        $payload = array_merge($this->payload(), ['plant_id' => $suPlanta->id]);

        $response = $this->postJson(
            $this->apiUrl("smartplant/plants/{$this->plant->id}/readings"),
            $payload,
            $this->moduleHeaders($this->user, TokenAbilities::SMARTPLANT_WRITE)
        );

        $response->assertStatus(201);

        $this->assertSame(
            0,
            SmartPlantRegister::query()->where('plant_id', $suPlanta->id)->count(),
            'Se guardó una lectura en la planta de otro usuario (H5).'
        );
        $this->assertSame(
            1,
            SmartPlantRegister::query()->where('plant_id', $this->plant->id)->count(),
            'La lectura no acabó en la planta de la URL.'
        );
    }

    /**
     * `smartplant_registers` no tiene columna `user_id` y el FormRequest la
     * validaba igual con `exists:users,id` (**N288**). Ahora esa regla no está:
     * de quién es la lectura se sabe por su planta, y la propiedad de la planta
     * la comprueba `OwnedSmartPlant`.
     */
    #[Test]
    public function the_reading_does_not_validate_a_user_id_that_is_not_a_column(): void
    {
        $this->assertFalse(
            Schema::hasColumn('smartplant_registers', 'user_id'),
            'La tabla ya tiene `user_id`: entonces hay que meterlo en el $fillable y volver a validarlo.'
        );

        // Mandar un `user_id` ajeno no debe cambiar nada: no se valida ni se guarda.
        $other = $this->createAuthenticatedUser(3);
        $payload = array_merge($this->payload(), ['user_id' => $other->id]);

        $this->postJson($this->apiUrl("smartplant/plants/{$this->plant->id}/readings"), $payload, $this->moduleHeaders($this->user, TokenAbilities::SMARTPLANT_WRITE))
            ->assertStatus(201);

        $this->assertSame(
            $this->plant->id,
            (int) SmartPlantRegister::query()->latest('id')->first()?->plant_id,
            'La lectura no quedó ligada a la planta del usuario autenticado.'
        );
    }
}
