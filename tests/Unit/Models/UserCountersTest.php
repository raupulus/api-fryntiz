<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Enums\UserRoleEnum;
use App\Models\User;
use Database\Seeders\RolesTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Los recuentos de usuarios dicen lo que su nombre promete (AR-CODE-01).
 *
 * Los tres métodos miraban `deleted_at`, que es borrado lógico, no
 * desactivación. Y los dos de «inactivos» hacían `self::where('deleted_at')`:
 * con un solo argumento Eloquent lo traduce a `whereNull('deleted_at')`, y como
 * el modelo usa `SoftDeletes` el global scope ya añade esa condición. O sea que
 * `getAllInactive()` devolvía **los usuarios vivos**, exactamente lo contrario
 * de lo que dice el nombre, y `countInactive()` los contaba.
 *
 * No los llamaba nadie, que es justo por lo que nadie lo había notado.
 */
class UserCountersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        (new RolesTableSeeder)->run();
    }

    private function crear(bool $activo, bool $borrado = false): User
    {
        $user = User::factory()->create([
            'role_id' => UserRoleEnum::User->value,
            'is_active' => $activo,
        ]);

        if ($borrado) {
            $user->delete();
        }

        return $user;
    }

    #[Test]
    public function los_activos_se_cuentan_por_is_active_y_no_por_deleted_at(): void
    {
        $this->crear(activo: true);
        $this->crear(activo: true);
        $this->crear(activo: false);

        $this->assertSame(2, User::countActive());
        $this->assertCount(2, User::getAllActive());
    }

    #[Test]
    public function los_inactivos_son_los_desactivados_no_los_vivos(): void
    {
        $this->crear(activo: true);
        $this->crear(activo: false);
        $this->crear(activo: false);

        $this->assertSame(2, User::countInactive());
        $this->assertCount(2, User::getAllInactive());

        foreach (User::getAllInactive() as $user) {
            $this->assertFalse((bool) $user->is_active);
        }
    }

    #[Test]
    public function un_usuario_borrado_no_cuenta_en_ninguno_de_los_dos(): void
    {
        // El global scope de SoftDeletes lo deja fuera de las dos consultas, que
        // es lo correcto: borrado no es ni activo ni inactivo, es que ya no está.
        $this->crear(activo: true, borrado: true);
        $this->crear(activo: false, borrado: true);

        $this->assertSame(0, User::countActive());
        $this->assertSame(0, User::countInactive());
    }
}
