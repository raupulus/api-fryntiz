<?php

declare(strict_types=1);

namespace Tests\Feature\Validation;

use App\Filament\Tenant\Pages\EditProfile;
use App\Models\File;
use App\Models\FileThumbnail;
use App\Models\Hardware\HardwareDevice;
use App\Models\Platform;
use App\Models\SmartPlant\SmartPlantPlant;
use App\Models\User;
use App\Services\CaptchaResult;
use App\Services\Contact\ContactService;
use App\Support\Auth\TokenAbilities;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Api\ApiTestCase;

class Fase3ValidationAndGdprTest extends ApiTestCase
{
    protected string $apiPrefix = 'api/v2';

    #[Test]
    public function edits_own_profile_persists_new_password_when_current_password_matches(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('OldPass123!'),
            'is_active' => true,
        ]);

        Filament::setCurrentPanel(Filament::getPanel('tenant'));
        $this->actingAs($user);

        Livewire::test(EditProfile::class)
            ->fillForm([
                'name' => $user->name,
                'current_password' => 'OldPass123!',
                'new_password' => 'NewSecurePass456!',
                'new_password_confirmation' => 'NewSecurePass456!',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertTrue(Hash::check('NewSecurePass456!', $user->fresh()->password));
    }

    #[Test]
    public function newsletter_and_contact_enforce_varchar_limits_without_sql_500(): void
    {
        Mail::fake();
        $user = $this->createAuthenticatedUser();
        $platform = Platform::create(['user_id' => $user->id, 'title' => 'Web', 'slug' => 'web-test']);

        $tooLongName = $this->postJson(
            $this->apiUrl('newsletter/subscriptions'),
            [
                'email' => 'reader@example.test',
                'name' => str_repeat('a', 101),
                'platform_id' => $platform->id,
            ],
            $this->guestHeaders()
        );
        $this->assertErrorResponse($tooLongName, 422);

        $longUserAgent = $this->postJson(
            $this->apiUrl('newsletter/subscriptions'),
            [
                'email' => 'reader2@example.test',
                'name' => 'Ana',
                'platform_id' => $platform->id,
                'privacy_accepted' => true,
            ],
            array_merge($this->guestHeaders(), ['User-Agent' => str_repeat('U', 700)])
        );
        $this->assertSuccessResponse($longUserAgent, 201);

        $email = app(ContactService::class)->register(
            [
                'email' => 'sender@example.test',
                'subject' => 'Consulta',
                'message' => 'Hola mundo desde el formulario.',
                'privacity' => true,
                'platform_id' => $platform->id,
            ],
            [
                'ip' => '198.51.100.10',
                'user_agent' => str_repeat('A', 400),
                'referer' => 'https://example.test/?utm='.str_repeat('x', 400),
                'accept_language' => 'es-ES',
                'host' => 'example.test',
            ],
            new CaptchaResult(configured: false, valid: true, score: null, provider: 'none')
        );

        $this->assertSame(255, mb_strlen((string) $email->client_user_agent));
        $this->assertSame(255, mb_strlen((string) $email->client_referer));
    }

    #[Test]
    public function keycounter_and_smartplant_requests_reject_malformed_dates_and_out_of_range_values_with_422(): void
    {
        $user = $this->createAuthenticatedUser();
        $device = HardwareDevice::create(['user_id' => $user->id, 'name' => 'Test Rig']);
        $plant = SmartPlantPlant::create([
            'user_id' => $user->id,
            'name' => 'Cactus',
            'name_scientific' => 'Cactaceae',
            'description' => 'Desc',
            'details' => 'Details',
            'start_at' => now(),
        ]);

        // Fecha mal formada → 422 (antes lanzaba 500 Carbon InvalidFormatException)
        $badDate = $this->postJson(
            $this->apiUrl('keycounter/keyboard-sessions'),
            [
                'hardware_device_id' => $device->id,
                'start_at' => 'not-a-date',
                'end_at' => '2026-11-23 10:00:00',
                'pulsations' => 10,
                'pulsations_special_keys' => 1,
                'pulsation_average' => 1.0,
                'score' => 5,
                'weekday' => 1,
            ],
            $this->deviceHeaders($device, [TokenAbilities::KEYCOUNTER_WRITE])
        );
        $this->assertErrorResponse($badDate, 422);

        // end_at anterior a start_at → 422
        $negativeDuration = $this->postJson(
            $this->apiUrl('keycounter/keyboard-sessions'),
            [
                'hardware_device_id' => $device->id,
                'start_at' => '2026-11-23 11:00:00',
                'end_at' => '2026-11-23 10:00:00',
                'pulsations' => 10,
                'pulsations_special_keys' => 1,
                'pulsation_average' => 1.0,
                'score' => 5,
                'weekday' => 1,
            ],
            $this->deviceHeaders($device, [TokenAbilities::KEYCOUNTER_WRITE])
        );
        $this->assertErrorResponse($negativeDuration, 422);

        // SmartPlant soil_humidity no entero → 422 (antes se casteaba a 0)
        $badHumidity = $this->postJson(
            $this->apiUrl("smartplant/plants/{$plant->id}/readings"),
            [
                'hardware_device_id' => $device->id,
                'soil_humidity' => 'basura',
                'humidity' => 150,
            ],
            $this->deviceHeaders($device, [TokenAbilities::SMARTPLANT_WRITE])
        );
        $this->assertErrorResponse($badHumidity, 422);

        // Hardware device status temp fuera de DECIMAL(6,2) → 422
        $badTemp = $this->putJson(
            $this->apiUrl("hardware/devices/{$device->id}/status"),
            ['temp' => 10000],
            $this->deviceHeaders($device, [TokenAbilities::HARDWARE_WRITE])
        );
        $this->assertErrorResponse($badTemp, 422);
    }

    #[Test]
    public function deleting_file_and_force_deleting_user_purges_physical_files_from_storage(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        $user = User::factory()->create([
            'profile_photo_path' => 'profile-photos/avatar-test.webp',
        ]);
        Storage::disk('public')->put('profile-photos/avatar-test.webp', 'fake-webp');

        Storage::disk('local')->put('public/uploads/doc.webp', 'fake-original');
        Storage::disk('local')->put('public/uploads/160/doc.webp', 'fake-thumb');

        $file = File::create([
            'user_id' => $user->id,
            'name' => 'doc.webp',
            'original_name' => 'doc.webp',
            'alt' => 'Doc',
            'title' => 'Doc',
            'path' => 'uploads',
            'storage_path' => 'public/uploads',
        ]);

        FileThumbnail::create([
            'file_id' => $file->id,
            'name' => 'doc.webp',
            'path' => 'uploads/160',
            'storage_path' => 'public/uploads/160',
            'key' => 'small',
            'width' => 160,
            'height' => 160,
            'size' => 10,
        ]);

        // Purga RGPD al forceDelete del usuario
        $user->forceDelete();

        Storage::disk('public')->assertMissing('profile-photos/avatar-test.webp');
        Storage::disk('local')->assertMissing('public/uploads/doc.webp');
        Storage::disk('local')->assertMissing('public/uploads/160/doc.webp');
        $this->assertDatabaseMissing('files', ['id' => $file->id]);
    }
}
