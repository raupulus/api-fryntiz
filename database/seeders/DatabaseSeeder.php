<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Class DatabaseSeeder
 */
class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // \App\Models\User::factory(10)->create();
        $this->call(LanguagesTableSeeder::class);
        $this->call(RolesTableSeeder::class);
        $this->call(UsersTableSeeder::class);
        $this->call(SocialNetworkSeeder::class);
        $this->call(HardwareTypesSeeder::class);
        $this->call(HardwareAvailableComponentsTableSeeder::class);
        $this->call(CurriculumAvailableRepositoryTypeSeeder::class);
        $this->call(ContentAvailableTypesSeeder::class);
        $this->call(ContentAvailableStatusSeeder::class);
        $this->call(ContentAvailablePageRawSeeder::class);
        $this->call(CategoriesSeeder::class);
        $this->call(TagsSeeder::class);
        $this->call(TechnologiesSeeder::class);
        $this->call(PrinterAvailableTypesSeeder::class);
        $this->call(ReferredPlatformsSeeder::class);
        $this->call(EnergySystemsSeeder::class);
    }
}
