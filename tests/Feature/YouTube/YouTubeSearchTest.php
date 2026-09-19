<?php

declare(strict_types=1);

namespace Tests\Feature\YouTube;

use App\Enums\UserRoleEnum;
use App\Filament\Admin\Resources\Content\Contents\Pages\CreateContent;
use App\Models\Platform;
use App\Models\User;
use App\Services\YouTube\YouTubeService;
use Database\Seeders\RolesTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class YouTubeSearchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        (new RolesTableSeeder)->run();
        Cache::flush();
        Config::set('google.api_key', 'AIzaFakeTestKeyForYouTubeTesting12345');
    }

    private function createFakeYouTubeSearchResponse(int $count = 2): array
    {
        $items = [];
        for ($i = 1; $i <= $count; $i++) {
            $items[] = [
                'id' => [
                    'kind' => 'youtube#video',
                    'videoId' => "fakeVideoId{$i}",
                ],
                'snippet' => [
                    'publishedAt' => '2026-09-19T10:00:00Z',
                    'channelId' => 'UC_fake_channel_123',
                    'title' => "Vídeo de prueba {$i}",
                    'description' => "Descripción del vídeo de prueba {$i}",
                    'thumbnails' => [
                        'default' => ['url' => "https://i.ytimg.com/vi/fakeVideoId{$i}/default.jpg", 'width' => 120, 'height' => 90],
                        'medium' => ['url' => "https://i.ytimg.com/vi/fakeVideoId{$i}/mqdefault.jpg", 'width' => 320, 'height' => 180],
                        'high' => ['url' => "https://i.ytimg.com/vi/fakeVideoId{$i}/hqdefault.jpg", 'width' => 480, 'height' => 360],
                    ],
                ],
            ];
        }

        return [
            'kind' => 'youtube#searchListResponse',
            'pageInfo' => [
                'totalResults' => 50,
                'resultsPerPage' => 10,
            ],
            'nextPageToken' => 'CAoQAA',
            'prevPageToken' => null,
            'items' => $items,
        ];
    }

    public function test_guest_cannot_access_youtube_search_endpoint(): void
    {
        $response = $this->getJson(route('admin.youtube.search', ['q' => 'laravel']));

        $response->assertUnauthorized();
    }

    public function test_regular_user_cannot_access_youtube_search_endpoint(): void
    {
        $user = User::factory()->create([
            'role_id' => UserRoleEnum::User->value,
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->getJson(route('admin.youtube.search', ['q' => 'laravel']));

        $response->assertForbidden();
    }

    public function test_admin_can_search_youtube_videos(): void
    {
        $admin = User::factory()->create([
            'role_id' => UserRoleEnum::Admin->value,
            'is_active' => true,
        ]);

        $fakeResponse = $this->createFakeYouTubeSearchResponse(2);

        Http::fake([
            'https://www.googleapis.com/youtube/v3/search*' => Http::response($fakeResponse, 200),
        ]);

        $response = $this->actingAs($admin)->getJson(route('admin.youtube.search', [
            'q' => 'laravel',
            'channel_id' => 'UC_fake_channel_123',
        ]));

        $response->assertOk()
            ->assertJsonPath('pageInfo.totalResults', 50)
            ->assertJsonPath('items.0.id.videoId', 'fakeVideoId1')
            ->assertJsonPath('items.1.id.videoId', 'fakeVideoId2');

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'https://www.googleapis.com/youtube/v3/search')
                && $request['q'] === 'laravel'
                && $request['channelId'] === 'UC_fake_channel_123'
                && $request['key'] === 'AIzaFakeTestKeyForYouTubeTesting12345';
        });
    }

    public function test_youtube_search_caches_results(): void
    {
        $admin = User::factory()->create([
            'role_id' => UserRoleEnum::Admin->value,
            'is_active' => true,
        ]);

        $fakeResponse = $this->createFakeYouTubeSearchResponse(1);

        Http::fake([
            'https://www.googleapis.com/youtube/v3/search*' => Http::response($fakeResponse, 200),
        ]);

        // Primera petición: llama a la API de Google
        $response1 = $this->actingAs($admin)->getJson(route('admin.youtube.search', [
            'q' => 'cache test',
            'channel_id' => 'UC_channel_cache',
        ]));
        $response1->assertOk();

        // Segunda petición idéntica: debe responder desde caché
        $response2 = $this->actingAs($admin)->getJson(route('admin.youtube.search', [
            'q' => 'cache test',
            'channel_id' => 'UC_channel_cache',
        ]));
        $response2->assertOk();

        // Se verifica que solo se hizo 1 llamada HTTP a Google
        Http::assertSentCount(1);
    }

    public function test_youtube_service_handles_missing_api_key_gracefully(): void
    {
        $service = new YouTubeService(apiKey: '');

        $result = $service->search('test');

        $this->assertArrayHasKey('error', $result);
        $this->assertStringContainsString('no está configurada', $result['error']['message']);
    }

    public function test_youtube_service_handles_google_api_error_response(): void
    {
        $admin = User::factory()->create([
            'role_id' => UserRoleEnum::Admin->value,
            'is_active' => true,
        ]);

        Http::fake([
            'https://www.googleapis.com/youtube/v3/search*' => Http::response([
                'error' => [
                    'code' => 403,
                    'message' => 'The request cannot be completed because you have exceeded your quota.',
                ],
            ], 403),
        ]);

        $response = $this->actingAs($admin)->getJson(route('admin.youtube.search', [
            'q' => 'quota test',
        ]));

        $response->assertStatus(403)
            ->assertJsonPath('error.message', 'The request cannot be completed because you have exceeded your quota.');
    }

    public function test_youtube_video_field_endpoint_is_rendered_without_exposing_api_key(): void
    {
        $admin = User::factory()->create([
            'role_id' => UserRoleEnum::Admin->value,
            'is_active' => true,
        ]);

        Platform::factory()->create([
            'youtube_channel_id' => 'UC_channel_999',
            'title' => 'Plataforma Test',
        ]);

        $response = $this->actingAs($admin)->get(CreateContent::getUrl());
        $response->assertOk();

        $content = $response->getContent();

        // Verificar que la clave de API nunca viaja en el HTML
        $this->assertStringNotContainsString('AIzaFakeTestKeyForYouTubeTesting12345', $content);
        $this->assertStringNotContainsString('apiKey:', $content);

        // Verificar que sí viaja la ruta del endpoint del backend
        $this->assertStringContainsString('searchEndpoint:', $content);
        $this->assertStringContainsString('admin\/youtube\/search', $content);
    }
}
