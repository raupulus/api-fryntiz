<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Enums\UserRoleEnum;
use App\Filament\Admin\Pages\Login as AdminLogin;
use App\Filament\Tenant\Pages\Login as TenantLogin;
use App\Models\User;
use App\Services\CaptchaResult;
use App\Services\RecaptchaService;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * reCAPTCHA v3 en los logins de Filament (Admin y Tenant), mismo patrón que el
 * formulario de contacto: sin `services.recaptcha.secret_key` configurada no
 * se aplica ninguna comprobación; configurada, un token inválido corta el
 * login con el mismo error genérico de credenciales.
 */
class RecaptchaLoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $roles = [
            ['id' => 1, 'name' => 'superadmin', 'display_name' => 'Super Admin', 'slug' => 'super-admin', 'description' => 'Administrador Principal'],
            ['id' => 2, 'name' => 'admin', 'display_name' => 'Admin', 'slug' => 'admin', 'description' => 'Administradores'],
            ['id' => 3, 'name' => 'user', 'display_name' => 'Usuario', 'slug' => 'usuario', 'description' => 'Usuario normal'],
            ['id' => 4, 'name' => 'editor', 'display_name' => 'Editor', 'slug' => 'editor', 'description' => 'Edita contenido sólo en las plataformas que tenga asignadas'],
        ];

        foreach ($roles as $role) {
            DB::table('user_roles')->insert($role + ['created_at' => now(), 'updated_at' => now()]);
        }
    }

    private function admin(): User
    {
        $user = User::factory()->create();
        $user->forceFill(['role_id' => UserRoleEnum::SuperAdmin->value, 'is_active' => true])->save();

        return $user->fresh();
    }

    private function tenant(): User
    {
        $user = User::factory()->create();
        $user->forceFill(['role_id' => UserRoleEnum::User->value, 'is_active' => true])->save();

        return $user->fresh();
    }

    #[Test]
    public function admin_login_is_rejected_when_recaptcha_is_configured_and_invalid(): void
    {
        $this->mock(RecaptchaService::class, function ($mock) {
            $mock->shouldReceive('verify')
                ->andReturn(new CaptchaResult(valid: false, score: null, configured: true));
        });

        $user = $this->admin();

        Filament::setCurrentPanel(Filament::getPanel('admin'));

        Livewire::test(AdminLogin::class)
            ->fillForm(['email' => $user->email, 'password' => 'password'])
            ->set('recaptchaToken', 'invalid-token')
            ->call('authenticate')
            ->assertHasErrors(['data.email']);

        $this->assertGuest('web');
    }

    #[Test]
    public function admin_login_succeeds_when_recaptcha_is_not_configured(): void
    {
        $this->mock(RecaptchaService::class, function ($mock) {
            $mock->shouldReceive('verify')
                ->andReturn(new CaptchaResult(valid: true, score: null, configured: false));
        });

        $user = $this->admin();

        Filament::setCurrentPanel(Filament::getPanel('admin'));

        Livewire::test(AdminLogin::class)
            ->fillForm(['email' => $user->email, 'password' => 'password'])
            ->call('authenticate')
            ->assertHasNoErrors();

        $this->assertAuthenticatedAs($user);
    }

    #[Test]
    public function tenant_login_is_rejected_when_recaptcha_is_configured_and_invalid(): void
    {
        $this->mock(RecaptchaService::class, function ($mock) {
            $mock->shouldReceive('verify')
                ->andReturn(new CaptchaResult(valid: false, score: null, configured: true));
        });

        $user = $this->tenant();

        Filament::setCurrentPanel(Filament::getPanel('tenant'));

        Livewire::test(TenantLogin::class)
            ->fillForm(['email' => $user->email, 'password' => 'password'])
            ->set('recaptchaToken', 'invalid-token')
            ->call('authenticate')
            ->assertHasErrors(['data.email']);

        $this->assertGuest('web');
    }

    #[Test]
    public function tenant_login_succeeds_when_recaptcha_is_valid(): void
    {
        $this->mock(RecaptchaService::class, function ($mock) {
            $mock->shouldReceive('verify')
                ->andReturn(new CaptchaResult(valid: true, score: 0.9, configured: true));
        });

        $user = $this->tenant();

        Filament::setCurrentPanel(Filament::getPanel('tenant'));

        Livewire::test(TenantLogin::class)
            ->fillForm(['email' => $user->email, 'password' => 'password'])
            ->set('recaptchaToken', 'valid-token')
            ->call('authenticate')
            ->assertHasNoErrors();

        $this->assertAuthenticatedAs($user);
    }
}
