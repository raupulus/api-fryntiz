<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Enums\UserRoleEnum;
use App\Filament\Admin\Clusters\AirFlight;
use App\Filament\Admin\Clusters\Energy;
use App\Filament\Admin\Clusters\KeyCounter;
use App\Filament\Admin\Clusters\SmartPlant;
use App\Filament\Admin\Pages\EnergyDashboard;
use App\Filament\Admin\Resources\ApiTokens\ApiTokenResource;
use App\Filament\Admin\Resources\CV\Curriculum\CurriculumResource;
use App\Filament\Admin\Resources\CV\CurriculumAvailableRepositoryTypes\CurriculumAvailableRepositoryTypeResource;
use App\Filament\Admin\Resources\FileTypes\FileTypeResource;
use App\Filament\Admin\Resources\Hardware\HardwareAvailableComponents\HardwareAvailableComponentResource;
use App\Filament\Admin\Resources\Hardware\HardwareDevices\HardwareDeviceResource;
use App\Filament\Admin\Resources\Hardware\HardwareTypes\HardwareTypeResource;
use App\Filament\Admin\Resources\KeyCounter\Keyboards\KeyboardResource;
use App\Filament\Admin\Resources\KeyCounter\Mice\MouseResource;
use App\Filament\Admin\Resources\Printers\PrinterResource;
use App\Filament\Admin\Widgets\DashboardStats;
use App\Filament\Admin\Widgets\DeviceStatusWidget;
use App\Filament\Admin\Widgets\EnergyHistoricalChart;
use App\Filament\Admin\Widgets\EnergyStatsWidget;
use App\Models\ApiToken;
use App\Models\CV\Curriculum;
use App\Models\Hardware\HardwareDevice;
use App\Models\Hardware\HardwareType;
use App\Models\KeyCounter\Keyboard;
use App\Models\KeyCounter\Mouse;
use App\Models\Printer;
use App\Models\PrinterAvailableType;
use App\Models\SmartPlant\SmartPlantPlant;
use App\Models\User;
use App\Support\Auth\TokenAbilities;
use Database\Seeders\RolesTableSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * El panel no se abre solo (AR-SEC-01, AR-SEC-02, AR-SEC-03, AR-SEC-04).
 *
 * `/admin` no es sólo para administradores: el rol `Editor` entra a gestionar
 * las publicaciones de sus plataformas ({@see User::canAccessPanel()}). Todo lo
 * que este test comprueba parte de ahí.
 *
 * El fallo de fondo que motiva el fichero es una trampa de Filament que conviene
 * tener escrita: **un modelo sin policy no queda cerrado, queda abierto**. Si
 * `Gate::getPolicyFor()` devuelve `null`, el recurso autoriza `viewAny`,
 * `create`, `edit` y `delete` a cualquiera que llegue al panel. Diez modelos
 * estaban así, y el peor era `ApiToken`: un `Editor` podía listar los tokens de
 * todos, emitirse uno a nombre de un `SuperAdmin` y revocar en lote los del
 * resto de administradores.
 *
 * Por eso la primera prueba no mira un recurso concreto sino **todos**: un
 * recurso nuevo sin policy tiene que romper la suite el día que se escriba, no
 * el día que alguien lo encuentre.
 */
class PanelAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        (new RolesTableSeeder)->run();
    }

    private function actuarComo(UserRoleEnum $role): User
    {
        $user = User::factory()->create([
            'role_id' => $role->value,
            'is_active' => true,
        ]);

        $this->actingAs($user);
        Filament::setServingStatus(true);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        return $user;
    }

    // Los registros se crean con `create()` y no con `Modelo::factory()`: los
    // modelos viven en subcarpetas (`App\Models\Hardware\HardwareDevice`) y las
    // factories están planas en `Database\Factories`, así que la convención de
    // Laravel busca una clase que no existe. Es el mismo patrón que usa
    // `tests/Unit/Policies/HardwarePolicyTest.php`.

    private function crearCurriculum(User $owner): Curriculum
    {
        return Curriculum::create([
            'user_id' => $owner->id,
            'title' => 'CV '.uniqid(),
            'slug' => 'cv-'.uniqid(),
        ]);
    }

    private function crearTeclado(User $owner): Keyboard
    {
        return Keyboard::create([
            'user_id' => $owner->id,
            'start_at' => now()->subHour(),
            'end_at' => now(),
            'duration' => 3600,
            'pulsations' => 100,
            'pulsations_special_keys' => 10,
            'pulsation_average' => 1.5,
            'score' => 100,
            'weekday' => 1,
        ]);
    }

    private function crearRaton(User $owner): Mouse
    {
        return Mouse::create([
            'user_id' => $owner->id,
            'start_at' => now()->subHour(),
            'end_at' => now(),
            'duration' => 3600,
            'clicks_left' => 50,
            'clicks_right' => 10,
            'clicks_middle' => 2,
            'total_clicks' => 62,
            'clicks_average' => 1.2,
            'weekday' => 1,
        ]);
    }

    private function crearDispositivo(User $owner): HardwareDevice
    {
        return HardwareDevice::create([
            'hardware_type_id' => HardwareType::firstOrCreate(['name' => HardwareType::WEATHER_STATION])->id,
            'user_id' => $owner->id,
            'name' => 'Cacharro '.uniqid(),
        ]);
    }

    private function crearImpresora(User $owner): Printer
    {
        return Printer::create([
            'user_id' => $owner->id,
            'printer_type_id' => PrinterAvailableType::firstOrCreate(
                ['name' => '3D'],
                ['slug' => '3d']
            )->id,
            'name' => 'Impresora '.uniqid(),
        ]);
    }

    private function crearPlanta(User $owner): SmartPlantPlant
    {
        return SmartPlantPlant::create([
            'user_id' => $owner->id,
            'name' => 'Planta '.uniqid(),
            'name_scientific' => 'Planta scientifica',
            'description' => 'Planta de prueba.',
            'details' => 'Sin detalles.',
            'start_at' => now()->subMonth(),
        ]);
    }

    #[Test]
    public function ningun_recurso_del_panel_administra_un_modelo_sin_policy(): void
    {
        $sinPolicy = [];

        foreach (Filament::getPanels() as $panel) {
            foreach ($panel->getResources() as $recurso) {
                $modelo = $recurso::getModel();

                if (Gate::getPolicyFor($modelo) === null) {
                    $sinPolicy[] = $recurso;
                }
            }
        }

        $this->assertSame([], $sinPolicy, implode("\n", [
            'Hay recursos cuyo modelo no tiene policy registrada en AuthServiceProvider.',
            'Eso NO los deja cerrados: los deja abiertos a cualquiera que entre al panel,',
            'rol Editor incluido. Registra una policy para: '.implode(', ', $sinPolicy),
        ]));
    }

    #[Test]
    public function un_editor_no_alcanza_los_tokens_de_la_api(): void
    {
        $this->actuarComo(UserRoleEnum::Editor);

        $this->assertFalse(ApiTokenResource::canViewAny(), 'Un Editor podía listar los tokens de todos los usuarios.');
        $this->assertFalse(ApiTokenResource::canCreate(), 'Un Editor podía emitirse un token a nombre de quien quisiera.');
    }

    #[Test]
    public function un_admin_gestiona_tokens_pero_no_los_de_un_superadmin(): void
    {
        $admin = $this->actuarComo(UserRoleEnum::Admin);
        $superadmin = User::factory()->create([
            'role_id' => UserRoleEnum::SuperAdmin->value,
            'is_active' => true,
        ]);
        $normal = User::factory()->create(['role_id' => UserRoleEnum::User->value]);

        // Un Admin administra: la jerarquía del proyecto es SuperAdmin → todo,
        // Admin → todo menos lo de un SuperAdmin, resto → lo suyo.
        $this->assertTrue(ApiTokenResource::canViewAny());
        $this->assertTrue(ApiTokenResource::canCreate());

        $delNormal = $normal->createToken('cacharro', [TokenAbilities::HARDWARE_WRITE])->accessToken;
        $delSuper = $superadmin->createToken('suyo', [TokenAbilities::HARDWARE_WRITE])->accessToken;

        $this->assertTrue($admin->can('delete', $delNormal));
        $this->assertFalse(
            $admin->can('delete', $delSuper),
            'Revocar el token de un SuperAdmin es escalar por la puerta de atrás.'
        );

        // Y tampoco lo ve listado: la tabla enseña lo que devuelva la consulta,
        // no lo que autorice `view()` fila a fila.
        $ids = ApiTokenResource::getEloquentQuery()->pluck('id')->all();
        $this->assertContains($delNormal->id, $ids);
        $this->assertNotContains($delSuper->id, $ids);
    }

    #[Test]
    public function un_superadmin_si_gestiona_los_tokens(): void
    {
        $this->actuarComo(UserRoleEnum::SuperAdmin);

        $this->assertTrue(ApiTokenResource::canViewAny());
        $this->assertTrue(ApiTokenResource::canCreate());
    }

    /**
     * @return array<string, array{class-string}>
     */
    public static function catalogosGlobales(): array
    {
        return [
            'tipos de fichero' => [FileTypeResource::class],
            'tipos de hardware' => [HardwareTypeResource::class],
            'componentes disponibles' => [HardwareAvailableComponentResource::class],
            'tipos de repositorio de CV' => [CurriculumAvailableRepositoryTypeResource::class],
        ];
    }

    /**
     * @param  class-string<\Filament\Resources\Resource>  $recurso
     */
    #[Test]
    #[DataProvider('catalogosGlobales')]
    public function un_editor_no_toca_los_catalogos_globales(string $recurso): void
    {
        $this->actuarComo(UserRoleEnum::Editor);

        $this->assertFalse($recurso::canViewAny());
        $this->assertFalse($recurso::canCreate());
    }

    /**
     * @param  class-string<\Filament\Resources\Resource>  $recurso
     */
    #[Test]
    #[DataProvider('catalogosGlobales')]
    public function un_admin_si_gestiona_los_catalogos_globales(string $recurso): void
    {
        $this->actuarComo(UserRoleEnum::Admin);

        $this->assertTrue($recurso::canViewAny());
        $this->assertTrue($recurso::canCreate());
    }

    #[Test]
    public function un_editor_no_ve_la_telemetria_ni_los_modulos_de_infraestructura(): void
    {
        $this->actuarComo(UserRoleEnum::Editor);

        // Widgets: nombres de servidores, CPU, disco, uptime y consumos.
        $this->assertFalse(DeviceStatusWidget::canView());
        $this->assertFalse(EnergyStatsWidget::canView());
        $this->assertFalse(EnergyHistoricalChart::canView());
        $this->assertFalse(DashboardStats::canView());

        // Páginas y clusters de la navegación.
        $this->assertFalse(EnergyDashboard::canAccess());
        $this->assertFalse(Energy::canAccess());
        $this->assertFalse(AirFlight::canAccess());
        $this->assertFalse(KeyCounter::canAccess());
        $this->assertFalse(SmartPlant::canAccess());
    }

    #[Test]
    public function un_admin_si_ve_la_telemetria(): void
    {
        $this->actuarComo(UserRoleEnum::Admin);

        $this->assertTrue(DeviceStatusWidget::canView());
        $this->assertTrue(EnergyStatsWidget::canView());
        $this->assertTrue(EnergyDashboard::canAccess());
        $this->assertTrue(Energy::canAccess());
    }

    // ───────────────────── Alcance de las tablas (AR-SEC-02) ─────────────────

    #[Test]
    public function la_tabla_de_curriculums_solo_muestra_los_propios(): void
    {
        $editor = $this->actuarComo(UserRoleEnum::Editor);
        $otro = User::factory()->create(['role_id' => UserRoleEnum::User->value]);

        $suyo = $this->crearCurriculum($editor);
        $ajeno = $this->crearCurriculum($otro);

        $ids = CurriculumResource::getEloquentQuery()->pluck('id')->all();

        $this->assertContains($suyo->id, $ids);
        $this->assertNotContains(
            $ajeno->id,
            $ids,
            'Un Editor veía en /admin el listado completo de currículums de todos los usuarios.'
        );
    }

    #[Test]
    public function la_tabla_de_teclados_solo_muestra_los_propios(): void
    {
        $editor = $this->actuarComo(UserRoleEnum::Editor);
        $otro = User::factory()->create(['role_id' => UserRoleEnum::User->value]);

        $suyo = $this->crearTeclado($editor);
        $ajeno = $this->crearTeclado($otro);

        $ids = KeyboardResource::getEloquentQuery()->pluck('id')->all();

        $this->assertContains($suyo->id, $ids);
        $this->assertNotContains(
            $ajeno->id,
            $ids,
            'Las pulsaciones y los horarios de actividad de otros usuarios eran visibles para un Editor.'
        );
    }

    #[Test]
    public function la_tabla_de_dispositivos_solo_muestra_los_propios(): void
    {
        $editor = $this->actuarComo(UserRoleEnum::Editor);
        $otro = User::factory()->create(['role_id' => UserRoleEnum::User->value]);

        $suyo = $this->crearDispositivo($editor);
        $ajeno = $this->crearDispositivo($otro);

        $ids = HardwareDeviceResource::getEloquentQuery()->pluck('id')->all();

        $this->assertContains($suyo->id, $ids);
        $this->assertNotContains($ajeno->id, $ids);
    }

    #[Test]
    public function la_tabla_de_impresoras_solo_muestra_las_propias(): void
    {
        $editor = $this->actuarComo(UserRoleEnum::Editor);
        $otro = User::factory()->create(['role_id' => UserRoleEnum::User->value]);

        $suya = $this->crearImpresora($editor);
        $ajena = $this->crearImpresora($otro);

        $ids = PrinterResource::getEloquentQuery()->pluck('id')->all();

        $this->assertContains($suya->id, $ids);
        $this->assertNotContains($ajena->id, $ids);
    }

    #[Test]
    public function un_administrador_sigue_viendo_las_tablas_enteras(): void
    {
        $admin = $this->actuarComo(UserRoleEnum::Admin);
        $otro = User::factory()->create(['role_id' => UserRoleEnum::User->value]);

        $suyo = $this->crearTeclado($admin);
        $ajeno = $this->crearTeclado($otro);

        $ids = KeyboardResource::getEloquentQuery()->pluck('id')->all();

        $this->assertContains($suyo->id, $ids);
        $this->assertContains($ajeno->id, $ids, 'El alcance por dueño no debe dejar fuera a un administrador.');
    }

    #[Test]
    public function la_tabla_de_ratones_solo_muestra_los_propios(): void
    {
        $editor = $this->actuarComo(UserRoleEnum::Editor);
        $otro = User::factory()->create(['role_id' => UserRoleEnum::User->value]);

        $suyo = $this->crearRaton($editor);
        $ajeno = $this->crearRaton($otro);

        $ids = MouseResource::getEloquentQuery()->pluck('id')->all();

        $this->assertContains($suyo->id, $ids);
        $this->assertNotContains($ajeno->id, $ids);
    }

    // ──────────── El Admin no se queda fuera de su panel (AR-SEC-03) ─────────

    #[Test]
    public function un_admin_abre_dispositivos_y_plantas_de_otros_usuarios(): void
    {
        $admin = $this->actuarComo(UserRoleEnum::Admin);
        $otro = User::factory()->create(['role_id' => UserRoleEnum::User->value]);

        $device = $this->crearDispositivo($otro);
        $plant = $this->crearPlanta($otro);

        // Antes daban 403: `isOwnedBy()` no contemplaba al administrador y el
        // atajo `Gate::before` sólo cubre a SuperAdmin.
        $this->assertTrue($admin->can('view', $device));
        $this->assertTrue($admin->can('update', $device));
        $this->assertTrue($admin->can('delete', $device));
        $this->assertTrue($admin->can('view', $plant));
        $this->assertTrue($admin->can('update', $plant));
    }

    #[Test]
    public function un_editor_no_abre_dispositivos_ni_plantas_de_otros(): void
    {
        $editor = $this->actuarComo(UserRoleEnum::Editor);
        $otro = User::factory()->create(['role_id' => UserRoleEnum::User->value]);

        $device = $this->crearDispositivo($otro);
        $plant = $this->crearPlanta($otro);

        $this->assertFalse($editor->can('view', $device));
        $this->assertFalse($editor->can('update', $device));
        $this->assertFalse($editor->can('view', $plant));
    }

    /**
     * El matiz que hace peligrosa la corrección de AR-SEC-03.
     *
     * El dueño de los cacharros es `SuperAdmin`, así que dar el bypass de
     * administrador sin mirar el token convertiría el token grabado en una placa
     * —cuyo usuario asociado es ese administrador— en una llave para el parque
     * de hardware entero. `Gate::before` ya se desactiva para peticiones de
     * dispositivo por esta misma razón; las policies tienen que hacer lo mismo.
     */
    #[Test]
    public function el_token_de_un_cacharro_no_hereda_los_permisos_de_administrador(): void
    {
        $duenyo = User::factory()->create([
            'role_id' => UserRoleEnum::SuperAdmin->value,
            'is_active' => true,
        ]);

        $propio = $this->crearDispositivo($duenyo);
        $ajeno = $this->crearDispositivo($duenyo);

        // Token ligado a UN dispositivo concreto, como el de un cacharro real.
        $token = $duenyo->createToken('estacion', [
            TokenAbilities::HARDWARE_WRITE,
            TokenAbilities::DEVICE_PREFIX.$propio->id,
        ]);

        $duenyo->withAccessToken(
            ApiToken::findToken($token->plainTextToken)
        );

        $this->assertTrue(
            $duenyo->can('writeData', $propio),
            'El cacharro debe poder escribir contra su propio dispositivo.'
        );
        $this->assertFalse(
            $duenyo->can('writeData', $ajeno),
            'El token de un cacharro no puede alcanzar los demás dispositivos, ni siendo su dueño SuperAdmin.'
        );
        $this->assertFalse(
            $duenyo->can('delete', $ajeno),
            'Borrar un dispositivo no es tarea de un dispositivo.'
        );
    }
}
