<?php

namespace Tests\Feature\Api\V2;

use App\Models\Hardware\HardwareDevice;
use App\Models\Hardware\HardwareType;
use App\Models\KeyCounter\Keyboard;
use App\Models\User;
use Tests\Feature\Api\ApiTestCase;

class KeyCounterWebTest extends ApiTestCase
{
    public function test_keycounter_web_page_renders_successfully_with_data()
    {
        // 1. Create a user, hardware type, and a hardware device
        $user = User::factory()->create(['role_id' => 1]);
        $type = HardwareType::create(['name' => 'Generic', 'description' => 'Tipo de prueba']);
        $device = HardwareDevice::create([
            'user_id' => $user->id,
            'hardware_type_id' => $type->id,
            'name' => 'test-device',
            'name_friendly' => 'Test Keyboard Device',
        ]);

        // 2. Create some keyboard records
        Keyboard::create([
            'user_id' => $user->id,
            'hardware_device_id' => $device->id,
            'start_at' => now()->subMinutes(10),
            'end_at' => now(),
            'duration' => 600,
            'pulsations' => 1200,
            'pulsations_special_keys' => 50,
            'pulsation_average' => 2.0,
            'score' => 75,
            'weekday' => 1,
            'created_at' => now(),
        ]);

        // 3. Request the public keycounter web view
        $response = $this->get('/keycounter');

        // 4. Assert response is HTTP 200
        $response->assertStatus(200);

        // 5. Assert the page contains the custom styled widgets and translations
        $response->assertSee('functions');
        $response->assertSee('military_tech');
        $response->assertSee('event');
        $response->assertSee('bar_chart');
        $response->assertSee('devices');
        $response->assertSee('dns');
        $response->assertSee('Test Keyboard Device');
    }
}
