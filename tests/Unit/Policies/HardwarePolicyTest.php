<?php

declare(strict_types=1);

namespace Tests\Unit\Policies;

use App\Enums\UserRoleEnum;
use App\Models\Hardware\HardwareDevice;
use App\Models\Hardware\HardwareType;
use App\Models\User;
use App\Policies\HardwarePolicy;
use Database\Seeders\RolesTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * ⚠️ Roles 2 (Admin) y 3 (User): `Gate::before` deja pasar al SuperAdmin sin
 * llegar a la policy, así que probar con él no comprobaría nada (AGENTS.md §12).
 *
 * Además se prueba la clase directamente, sin Gate, para que ese atajo no
 * pueda enmascarar el resultado.
 */
class HardwarePolicyTest extends TestCase
{
    use RefreshDatabase;

    private HardwarePolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();

        (new RolesTableSeeder)->run();

        $this->policy = new HardwarePolicy;
    }

    private function makeUser(UserRoleEnum $role = UserRoleEnum::User): User
    {
        return User::factory()->create(['role_id' => $role->value]);
    }

    private function makeDevice(?User $owner): HardwareDevice
    {
        $type = HardwareType::firstOrCreate(['name' => HardwareType::WEATHER_STATION]);

        return HardwareDevice::create([
            'hardware_type_id' => $type->id,
            'user_id' => $owner?->id,
            'name' => 'Cacharro '.uniqid(),
        ]);
    }

    #[Test]
    public function the_owner_views_and_edits_their_device(): void
    {
        $user = $this->makeUser();
        $device = $this->makeDevice($user);

        $this->assertTrue($this->policy->view($user, $device));
        $this->assertTrue($this->policy->update($user, $device));
    }

    #[Test]
    public function a_regular_user_does_not_see_another_users_device(): void
    {
        $othersDevice = $this->makeDevice($this->makeUser());

        $this->assertFalse($this->policy->view($this->makeUser(), $othersDevice));
    }

    #[Test]
    public function the_admin_does_see_and_edit_another_users_device(): void
    {
        // Este test afirmaba lo contrario —«los cacharros son de su dueño y
        // punto»— y con ello dejaba fuera de su propio panel a un rol que
        // `AGENTS.md` describe como capaz de «gestionar dispositivos hardware».
        //
        // La jerarquía del proyecto es: SuperAdmin llega a todo, Admin a todo
        // menos a lo de un SuperAdmin, y el resto sólo a lo suyo. `Gate::before`
        // sólo implementa el primer escalón, así que si la policy no contempla
        // al Admin, éste ve el listado y se lleva un 403 al abrir cualquier
        // ficha ajena (AR-SEC-03).
        $othersDevice = $this->makeDevice($this->makeUser());
        $admin = $this->makeUser(UserRoleEnum::Admin);

        $this->assertTrue($this->policy->view($admin, $othersDevice));
        $this->assertTrue($this->policy->update($admin, $othersDevice));
        $this->assertTrue($this->policy->delete($admin, $othersDevice));
    }

    #[Test]
    public function the_owner_deletes_their_device(): void
    {
        $user = $this->makeUser();

        $this->assertTrue($this->policy->delete($user, $this->makeDevice($user)));
    }

    #[Test]
    public function a_regular_user_does_not_delete_another_users_device(): void
    {
        $othersDevice = $this->makeDevice($this->makeUser());

        $this->assertFalse($this->policy->delete($this->makeUser(), $othersDevice));
    }

    #[Test]
    public function the_owner_writes_readings_to_their_device(): void
    {
        $user = $this->makeUser();

        $this->assertTrue($this->policy->writeData($user, $this->makeDevice($user)));
    }

    #[Test]
    public function no_readings_are_written_to_another_users_device(): void
    {
        $othersDevice = $this->makeDevice($this->makeUser());

        $this->assertFalse($this->policy->writeData($this->makeUser(), $othersDevice));
    }

    #[Test]
    public function a_device_without_an_owner_belongs_to_no_one(): void
    {
        $orphanDevice = $this->makeDevice(null);

        $this->assertFalse($this->policy->view($this->makeUser(), $orphanDevice));
    }
}
