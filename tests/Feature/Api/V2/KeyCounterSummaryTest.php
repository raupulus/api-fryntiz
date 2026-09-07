<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V2;

use App\Models\Hardware\HardwareDevice;
use App\Models\KeyCounter\Keyboard;
use App\Models\KeyCounter\Mouse;
use App\Models\User;
use App\Support\Auth\TokenAbilities;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Api\ApiTestCase;

/**
 * `GET /keycounter/summary` — el acumulado que pide un contador al arrancar.
 *
 * Apagarse o reiniciarse le borra lo que llevaba del día. Sin esto empieza de
 * cero y enseña un total falso hasta medianoche.
 */
class KeyCounterSummaryTest extends ApiTestCase
{
    protected string $apiPrefix = 'api/v2';

    private User $user;

    private HardwareDevice $teclado;

    private HardwareDevice $otro;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = $this->createAuthenticatedUser();
        $this->teclado = HardwareDevice::create(['user_id' => $this->user->id, 'name' => 'Thinkpad']);
        $this->otro = HardwareDevice::create(['user_id' => $this->user->id, 'name' => 'Sobremesa']);
    }

    #[Test]
    public function suma_lo_de_hoy_y_guarda_los_maximos(): void
    {
        $this->racha($this->teclado, pulsaciones: 1200, especiales: 50, score: 80, cuando: now());
        $this->racha($this->teclado, pulsaciones: 420, especiales: 12, score: 87, cuando: now()->subHours(2));
        // De ayer: no cuenta.
        $this->racha($this->teclado, pulsaciones: 9999, especiales: 999, score: 999, cuando: now()->subDay());

        $respuesta = $this->pide();

        $respuesta->assertOk()
            ->assertJsonPath('data.period', 'today')
            ->assertJsonPath('data.pulsations_total', 1620)
            ->assertJsonPath('data.pulsations_total_special_keys', 62)
            // El máximo del periodo, para no perder el récord al reiniciar.
            ->assertJsonPath('data.combo_score', 87)
            ->assertJsonPath('data.pulsation_high', 1200)
            ->assertJsonPath('data.sessions', 2);
    }

    #[Test]
    public function sin_el_parametro_date_se_entiende_hoy(): void
    {
        $this->racha($this->teclado, pulsaciones: 500, especiales: 10, score: 40, cuando: now());

        $this->pide('')->assertOk()
            ->assertJsonPath('data.period', 'today')
            ->assertJsonPath('data.pulsations_total', 500);
    }

    #[Test]
    public function el_mes_en_curso_incluye_los_dias_anteriores(): void
    {
        $this->racha($this->teclado, pulsaciones: 500, especiales: 10, score: 40, cuando: now());
        $this->racha($this->teclado, pulsaciones: 700, especiales: 20, score: 50, cuando: now()->startOfMonth());

        $this->pide('date=month')->assertOk()
            ->assertJsonPath('data.period', 'month')
            ->assertJsonPath('data.pulsations_total', 1200);
    }

    #[Test]
    public function un_dia_concreto_de_un_mes_concreto(): void
    {
        $this->racha($this->teclado, pulsaciones: 333, especiales: 3, score: 30, cuando: '2026-03-15 10:00:00');
        $this->racha($this->teclado, pulsaciones: 111, especiales: 1, score: 10, cuando: '2026-03-16 10:00:00');

        $this->pide('date=2026-03-15')->assertOk()
            ->assertJsonPath('data.period', '2026-03-15')
            ->assertJsonPath('data.pulsations_total', 333);
    }

    #[Test]
    public function un_mes_entero_concreto(): void
    {
        $this->racha($this->teclado, pulsaciones: 333, especiales: 3, score: 30, cuando: '2026-03-01 00:30:00');
        $this->racha($this->teclado, pulsaciones: 111, especiales: 1, score: 10, cuando: '2026-03-31 23:30:00');
        $this->racha($this->teclado, pulsaciones: 999, especiales: 9, score: 90, cuando: '2026-04-01 00:30:00');

        $this->pide('date=2026-03')->assertOk()
            ->assertJsonPath('data.pulsations_total', 444);
    }

    /**
     * El resumen es de **un** cacharro: el que pregunta. Lo que cuente el de al
     * lado no es asunto suyo.
     */
    #[Test]
    public function devuelve_solo_lo_del_dispositivo_que_pregunta(): void
    {
        $this->racha($this->teclado, pulsaciones: 1000, especiales: 10, score: 50, cuando: now());
        $this->racha($this->otro, pulsaciones: 2000, especiales: 20, score: 60, cuando: now());

        $this->pide()->assertOk()
            ->assertJsonPath('data.hardware_device_id', $this->teclado->id)
            ->assertJsonPath('data.pulsations_total', 1000);
    }

    #[Test]
    public function device_id_es_obligatorio(): void
    {
        $respuesta = $this->getJson(
            $this->apiUrl('keycounter/summary?date=today'),
            $this->moduleHeaders($this->user, TokenAbilities::KEYCOUNTER_READ)
        );

        $this->assertErrorResponse($respuesta, 422);
        $respuesta->assertJsonValidationErrors(['device_id']);
    }

    #[Test]
    public function el_raton_va_en_su_propio_bloque(): void
    {
        Mouse::create([
            'user_id' => $this->user->id,
            'hardware_device_id' => $this->teclado->id,
            'start_at' => now()->subMinutes(5),
            'end_at' => now(),
            'duration' => 300,
            'clicks_left' => 200,
            'clicks_right' => 40,
            'clicks_middle' => 10,
            'total_clicks' => 250,
            'clicks_average' => 0.8,
            'weekday' => 1,
        ]);

        $this->pide()->assertOk()
            ->assertJsonPath('data.mouse.clicks_total', 250)
            ->assertJsonPath('data.mouse.clicks_high', 250)
            ->assertJsonPath('data.mouse.sessions', 1);
    }

    /**
     * Un token ligado a un cacharro no puede preguntar por el de al lado.
     */
    #[Test]
    public function un_token_ligado_a_un_dispositivo_no_alcanza_a_otro(): void
    {
        $this->racha($this->teclado, pulsaciones: 1000, especiales: 10, score: 50, cuando: now());
        $this->racha($this->otro, pulsaciones: 2000, especiales: 20, score: 60, cuando: now());

        $headers = $this->headersWithAbilities($this->user, [
            TokenAbilities::KEYCOUNTER_READ,
            TokenAbilities::forDevice($this->teclado),
        ]);

        $this->getJson($this->apiUrl('keycounter/summary?device_id='.$this->teclado->id.'&date=today'), $headers)
            ->assertOk()
            ->assertJsonPath('data.pulsations_total', 1000);

        $ajeno = $this->getJson(
            $this->apiUrl('keycounter/summary?device_id='.$this->otro->id.'&date=today'),
            $headers
        );

        $this->assertErrorResponse($ajeno, 422);
        $ajeno->assertJsonValidationErrors(['device_id']);
    }

    #[Test]
    public function un_periodo_que_no_existe_responde_422(): void
    {
        $this->assertErrorResponse($this->pide('date=basura'), 422);
        $this->assertErrorResponse($this->pide('date=2026-13-45'), 422);
    }

    #[Test]
    public function no_se_puede_pedir_el_dispositivo_de_otro(): void
    {
        $ajeno = HardwareDevice::create([
            'user_id' => $this->createAuthenticatedUser()->id,
            'name' => 'De otro',
        ]);

        $respuesta = $this->getJson(
            $this->apiUrl('keycounter/summary?device_id='.$ajeno->id),
            $this->moduleHeaders($this->user, TokenAbilities::KEYCOUNTER_READ)
        );

        $this->assertErrorResponse($respuesta, 422);
    }

    #[Test]
    public function exige_la_ability_de_lectura(): void
    {
        $url = 'keycounter/summary?device_id='.$this->teclado->id;

        $this->getJson($this->apiUrl($url), $this->guestHeaders())->assertUnauthorized();

        $this->getJson(
            $this->apiUrl($url),
            $this->moduleHeaders($this->user, TokenAbilities::KEYCOUNTER_WRITE)
        )->assertForbidden();
    }

    /**
     * `device_id` es obligatorio, así que va siempre salvo que la prueba sea
     * justamente que falta.
     */
    private function pide(string $query = 'date=today'): TestResponse
    {
        $url = 'keycounter/summary?device_id='.$this->teclado->id.($query === '' ? '' : '&'.$query);

        return $this->getJson(
            $this->apiUrl($url),
            $this->moduleHeaders($this->user, TokenAbilities::KEYCOUNTER_READ)
        );
    }

    private function racha(
        HardwareDevice $device,
        int $pulsaciones,
        int $especiales,
        int $score,
        mixed $cuando
    ): void {
        $racha = Keyboard::create([
            'user_id' => $this->user->id,
            'hardware_device_id' => $device->id,
            'start_at' => $cuando,
            'end_at' => $cuando,
            'duration' => 300,
            'pulsations' => $pulsaciones,
            'pulsations_special_keys' => $especiales,
            'pulsation_average' => 2.0,
            'score' => $score,
            'weekday' => 1,
        ]);

        // `created_at` no está en `$fillable`, así que `create()` lo ignora y
        // pone la hora actual. El resumen agrupa por esa columna —igual que la
        // web de KeyCounter—, así que aquí hay que forzarla.
        $racha->forceFill(['created_at' => $cuando])->save();
    }
}
