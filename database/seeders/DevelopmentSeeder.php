<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Platform;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DevelopmentSeeder extends Seeder
{
    public function run(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            User::firstOrCreate(
                ['email' => "user{$i}@test.dev"],
                [
                    'name' => "Usuario Test {$i}",
                    'nickname' => "testuser{$i}",
                    'role_id' => 3,
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                ]
            );
        }

        $platforms = ['Blog Personal', 'Portfolio', 'Documentacion'];
        foreach ($platforms as $name) {
            Platform::firstOrCreate(
                ['slug' => Str::slug($name)],
                [
                    'user_id' => 1,
                    'name' => $name,
                    'slug' => Str::slug($name),
                    'domain' => Str::slug($name).'.test',
                ]
            );
        }
    }
}
