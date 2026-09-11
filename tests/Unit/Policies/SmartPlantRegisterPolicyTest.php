<?php

declare(strict_types=1);

namespace Tests\Unit\Policies;

use App\Enums\UserRoleEnum;
use App\Models\SmartPlant\SmartPlantPlant;
use App\Models\SmartPlant\SmartPlantRegister;
use App\Models\User;
use App\Policies\SmartPlantRegisterPolicy;
use Database\Seeders\RolesTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Los registros son lecturas que sube el propio dispositivo IoT: nadie los
 * crea ni los edita a mano desde el panel, ni siquiera un admin. Lo único que
 * cabe es mirarlos, filtrarlos y borrarlos (por ejemplo, limpiar las lecturas
 * de una prueba con un dispositivo) — y borrar sigue siendo cosa de admin y
 * superadmin.
 *
 * ⚠️ Se prueba con Admin y User, no con SuperAdmin: `Gate::before` deja pasar
 * al SuperAdmin sin llegar a la policy (AGENTS.md §12), así que probar con él
 * no comprobaría nada.
 */
class SmartPlantRegisterPolicyTest extends TestCase
{
    use RefreshDatabase;

    private SmartPlantRegisterPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();

        (new RolesTableSeeder)->run();

        $this->policy = new SmartPlantRegisterPolicy;
    }

    private function makeUser(UserRoleEnum $role): User
    {
        return User::factory()->create(['role_id' => $role->value]);
    }

    private function register(): SmartPlantRegister
    {
        $plant = SmartPlantPlant::create([
            'name' => 'Olmo chino',
            'name_scientific' => 'Ulmus parvifolia',
            'description' => 'Un bonsái',
            'details' => 'Detalles.',
            'image' => 'smartplant/default.jpg',
            'start_at' => now()->subYear(),
        ]);

        return SmartPlantRegister::create([
            'plant_id' => $plant->id,
            'soil_humidity' => 40,
        ]);
    }

    #[Test]
    public function nobody_creates_a_register_by_hand_not_even_an_admin(): void
    {
        $this->assertFalse($this->policy->create($this->makeUser(UserRoleEnum::Admin)));
        $this->assertFalse($this->policy->create($this->makeUser(UserRoleEnum::User)));
    }

    #[Test]
    public function nobody_edits_a_register_by_hand_not_even_an_admin(): void
    {
        $register = $this->register();

        $this->assertFalse($this->policy->update($this->makeUser(UserRoleEnum::Admin), $register));
        $this->assertFalse($this->policy->update($this->makeUser(UserRoleEnum::User), $register));
    }

    #[Test]
    public function only_admin_and_superadmin_delete_a_register(): void
    {
        $register = $this->register();

        $this->assertTrue($this->policy->delete($this->makeUser(UserRoleEnum::Admin), $register));
        $this->assertFalse($this->policy->delete($this->makeUser(UserRoleEnum::User), $register));
    }

    #[Test]
    public function only_admin_and_superadmin_view_the_listing(): void
    {
        $this->assertTrue($this->policy->viewAny($this->makeUser(UserRoleEnum::Admin)));
        $this->assertFalse($this->policy->viewAny($this->makeUser(UserRoleEnum::User)));
    }
}
