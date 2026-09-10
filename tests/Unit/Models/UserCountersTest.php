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

    private function createUser(bool $active, bool $deleted = false): User
    {
        $user = User::factory()->create([
            'role_id' => UserRoleEnum::User->value,
            'is_active' => $active,
        ]);

        if ($deleted) {
            $user->delete();
        }

        return $user;
    }

    #[Test]
    public function active_users_are_counted_by_is_active_and_not_by_deleted_at(): void
    {
        $this->createUser(active: true);
        $this->createUser(active: true);
        $this->createUser(active: false);

        $this->assertSame(2, User::countActive());
        $this->assertCount(2, User::getAllActive());
    }

    #[Test]
    public function the_inactive_ones_are_the_deactivated_ones_not_the_alive_ones(): void
    {
        $this->createUser(active: true);
        $this->createUser(active: false);
        $this->createUser(active: false);

        $this->assertSame(2, User::countInactive());
        $this->assertCount(2, User::getAllInactive());

        foreach (User::getAllInactive() as $user) {
            $this->assertFalse((bool) $user->is_active);
        }
    }

    #[Test]
    public function a_deleted_user_does_not_count_in_either_of_the_two(): void
    {
        // El global scope de SoftDeletes lo deja fuera de las dos consultas, que
        // es lo correcto: borrado no es ni activo ni inactivo, es que ya no está.
        $this->createUser(active: true, deleted: true);
        $this->createUser(active: false, deleted: true);

        $this->assertSame(0, User::countActive());
        $this->assertSame(0, User::countInactive());
    }
}
