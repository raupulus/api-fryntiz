<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V2;

use App\Http\Resources\V2\KeyCounter\KeyboardResource;
use App\Http\Resources\V2\KeyCounter\MouseResource;
use App\Http\Resources\V2\SmartPlant\SmartPlantRegisterResource;
use App\Http\Resources\V2\WeatherStation\TemperatureResource;
use App\Jobs\ProcessContentViewJob;
use App\Jobs\SendContactEmailJob;
use App\Models\Hardware\HardwareDevice;
use App\Models\KeyCounter\Keyboard;
use App\Models\KeyCounter\Mouse;
use App\Models\SmartPlant\SmartPlantPlant;
use App\Models\SmartPlant\SmartPlantRegister;
use App\Models\WeatherStation\Temperature;
use App\Services\RecaptchaService;
use App\Support\WeatherStation\CapWarnings;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Api\ApiTestCase;

class Fase4ApiAndOpsTest extends ApiTestCase
{
    #[Test]
    public function exact_api_v1_returns_410_and_405_preserves_allow_header(): void
    {
        $v1 = $this->getJson('/api/v1');
        $this->assertErrorResponse($v1, 410);

        $notAllowed = $this->deleteJson('/', [], $this->guestHeaders());
        $this->assertErrorResponse($notAllowed, 405);
        $this->assertNotEmpty($notAllowed->headers->get('Allow'));
    }

    #[Test]
    public function iot_resources_serialize_numeric_columns_as_floats_and_keycounter_dates_as_iso8601(): void
    {
        $user = $this->createAuthenticatedUser();
        $device = HardwareDevice::create(['user_id' => $user->id, 'name' => 'Sensor Hub']);
        $plant = SmartPlantPlant::create([
            'user_id' => $user->id,
            'name' => 'Bonsai',
            'name_scientific' => 'Ficus retusa',
            'description' => 'Desc',
            'details' => 'Details',
            'start_at' => now(),
        ]);

        $temp = Temperature::create([
            'hardware_device_id' => $device->id,
            'value' => 23.45,
        ])->fresh();

        $tempArr = (new TemperatureResource($temp))->toArray(new Request);
        $this->assertIsFloat($tempArr['value']);
        $this->assertSame(23.45, $tempArr['value']);

        $register = SmartPlantRegister::create([
            'plant_id' => $plant->id,
            'hardware_device_id' => $device->id,
            'temperature' => 21.50,
            'humidity' => 55.25,
            'pressure' => 1013.20,
            'soil_humidity' => 45,
        ])->fresh();

        $regArr = (new SmartPlantRegisterResource($register))->toArray(new Request);
        $this->assertIsFloat($regArr['temperature']);
        $this->assertIsFloat($regArr['humidity']);
        $this->assertIsFloat($regArr['pressure']);

        $kb = Keyboard::create([
            'user_id' => $user->id,
            'hardware_device_id' => $device->id,
            'start_at' => '2026-11-23 10:00:00',
            'end_at' => '2026-11-23 10:05:00',
            'duration' => 300,
            'pulsations' => 300,
            'pulsations_special_keys' => 10,
            'pulsation_average' => 1.5,
            'score' => 50,
            'weekday' => 1,
        ])->fresh();

        $kbArr = (new KeyboardResource($kb))->toArray(new Request);
        $this->assertIsFloat($kbArr['pulsation_average']);
        $this->assertStringContainsString('T', (string) $kbArr['start_at']);
        $this->assertStringEndsWith('Z', (string) $kbArr['end_at']);

        $ms = Mouse::create([
            'user_id' => $user->id,
            'hardware_device_id' => $device->id,
            'start_at' => '2026-11-23 10:00:00',
            'end_at' => '2026-11-23 10:05:00',
            'duration' => 300,
            'clicks_left' => 10,
            'clicks_right' => 5,
            'clicks_middle' => 0,
            'total_clicks' => 15,
            'clicks_average' => 2.5,
            'weekday' => 1,
        ])->fresh();

        $msArr = (new MouseResource($ms))->toArray(new Request);
        $this->assertIsFloat($msArr['clicks_average']);
        $this->assertStringContainsString('T', (string) $msArr['start_at']);
    }

    #[Test]
    public function cap_warnings_detects_and_inflates_gzip_payloads(): void
    {
        $fakeTar = str_repeat("\0", 257).'ustar'.str_repeat("\0", 762);
        $gzipped = gzencode($fakeTar);

        // No lanza excepción y supera la detección gzip 1f8b -> ustar
        $this->assertIsArray(CapWarnings::fromTar((string) $gzipped));
        $this->assertSame([], CapWarnings::fromTar("\x1f\x8bcorrupt-gzip-bytes"));
    }

    #[Test]
    public function recaptcha_rejects_4xx_responses_and_action_mismatch(): void
    {
        config(['google.recaptcha.secret_key' => 'test-secret']);

        Http::fake([
            'https://www.google.com/recaptcha/api/siteverify' => Http::sequence()
                ->push(['error' => 'bad request'], 400)
                ->push(['success' => true, 'score' => 0.9, 'action' => 'contact'], 200)
                ->push(['success' => true, 'score' => 0.9, 'action' => 'login'], 200),
        ]);

        $service = app(RecaptchaService::class);

        $fourXx = $service->verify('tok', '198.51.100.1');
        $this->assertFalse($fourXx->valid);

        $wrongAction = $service->verify('tok', '198.51.100.1', 0.3, 'login');
        $this->assertFalse($wrongAction->valid);

        $rightAction = $service->verify('tok', '198.51.100.1', 0.3, 'login');
        $this->assertTrue($rightAction->valid);
    }

    #[Test]
    public function jobs_define_explicit_timeouts_and_printers_cleanup_is_scheduled(): void
    {
        $viewJob = new ProcessContentViewJob(1, now());
        $mailJob = new SendContactEmailJob(1);

        $this->assertGreaterThan(0, $viewJob->timeout);
        $this->assertGreaterThan(0, $mailJob->timeout);

        $events = collect(app(Schedule::class)->events())
            ->map(fn ($event) => (string) $event->command)
            ->implode("\n");

        $this->assertStringContainsString('printers:cleanup-stale-jobs', $events);
    }
}
