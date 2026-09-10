<?php

declare(strict_types=1);

namespace Tests\Feature\Files;

use App\Enums\UserRoleEnum;
use App\Models\File;
use App\Models\User;
use Database\Seeders\RolesTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Quién puede borrar un fichero (AR-SEC-05).
 *
 * `FileController::delete()` comprobaba únicamente que el fichero fuese del
 * usuario autenticado. Bien contra el abuso —antes de N27 no comprobaba nada y
 * cualquiera barría los ficheros de otro recorriendo ids—, pero dejaba fuera la
 * moderación: si alguien subía algo que infringe las normas, o el fichero
 * quedaba huérfano de su contenido, un administrador no tenía manera de
 * retirarlo desde la web. La única salida era artisan o tocar la base de datos.
 *
 * El criterio de la plataforma es el mismo de siempre: SuperAdmin llega a todo,
 * Admin a todo menos a lo de un SuperAdmin, y el resto sólo a lo suyo.
 */
class FileDeletionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        (new RolesTableSeeder)->run();
    }

    private function fileOwnedBy(User $owner): File
    {
        $file = new File;

        $file->forceFill([
            'user_id' => $owner->id,
            'module' => 'content',
            'path' => 'content',
            'storage_path' => 'public/content',
            'name' => 'fichero-'.uniqid().'.jpg',
            'original_name' => 'foto.jpg',
            'size' => 1024,
            'alt' => '',
            'title' => '',
            'is_private' => false,
        ])->save();

        return $file;
    }

    #[Test]
    public function the_owner_deletes_their_file(): void
    {
        $owner = User::factory()->create(['role_id' => UserRoleEnum::User->value]);
        $file = $this->fileOwnedBy($owner);

        $this->actingAs($owner)
            ->post("/file/delete/{$file->id}")
            ->assertRedirect();

        $this->assertDatabaseMissing('files', ['id' => $file->id]);
    }

    #[Test]
    public function a_regular_user_cannot_delete_someone_elses_file(): void
    {
        $file = $this->fileOwnedBy(User::factory()->create(['role_id' => UserRoleEnum::User->value]));
        $intruder = User::factory()->create(['role_id' => UserRoleEnum::User->value]);

        $this->actingAs($intruder)
            ->post("/file/delete/{$file->id}")
            ->assertForbidden();

        $this->assertDatabaseHas('files', ['id' => $file->id]);
    }

    #[Test]
    public function an_administrator_can_delete_someone_elses_file(): void
    {
        $file = $this->fileOwnedBy(User::factory()->create(['role_id' => UserRoleEnum::User->value]));
        $admin = User::factory()->create(['role_id' => UserRoleEnum::Admin->value]);

        $this->actingAs($admin)
            ->post("/file/delete/{$file->id}")
            ->assertRedirect();

        $this->assertDatabaseMissing('files', ['id' => $file->id]);
    }

    #[Test]
    public function nothing_gets_deleted_without_a_session(): void
    {
        $file = $this->fileOwnedBy(User::factory()->create(['role_id' => UserRoleEnum::User->value]));

        $this->post("/file/delete/{$file->id}")->assertRedirect('/panel/login');

        $this->assertDatabaseHas('files', ['id' => $file->id]);
    }

    #[Test]
    public function deleting_a_nonexistent_file_does_not_blow_up(): void
    {
        $user = User::factory()->create(['role_id' => UserRoleEnum::User->value]);

        $this->actingAs($user)
            ->post('/file/delete/999999')
            ->assertRedirect();
    }
}
