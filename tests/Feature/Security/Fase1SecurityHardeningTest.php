<?php

declare(strict_types=1);

namespace Tests\Feature\Security;

use App\Enums\UserRoleEnum;
use App\Filament\Admin\Resources\Platforms\PlatformResource;
use App\Models\AiProvider;
use App\Models\ApiToken;
use App\Models\Category;
use App\Models\Content\Content;
use App\Models\CV\Curriculum;
use App\Models\File;
use App\Models\Gallery;
use App\Models\Hardware\HardwareDevice;
use App\Models\KeyCounter\Keyboard;
use App\Models\KeyCounter\Mouse;
use App\Models\Platform;
use App\Models\PlatformEditorProfile;
use App\Models\SmartPlant\SmartPlantPlant;
use App\Models\User;
use App\Policies\AiProviderPolicy;
use App\Policies\FilePolicy;
use App\Policies\PlatformEditorProfilePolicy;
use App\Services\Hardware\DeviceTokenService;
use App\Support\Auth\TokenAbilities;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Api\ApiTestCase;

class Fase1SecurityHardeningTest extends ApiTestCase
{
    protected string $apiPrefix = 'api/v2';

    #[Test]
    public function admin_cannot_issue_device_token_for_superadmin_device(): void
    {
        $superAdmin = $this->createAuthenticatedUser(UserRoleEnum::SuperAdmin->value);
        $admin = $this->createAuthenticatedUser(UserRoleEnum::Admin->value);
        $device = HardwareDevice::create([
            'user_id' => $superAdmin->id,
            'name' => 'SuperAdmin Device',
        ]);

        $this->expectException(AuthorizationException::class);

        app(DeviceTokenService::class)->issue(
            $device,
            [TokenAbilities::HARDWARE_READ],
            null,
            $admin
        );
    }

    #[Test]
    public function admin_cannot_manage_superadmin_owned_resources_and_device_tokens_cannot_escalate(): void
    {
        $superAdmin = $this->createAuthenticatedUser(UserRoleEnum::SuperAdmin->value);
        $admin = $this->createAuthenticatedUser(UserRoleEnum::Admin->value);

        $device = HardwareDevice::create(['user_id' => $superAdmin->id, 'name' => 'SA HW']);
        $plant = SmartPlantPlant::create([
            'user_id' => $superAdmin->id,
            'name' => 'SA Plant',
            'name_scientific' => 'Aloe vera',
            'description' => 'Planta de prueba',
            'details' => 'Detalles de prueba',
            'start_at' => now(),
        ]);
        $curriculum = Curriculum::create([
            'user_id' => $superAdmin->id,
            'title' => 'SA CV',
            'slug' => 'sa-cv',
        ]);
        $gallery = Gallery::create([
            'user_id' => $superAdmin->id,
            'name' => 'SA Gallery',
        ]);
        $platform = Platform::create(['user_id' => $superAdmin->id, 'title' => 'SA Platform', 'slug' => 'sa-platform']);
        $content = Content::create([
            'author_id' => $superAdmin->id,
            'platform_id' => $platform->id,
            'title' => 'SA Content',
            'slug' => 'sa-content',
            'status_id' => 1,
        ]);
        $file = File::create([
            'user_id' => $superAdmin->id,
            'name' => 'secret.txt',
            'original_name' => 'secret.txt',
            'alt' => 'Secret file',
            'title' => 'Secret file',
            'path' => 'private/secret.txt',
            'is_private' => true,
        ]);

        // Frontera Admin -> SuperAdmin
        $this->assertFalse($admin->can('view', $device));
        $this->assertFalse($admin->can('update', $device));
        $this->assertFalse($admin->can('delete', $device));
        $this->assertFalse($admin->can('forceDelete', $device));

        $this->assertFalse($admin->can('view', $plant));
        $this->assertFalse($admin->can('delete', $plant));
        $this->assertFalse($admin->can('forceDelete', $plant));

        $this->assertFalse($admin->can('view', $curriculum));
        $this->assertFalse($admin->can('update', $curriculum));
        $this->assertFalse($admin->can('delete', $curriculum));

        $this->assertFalse($admin->can('view', $gallery));
        $this->assertFalse($admin->can('delete', $gallery));

        $this->assertFalse($admin->can('view', $platform));
        $this->assertFalse($admin->can('update', $platform));
        $this->assertFalse($admin->can('delete', $platform));

        $this->assertFalse($admin->can('update', $content));
        $this->assertFalse($admin->can('delete', $content));

        $this->assertFalse($admin->can('view', $file));
        $this->assertFalse($admin->can('delete', $file));

        // Trampa 5: token de dispositivo perteneciente a un SuperAdmin no hereda privilegios administrativos
        $token = app(DeviceTokenService::class)->issue($device, [TokenAbilities::HARDWARE_WRITE])->accessToken;
        $superAdmin->withAccessToken($token);

        $this->assertFalse($superAdmin->can('viewAny', User::class));
        $this->assertFalse($superAdmin->can('viewAny', AiProvider::class));
        $this->assertFalse($superAdmin->can('viewAny', Platform::class));
        $this->assertFalse($superAdmin->can('viewAny', Category::class));
        $this->assertFalse($superAdmin->can('view', $curriculum));
        $this->assertFalse($superAdmin->can('delete', $file));
    }

    #[Test]
    public function platform_resource_scopes_query_for_editor_and_admin(): void
    {
        $superAdmin = $this->createAuthenticatedUser(UserRoleEnum::SuperAdmin->value);
        $admin = $this->createAuthenticatedUser(UserRoleEnum::Admin->value);
        $editor = $this->createAuthenticatedUser(UserRoleEnum::Editor->value);

        $saPlatform = Platform::create(['user_id' => $superAdmin->id, 'title' => 'SA Web', 'slug' => 'sa-web']);
        $adminPlatform = Platform::create(['user_id' => $admin->id, 'title' => 'Admin Web', 'slug' => 'admin-web']);
        $editor->platforms()->attach($adminPlatform->id);

        $this->actingAs($editor);
        $editorIds = PlatformResource::getEloquentQuery()->pluck('id')->all();
        $this->assertSame([$adminPlatform->id], $editorIds);

        $this->actingAs($admin);
        $adminIds = PlatformResource::getEloquentQuery()->pluck('id')->all();
        $this->assertContains($adminPlatform->id, $adminIds);
        $this->assertNotContains($saPlatform->id, $adminIds);

        $this->actingAs($superAdmin);
        $saIds = PlatformResource::getEloquentQuery()->pluck('id')->all();
        $this->assertContains($saPlatform->id, $saIds);
        $this->assertContains($adminPlatform->id, $saIds);
    }

    #[Test]
    public function keycounter_read_endpoints_respect_device_scope_and_deleting_device_revokes_tokens(): void
    {
        $user = $this->createAuthenticatedUser();
        $deviceA = HardwareDevice::create(['user_id' => $user->id, 'name' => 'Laptop A']);
        $deviceB = HardwareDevice::create(['user_id' => $user->id, 'name' => 'Laptop B']);

        Keyboard::create([
            'user_id' => $user->id,
            'hardware_device_id' => $deviceA->id,
            'start_at' => now()->subMinutes(10),
            'end_at' => now(),
            'duration' => 600,
            'pulsations' => 111,
            'pulsations_special_keys' => 1,
            'pulsation_average' => 1.0,
            'score' => 10,
            'weekday' => 1,
        ]);
        Keyboard::create([
            'user_id' => $user->id,
            'hardware_device_id' => $deviceB->id,
            'start_at' => now()->subMinutes(10),
            'end_at' => now(),
            'duration' => 600,
            'pulsations' => 999,
            'pulsations_special_keys' => 1,
            'pulsation_average' => 1.0,
            'score' => 10,
            'weekday' => 1,
        ]);
        Mouse::create([
            'user_id' => $user->id,
            'hardware_device_id' => $deviceA->id,
            'start_at' => now()->subMinutes(10),
            'end_at' => now(),
            'duration' => 600,
            'clicks_left' => 25,
            'clicks_right' => 5,
            'clicks_middle' => 0,
            'total_clicks' => 30,
            'clicks_average' => 3.0,
            'weekday' => 1,
        ]);
        Mouse::create([
            'user_id' => $user->id,
            'hardware_device_id' => $deviceB->id,
            'start_at' => now()->subMinutes(10),
            'end_at' => now(),
            'duration' => 600,
            'clicks_left' => 500,
            'clicks_right' => 0,
            'clicks_middle' => 0,
            'total_clicks' => 500,
            'clicks_average' => 50.0,
            'weekday' => 1,
        ]);

        $headersA = $this->deviceHeaders($deviceA, [TokenAbilities::KEYCOUNTER_READ]);

        $kb = $this->getJson($this->apiUrl('keycounter/keyboard-sessions'), $headersA);
        $this->assertPaginatedResponse($kb);
        $this->assertSame(1, $kb->json('meta.total'));
        $this->assertSame(111, $kb->json('data.0.pulsations'));

        $ms = $this->getJson($this->apiUrl('keycounter/mouse-sessions'), $headersA);
        $this->assertPaginatedResponse($ms);
        $this->assertSame(1, $ms->json('meta.total'));

        $stats = $this->getJson($this->apiUrl('keycounter/stats'), $headersA);
        $this->assertSuccessResponse($stats);
        $this->assertSame(111, $stats->json('data.lifetime_pulsations'));
        $this->assertSame(30, $stats->json('data.lifetime_clicks'));

        // Al eliminar el dispositivo A, sus tokens `device:{id}` quedan revocados
        $this->assertGreaterThan(0, ApiToken::query()->where('tokenable_id', $user->id)->count());
        $deviceA->delete();
        $this->assertSame(
            0,
            ApiToken::query()
                ->where('tokenable_id', $user->id)
                ->whereJsonContains('abilities', TokenAbilities::forDevice($deviceA))
                ->count()
        );
    }

    #[Test]
    public function inactive_user_is_blocked_from_authenticated_web_routes_and_session_tokens_are_revoked(): void
    {
        $user = $this->createAuthenticatedUser();
        $user->createToken('web-session', TokenAbilities::forSession());

        $user->forceFill(['is_active' => false])->save();

        $this->assertSame(0, $user->tokens()->count());

        $response = $this->actingAs($user)->get('/docs');
        $response->assertStatus(403);
    }

    #[Test]
    public function platform_social_tokens_are_encrypted_at_rest_and_hidden_in_serialization(): void
    {
        $user = $this->createAuthenticatedUser();

        $platform = Platform::create([
            'user_id' => $user->id,
            'title' => 'Encrypted Platform',
            'slug' => 'encrypted-platform',
            'twitter_token' => 'secret-twitter-token-123',
            'mastodon_token' => 'secret-mastodon-token-456',
        ]);

        $rawRow = DB::table('platforms')->where('id', $platform->id)->first();
        $this->assertNotSame('secret-twitter-token-123', $rawRow->twitter_token);
        $this->assertNotSame('secret-mastodon-token-456', $rawRow->mastodon_token);

        $fresh = $platform->fresh();
        $this->assertSame('secret-twitter-token-123', $fresh->twitter_token);
        $this->assertSame('secret-mastodon-token-456', $fresh->mastodon_token);

        $serialized = $fresh->toArray();
        $this->assertArrayNotHasKey('twitter_token', $serialized);
        $this->assertArrayNotHasKey('mastodon_token', $serialized);
    }

    #[Test]
    public function explicit_policies_are_registered_for_ai_provider_editor_profile_and_file(): void
    {
        $this->assertInstanceOf(AiProviderPolicy::class, Gate::getPolicyFor(AiProvider::class));
        $this->assertInstanceOf(PlatformEditorProfilePolicy::class, Gate::getPolicyFor(PlatformEditorProfile::class));
        $this->assertInstanceOf(FilePolicy::class, Gate::getPolicyFor(File::class));
    }
}
