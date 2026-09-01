<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Catálogos mínimos para que la aplicación funcione en producción.
 *
 * Es `DatabaseSeeder` MENOS `UsersTableSeeder`. Ese es el motivo de que exista
 * este fichero: `UsersTableSeeder` crea `superadmin@domain.es` con la contraseña
 * `123123` y le emite un token de API. En desarrollo es cómodo; en un servidor
 * público es una cuenta de administrador con contraseña conocida. Con
 * `db:seed --class=ProductionSeeder` no hay forma de invocarlo por accidente.
 *
 * Los catálogos que llama son idempotentes: cada uno comprueba si la fila ya
 * existe (por `id`, `slug` o `iso2`) antes de insertar, y ninguno hace
 * `truncate` ni `delete`. Se puede ejecutar sobre la base de datos poblada del
 * VPS tantas veces como haga falta sin tocar un solo dato existente: lo único
 * que hace es rellenar los catálogos nuevos que trae la v2.
 *
 * Los usuarios de producción se crean a mano — ver `docs/deploys/deploy-vps.md`.
 */
class ProductionSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(LanguagesTableSeeder::class);
        $this->call(RolesTableSeeder::class);
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
