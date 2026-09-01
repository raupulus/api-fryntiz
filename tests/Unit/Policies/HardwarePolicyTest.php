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
    public function el_dueno_ve_y_edita_su_dispositivo(): void
    {
        $user = $this->makeUser();
        $device = $this->makeDevice($user);

        $this->assertTrue($this->policy->view($user, $device));
        $this->assertTrue($this->policy->update($user, $device));
    }

    #[Test]
    public function nadie_ve_el_dispositivo_de_otro(): void
    {
        $ajeno = $this->makeDevice($this->makeUser());

        $this->assertFalse($this->policy->view($this->makeUser(), $ajeno));
    }

    #[Test]
    public function ni_siquiera_el_admin_ve_el_dispositivo_de_otro(): void
    {
        // Los cacharros son de su dueño y punto: aquí no hay excepción de
        // administración como en los contenidos.
        $ajeno = $this->makeDevice($this->makeUser());

        $this->assertFalse($this->policy->view($this->makeUser(UserRoleEnum::Admin), $ajeno));
    }

    #[Test]
    public function el_dueno_borra_su_dispositivo(): void
    {
        $user = $this->makeUser();

        $this->assertTrue($this->policy->delete($user, $this->makeDevice($user)));
    }

    #[Test]
    public function nadie_borra_el_dispositivo_de_otro(): void
    {
        $ajeno = $this->makeDevice($this->makeUser());

        $this->assertFalse($this->policy->delete($this->makeUser(), $ajeno));
    }

    #[Test]
    public function el_dueno_escribe_lecturas_en_su_dispositivo(): void
    {
        $user = $this->makeUser();

        $this->assertTrue($this->policy->writeData($user, $this->makeDevice($user)));
    }

    #[Test]
    public function no_se_escriben_lecturas_en_el_dispositivo_de_otro(): void
    {
        $ajeno = $this->makeDevice($this->makeUser());

        $this->assertFalse($this->policy->writeData($this->makeUser(), $ajeno));
    }

    #[Test]
    public function un_dispositivo_sin_dueno_no_es_de_nadie(): void
    {
        $huerfano = $this->makeDevice(null);

        $this->assertFalse($this->policy->view($this->makeUser(), $huerfano));
    }
}
