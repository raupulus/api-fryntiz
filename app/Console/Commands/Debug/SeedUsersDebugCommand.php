<?php

namespace App\Console\Commands\Debug;

use App\Console\Commands\Debug\Concerns\ResolvesDebugDefaults;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class SeedUsersDebugCommand extends Command
{
    use ResolvesDebugDefaults;

    protected $signature = 'debug:seed-users {--count=5 : Número de usuarios a crear}';

    protected $description = 'Crea usuarios de prueba con roles aleatorios (solo desarrollo)';

    public function handle(): int
    {
        if (! $this->guardEnvironment()) {
            return self::FAILURE;
        }

        $count = (int) $this->option('count');
        $this->info("Creando {$count} usuarios de prueba...");

        // Asegura al menos un rol disponible
        $role = UserRole::query()->firstOrCreate(
            ['id' => 3],
            ['name' => 'user', 'display_name' => 'Usuario', 'slug' => 'usuario', 'description' => 'Usuario normal']
        );

        for ($i = 0; $i < $count; $i++) {
            User::create([
                'name' => fake()->name(),
                'email' => fake()->unique()->safeEmail(),
                'password' => Hash::make('password'),
                'role_id' => $role->id,
                'email_verified_at' => now(),
            ]);
        }

        $this->info("✅ {$count} usuarios creados.");
        return self::SUCCESS;
    }
}
