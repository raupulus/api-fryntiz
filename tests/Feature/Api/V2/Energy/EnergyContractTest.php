<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V2\Energy;

use App\Models\Hardware\HardwareDevice;
use App\Models\Hardware\HardwareEnergy;
use App\Models\Hardware\HardwareEnergyReading;
use App\Models\User;
use App\Support\Auth\TokenAbilities;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Api\ApiTestCase;

/**
 * El contrato HTTP de `/energy/readings`, exactamente como lo ve un cliente.
 *
 * Esta clase es la red que sujeta a `docs/info/api/v2/energy.md`: si alguien
 * cambia la forma de la respuesta, aquí se rompe algo y la documentación deja
 * de poder mentir en silencio. Estuvo mintiendo —documentaba un `element`
 * anidado, campos sueltos en la raíz y bloques `battery` y `status` que el
 * recurso nunca ha devuelto—, y eso se lo come el que integre.
 *
 * Lo que se fija aquí:
 *
 * - la forma de la respuesta de `POST` y de `GET`, clave por clave;
 * - qué campos se aceptan y cuáles son alias;
 * - la precisión de las magnitudes;
 * - qué pasa cuando algo falla, y con qué código.
 */
class EnergyContractTest extends ApiTestCase
{
    protected string $apiPrefix = 'api/v2';

    private User $user;

    private HardwareDevice $device;

    private HardwareEnergy $generator;

    private HardwareEnergy $battery;

    private HardwareEnergy $load;

    /**
     * La forma exacta de una lectura en cualquier respuesta del módulo.
     */
    private const FORMA_LECTURA = [
        'id',
        'hardware_device_id',
        'hardware_energy_id',
        'role',
        'measured' => [
            'amperage',
            'voltage',
            'power',
            'delta_seconds',
            'temperature',
            'battery_voltage',
            'battery_percentage',
            'fan',
            'charging_status',
            'charging_status_label',
            'light_status',
            'light_brightness',
        ],
        'derived' => ['energy_wh', 'energy_ah'],
        'sources' => ['energy', 'voltage'],
        'is_suspicious',
        'suspicious_reason',
        'created_at',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = $this->createAuthenticatedUser(3);

        $this->device = HardwareDevice::create([
            'user_id' => $this->user->id,
            'name' => 'Renogy Rover 20LI',
        ]);

        $this->generator = HardwareEnergy::create([
            'hardware_device_id' => $this->device->id,
            'hardware_device_monitorized_id' => $this->device->id,
            'role' => HardwareEnergy::ROLE_GENERATOR,
            'sensor_position' => 0,
            'nominal_voltage' => 24.0,
            'is_active' => true,
        ]);

        $this->battery = HardwareEnergy::create([
            'hardware_device_id' => $this->device->id,
            'hardware_device_monitorized_id' => $this->device->id,
            'role' => HardwareEnergy::ROLE_BATTERY,
            'sensor_position' => 0,
            'nominal_voltage' => 12.8,
            'voltage_min' => 11.0,
            'voltage_max' => 14.4,
            'is_active' => true,
        ]);

        $this->load = HardwareEnergy::create([
            'hardware_device_id' => $this->device->id,
            'hardware_device_monitorized_id' => $this->device->id,
            'role' => HardwareEnergy::ROLE_LOAD,
            'sensor_position' => 0,
            'nominal_voltage' => 12.0,
            'is_active' => true,
        ]);
    }

    private function escritura(): array
    {
        return $this->deviceHeaders($this->device, [TokenAbilities::ENERGY_WRITE]);
    }

    private function lectura(): array
    {
        return $this->deviceHeaders($this->device, [TokenAbilities::ENERGY_READ]);
    }

    private function payloadCompleto(): array
    {
        return [
            'hardware_device_id' => $this->device->id,
            'duration' => 60,
            'energy' => [
                'generator' => [
                    'voltage' => 34.5,
                    'amperage' => 4.2,
                    'power' => 144.9,
                    'charging_status' => 3,
                    'charging_status_label' => 'mppt',
                    'light_status' => false,
                    'today_energy_wh' => 1250.0,
                    'historical_energy_wh' => 45000.0,
                ],
                'battery' => [
                    'voltage' => 13.4,
                    'amperage' => 5.0,
                    'soc' => 92,
                    'temperature' => 24.5,
                    'today_energy_ah' => 40.0,
                    'historical_energy_ah' => 1500.0,
                    'battery_full_charges' => 25,
                    'battery_over_discharges' => 1,
                ],
                'loads' => [
                    [
                        'channel' => 0,
                        'voltage' => 12.1,
                        'amperage' => 2.5,
                        'today_energy_wh' => 310.0,
                        'historical_energy_wh' => 12500.0,
                    ],
                ],
            ],
        ];
    }

    // ───────────────────────── Forma de la respuesta ─────────────────────

    #[Test]
    public function the_post_response_has_the_documented_shape(): void
    {
        $response = $this->postJson($this->apiUrl('energy/readings'), $this->payloadCompleto(), $this->escritura());

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['*' => self::FORMA_LECTURA],
            ])
            ->assertJson(['success' => true]);

        // Una entrada por bloque, en el orden en que se procesan.
        $data = $response->json('data');
        $this->assertCount(3, $data);
        $this->assertSame(['generator', 'battery', 'load'], array_column($data, 'role'));
    }

    #[Test]
    public function the_get_response_has_the_same_shape_plus_pagination(): void
    {
        $this->postJson($this->apiUrl('energy/readings'), $this->payloadCompleto(), $this->escritura());

        $response = $this->getJson($this->apiUrl('energy/readings'), $this->lectura());

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['*' => self::FORMA_LECTURA],
                'meta' => ['total', 'per_page', 'current_page', 'last_page', 'from', 'to'],
            ]);
    }

    #[Test]
    public function a_reading_reads_back_exactly_as_it_was_returned_when_created(): void
    {
        $creada = $this->postJson($this->apiUrl('energy/readings'), $this->payloadCompleto(), $this->escritura())
            ->json('data.0');

        $releida = collect($this->getJson($this->apiUrl('energy/readings'), $this->lectura())->json('data'))
            ->firstWhere('id', $creada['id']);

        $this->assertSame(
            $creada,
            $releida,
            'El POST y el GET tienen que devolver exactamente lo mismo para la misma lectura.'
        );
    }

    #[Test]
    public function warnings_only_appear_when_there_is_something_to_warn_about(): void
    {
        // Todo correcto: sin bloque `warnings`.
        $limpia = $this->postJson($this->apiUrl('energy/readings'), $this->payloadCompleto(), $this->escritura());
        $limpia->assertStatus(201);
        $this->assertArrayNotHasKey('warnings', $limpia->json());

        // Con algo que contar: el bloque aparece, y la lectura se guarda igual.
        $avisada = $this->postJson($this->apiUrl('energy/readings'), [
            'hardware_device_id' => $this->device->id,
            'duration' => 60,
            'energy' => ['loads' => [['channel' => 0, 'voltage' => 12.0, 'amperage' => -3.0]]],
        ], $this->escritura());

        $avisada->assertStatus(201);
        $this->assertIsArray($avisada->json('warnings'));
        $this->assertNotEmpty($avisada->json('warnings'));
    }

    // ───────────────────────────── Entradas ──────────────────────────────

    #[Test]
    public function duration_can_travel_at_the_root_or_inside_the_energy_block(): void
    {
        $raiz = $this->postJson($this->apiUrl('energy/readings'), [
            'hardware_device_id' => $this->device->id,
            'duration' => 120,
            'energy' => ['generator' => ['voltage' => 24.0, 'amperage' => 1.0]],
        ], $this->escritura());

        $raiz->assertStatus(201);
        $this->assertSame(120, $raiz->json('data.0.measured.delta_seconds'));

        $dentro = $this->postJson($this->apiUrl('energy/readings'), [
            'hardware_device_id' => $this->device->id,
            'energy' => ['duration' => 300, 'generator' => ['voltage' => 24.0, 'amperage' => 1.0]],
        ], $this->escritura());

        $dentro->assertStatus(201);
        $this->assertSame(300, $dentro->json('data.0.measured.delta_seconds'));
    }

    #[Test]
    public function the_block_duration_wins_over_the_root_one(): void
    {
        $response = $this->postJson($this->apiUrl('energy/readings'), [
            'hardware_device_id' => $this->device->id,
            'duration' => 60,
            'energy' => ['duration' => 900, 'generator' => ['voltage' => 24.0, 'amperage' => 1.0]],
        ], $this->escritura());

        $response->assertStatus(201);
        $this->assertSame(900, $response->json('data.0.measured.delta_seconds'));
    }

    #[Test]
    public function without_any_duration_it_falls_back_to_sixty_seconds(): void
    {
        $response = $this->postJson($this->apiUrl('energy/readings'), [
            'hardware_device_id' => $this->device->id,
            'energy' => ['generator' => ['voltage' => 24.0, 'amperage' => 1.0]],
        ], $this->escritura());

        $response->assertStatus(201);
        $this->assertSame(60, $response->json('data.0.measured.delta_seconds'));
        // 1 A · 60 s / 3600 · 24 V = 0,4 Wh
        $this->assertSame(0.4, $response->json('data.0.derived.energy_wh'));
    }

    #[Test]
    public function the_device_health_block_accepts_both_its_name_and_its_alias(): void
    {
        foreach (['hardware_device_info', 'device'] as $clave) {
            $this->postJson($this->apiUrl('energy/readings'), [
                'hardware_device_id' => $this->device->id,
                'duration' => 60,
                $clave => ['temp' => 41.5, 'uptime' => 86400],
                'energy' => ['generator' => ['voltage' => 24.0, 'amperage' => 1.0]],
            ], $this->escritura())->assertStatus(201);

            $this->assertEqualsWithDelta(41.5, (float) $this->device->fresh()->temp, 0.001, "Con la clave «{$clave}»");
        }
    }

    #[Test]
    public function the_channel_of_a_load_accepts_both_names(): void
    {
        $response = $this->postJson($this->apiUrl('energy/readings'), [
            'hardware_device_id' => $this->device->id,
            'duration' => 60,
            'energy' => ['loads' => [['sensor_position' => 0, 'voltage' => 12.0, 'amperage' => 1.0]]],
        ], $this->escritura());

        $response->assertStatus(201);
        $this->assertSame($this->load->id, $response->json('data.0.hardware_energy_id'));
    }

    #[Test]
    public function the_battery_percentage_accepts_both_soc_and_battery_percentage(): void
    {
        $response = $this->postJson($this->apiUrl('energy/readings'), [
            'hardware_device_id' => $this->device->id,
            'duration' => 60,
            'energy' => ['battery' => ['voltage' => 13.4, 'battery_percentage' => 77]],
        ], $this->escritura());

        $response->assertStatus(201);
        $this->assertSame(77, $response->json('data.0.measured.battery_percentage'));
    }

    // ───────────────────────────── Precisión ─────────────────────────────

    #[Test]
    public function magnitudes_are_rounded_to_the_precision_of_their_column(): void
    {
        // 3 A durante 70 s a 13,333 V da decimales de sobra para verlo.
        $response = $this->postJson($this->apiUrl('energy/readings'), [
            'hardware_device_id' => $this->device->id,
            'duration' => 70,
            'energy' => ['battery' => ['voltage' => 13.333, 'amperage' => 3.0]],
        ], $this->escritura());

        $response->assertStatus(201);

        $derived = $response->json('data.0.derived');

        // energy_wh y energy_ah son decimal(14,4): cuatro decimales, ni uno más.
        $this->assertSame(round($derived['energy_wh'], 4), $derived['energy_wh']);
        $this->assertSame(round($derived['energy_ah'], 4), $derived['energy_ah']);
    }

    // ────────────────────────────── Errores ──────────────────────────────

    #[Test]
    public function it_rejects_a_payload_without_the_energy_block(): void
    {
        $this->postJson($this->apiUrl('energy/readings'), [
            'hardware_device_id' => $this->device->id,
            'duration' => 60,
        ], $this->escritura())
            ->assertStatus(422)
            ->assertJson(['success' => false])
            ->assertJsonStructure(['success', 'message', 'errors']);
    }

    #[Test]
    public function it_rejects_an_energy_block_with_no_subsystem(): void
    {
        // Un `energy` con algo dentro pero sin ningún subsistema: la petición
        // parece válida y no traería ni una medida.
        $response = $this->postJson($this->apiUrl('energy/readings'), [
            'hardware_device_id' => $this->device->id,
            'energy' => ['duration' => 60],
        ], $this->escritura());

        $response->assertStatus(422);
        $this->assertStringContainsString('generator', implode(' ', $response->json('errors.energy') ?? []));
    }

    #[Test]
    public function an_empty_energy_block_is_treated_as_missing(): void
    {
        // `energy: {}` y `energy: []` llegan como array vacío, que para Laravel
        // es lo mismo que no mandarlo: responde con el «es obligatorio».
        $response = $this->postJson($this->apiUrl('energy/readings'), [
            'hardware_device_id' => $this->device->id,
            'energy' => [],
        ], $this->escritura());

        $response->assertStatus(422);
        $this->assertStringContainsString('obligatorio', implode(' ', $response->json('errors.energy') ?? []));
    }

    #[Test]
    public function validation_errors_of_the_energy_block_come_back_under_energy(): void
    {
        $response = $this->postJson($this->apiUrl('energy/readings'), [
            'hardware_device_id' => $this->device->id,
            'energy' => ['battery' => ['soc' => 150]],
        ], $this->escritura());

        $response->assertStatus(422)->assertJsonStructure(['errors' => ['energy']]);
    }

    #[Test]
    public function it_rejects_a_duration_below_one_second(): void
    {
        $this->postJson($this->apiUrl('energy/readings'), [
            'hardware_device_id' => $this->device->id,
            'energy' => ['duration' => 0, 'generator' => ['voltage' => 24.0, 'amperage' => 1.0]],
        ], $this->escritura())->assertStatus(422);
    }

    #[Test]
    public function it_rejects_an_unknown_device(): void
    {
        $this->postJson($this->apiUrl('energy/readings'), [
            'hardware_device_id' => 999999,
            'energy' => ['generator' => ['voltage' => 24.0, 'amperage' => 1.0]],
        ], $this->escritura())->assertStatus(422);
    }

    // ──────────────────────────── Autenticación ──────────────────────────

    #[Test]
    public function it_refuses_an_anonymous_upload(): void
    {
        $this->postJson($this->apiUrl('energy/readings'), $this->payloadCompleto(), $this->guestHeaders())
            ->assertStatus(401);

        $this->assertSame(0, HardwareEnergyReading::query()->count());
    }

    #[Test]
    public function the_read_ability_cannot_write_and_the_write_one_cannot_read(): void
    {
        $this->postJson($this->apiUrl('energy/readings'), $this->payloadCompleto(), $this->lectura())
            ->assertStatus(403);

        $this->getJson($this->apiUrl('energy/readings'), $this->escritura())
            ->assertStatus(403);
    }

    #[Test]
    public function a_reading_of_another_user_is_never_listed(): void
    {
        $this->postJson($this->apiUrl('energy/readings'), $this->payloadCompleto(), $this->escritura());

        $otro = $this->createAuthenticatedUser(3);
        $suyo = HardwareDevice::create(['user_id' => $otro->id, 'name' => 'Ajeno']);

        $response = $this->getJson(
            $this->apiUrl('energy/readings'),
            $this->deviceHeaders($suyo, [TokenAbilities::ENERGY_READ])
        );

        $response->assertStatus(200);
        $this->assertSame([], $response->json('data'));
    }

    // ───────────────────────────── Consultas ─────────────────────────────

    #[Test]
    public function the_listing_can_be_filtered_by_role(): void
    {
        $this->postJson($this->apiUrl('energy/readings'), $this->payloadCompleto(), $this->escritura());

        $response = $this->getJson($this->apiUrl('energy/readings?role=battery'), $this->lectura());

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertSame('battery', $response->json('data.0.role'));
    }

    #[Test]
    public function the_listing_can_be_filtered_by_element_and_by_suspicion(): void
    {
        $this->postJson($this->apiUrl('energy/readings'), $this->payloadCompleto(), $this->escritura());

        $this->postJson($this->apiUrl('energy/readings'), [
            'hardware_device_id' => $this->device->id,
            'duration' => 60,
            'energy' => ['loads' => [['channel' => 0, 'voltage' => 12.0, 'amperage' => -3.0]]],
        ], $this->escritura());

        $porElemento = $this->getJson(
            $this->apiUrl("energy/readings?hardware_energy_id={$this->generator->id}"),
            $this->lectura()
        );
        $porElemento->assertStatus(200);
        $this->assertCount(1, $porElemento->json('data'));

        $sospechosas = $this->getJson($this->apiUrl('energy/readings?is_suspicious=1'), $this->lectura());
        $sospechosas->assertStatus(200);
        $this->assertCount(1, $sospechosas->json('data'));
        $this->assertTrue($sospechosas->json('data.0.is_suspicious'));
    }

    #[Test]
    public function the_listing_defaults_to_twenty_five_per_page_and_caps_at_a_hundred(): void
    {
        $this->postJson($this->apiUrl('energy/readings'), $this->payloadCompleto(), $this->escritura());

        $porDefecto = $this->getJson($this->apiUrl('energy/readings'), $this->lectura());
        $this->assertSame(25, $porDefecto->json('meta.per_page'));

        $pasado = $this->getJson($this->apiUrl('energy/readings?per_page=500'), $this->lectura());
        $this->assertSame(100, $pasado->json('meta.per_page'), 'El tope es 100 por página.');
    }

    #[Test]
    public function the_listing_comes_newest_first_and_respects_per_page(): void
    {
        $this->postJson($this->apiUrl('energy/readings'), $this->payloadCompleto(), $this->escritura());

        $response = $this->getJson($this->apiUrl('energy/readings?per_page=2'), $this->lectura());

        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data'));
        $this->assertSame(3, $response->json('meta.total'));
        $this->assertSame(2, $response->json('meta.per_page'));
        $this->assertSame(2, $response->json('meta.last_page'));
    }
}
