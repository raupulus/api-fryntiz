<?php

declare(strict_types=1);

namespace App\Console\Commands\User;

use App\Enums\UserRoleEnum;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

/**
 * Crea el primer usuario administrador de un servidor.
 *
 * Sustituye al recorte de `tinker` que llevaba la guía de despliegue, que ya no
 * funcionaba: escribía en `user_role_id` (la columna se llama `role_id`) y
 * creaba el rol sin `slug`, que es NOT NULL UNIQUE. Un comando no se desincroniza
 * en silencio del esquema, porque valida y falla con un mensaje claro.
 *
 * Los roles los da por hechos: los crea `RolesTableSeeder`, que corre dentro de
 * `ProductionSeeder`. Si no están, el comando lo dice en vez de inventárselos.
 */
class MakeAdminCommand extends Command
{
    protected $signature = 'user:make-admin
        {--email= : Correo del administrador}
        {--name= : Nombre visible}
        {--password= : Contraseña (si no se pasa, se pide por consola sin dejar rastro en el historial)}
        {--superadmin : Crear con rol SuperAdmin en vez de Admin}';

    protected $description = 'Crea un usuario administrador (pensado para el primer arranque en un servidor)';

    public function handle(): int
    {
        $email = $this->option('email') ?: $this->ask('Correo del administrador');
        $name = $this->option('name') ?: $this->ask('Nombre visible', 'Administrador');

        // La contraseña se pide con `secret()` para que no quede en el historial
        // del shell ni en la lista de procesos.
        $password = $this->option('password') ?: $this->secret('Contraseña');

        $role = $this->option('superadmin') ? UserRoleEnum::SuperAdmin : UserRoleEnum::Admin;

        $validator = Validator::make(
            ['email' => $email, 'name' => $name, 'password' => $password],
            [
                'email' => ['required', 'email', 'unique:users,email'],
                'name' => ['required', 'string', 'max:255'],
                'password' => ['required', Password::min(12)->letters()->numbers()->symbols()],
            ]
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        if (! UserRole::query()->whereKey($role->value)->exists()) {
            $this->error("No existe el rol {$role->label()} (id {$role->value}).");
            $this->line('Ejecuta antes:  php artisan db:seed --class=ProductionSeeder --force');

            return self::FAILURE;
        }

        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'role_id' => $role->value,
            // Sin esto el usuario no puede entrar en los paneles de Filament.
            'is_active' => true,
        ]);

        // `email_verified_at` no es asignable en masa a propósito (ver User),
        // así que se marca aparte. Sin esto el administrador recién creado se
        // queda fuera del panel esperando un correo de verificación que en el
        // primer arranque de un servidor no le va a llegar.
        $user->markEmailAsVerified();

        $this->info("Usuario #{$user->id} ({$role->label()}) creado: {$user->email}");

        return self::SUCCESS;
    }
}
