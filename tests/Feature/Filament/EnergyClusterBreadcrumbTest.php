<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Filament\Admin\Pages\EnergyDashboard;
use App\Models\User;
use Database\Seeders\RolesTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * «Energy» en las migas de pan lleva a la portada del módulo.
 *
 * `/admin/energy` no es una página: su `mount()` redirige al **primer** elemento
 * de la subnavegación del clúster. Como el resumen y las instalaciones estaban
 * empatados en `navigationSort`, ganaba «Instalaciones», así que pulsar «Energy»
 * llevaba siempre allí — y estando ya en esa pantalla parecía que la página sólo
 * se recargaba.
 */
class EnergyClusterBreadcrumbTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function la_raiz_del_cluster_lleva_al_resumen(): void
    {
        (new RolesTableSeeder)->run();

        $this->actingAs(User::factory()->create(['role_id' => 1, 'is_active' => true]));

        $this->get('/admin/energy')->assertRedirect(EnergyDashboard::getUrl());
    }
}
