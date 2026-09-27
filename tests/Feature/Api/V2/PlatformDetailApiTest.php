<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V2;

use App\Models\PlatformCategory;
use App\Models\SocialNetwork;
use App\Models\UserDetail;
use App\Models\UserSocial;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Api\ApiTestCase;
use Tests\Traits\BuildsApiContents;
use Tests\Traits\SeedsProductionContentStatuses;
use Tests\Traits\UsesTemporaryStorage;

/**
 * La ficha de una plataforma, completa como en la v1 y sin sus claves de
 * publicación (P7 de la auditoría de contenidos; F9 del plan del 2026-09-24).
 */
class PlatformDetailApiTest extends ApiTestCase
{
    use BuildsApiContents;
    use SeedsProductionContentStatuses;
    use UsesTemporaryStorage;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareApiContents();

        $this->platform->update([
            'youtube_channel_id' => 'UC123',
            'youtube_presentation_video_id' => 'vid42',
            'twitter' => 'raupulus',
            'twitter_token' => 'SECRETO-DE-TWITTER',
            'mastodon' => '@raupulus@mastodon.social',
            'mastodon_token' => 'SECRETO-DE-MASTODON',
            'twitch' => 'raupulus',
            'tiktok' => 'raupulus',
            'instagram' => 'raupulus',
        ]);
    }

    #[Test]
    public function the_platform_file_has_everything_the_v1_gave(): void
    {
        UserDetail::query()->create(['user_id' => $this->admin->id, 'profession' => 'Desarrollador', 'web' => 'https://raupulus.dev']);
        $network = SocialNetwork::query()->create(['name' => 'GitHub', 'slug' => 'github', 'type' => 'social', 'color' => '#000000', 'url' => 'https://github.com']);
        UserSocial::query()->create(['user_id' => $this->admin->id, 'social_network_id' => $network->id, 'nick' => 'raupulus', 'url' => 'https://github.com/raupulus']);

        $project = $this->published(['type_id' => $this->typeId('project'), 'title' => 'Proyecto']);
        $project->technologies()->attach($this->technology('Laravel')->id);
        $draft = $this->published(['type_id' => $this->typeId('project'), 'status_id' => 1, 'slug' => 'borrador']);
        $draft->technologies()->attach($this->technology('Rust')->id);
        $this->published(['title' => 'Un artículo']);
        $this->published(['title' => 'Otro artículo']);
        $this->published(['type_id' => $this->typeId('page'), 'title' => 'Sobre mí']);

        $data = $this->getJson("/api/v2/platforms/{$this->platform->slug}")->assertOk()->json('data');

        $this->assertSame($this->platform->title, $data['name']);
        $this->assertSame($this->platform->url_about, $data['url_about']);
        $this->assertSame([
            'youtube_channel_id' => 'UC123',
            'youtube_presentation_video_id' => 'vid42',
            'twitter' => 'raupulus',
            'mastodon' => '@raupulus@mastodon.social',
            'twitch' => 'raupulus',
            'tiktok' => 'raupulus',
            'instagram' => 'raupulus',
        ], $data['social_networks']);

        // El autor, con lo suyo y no con «Developer» escrito a mano.
        $this->assertSame('Desarrollador', $data['author']['profession']);
        $this->assertSame('https://raupulus.dev', $data['author']['web']);
        $this->assertSame(['github'], array_column($data['author']['social_networks'], 'slug'));

        $this->assertSame(['Laravel'], array_column($data['technologies'], 'name'), 'Sólo las de sus proyectos publicados.');

        $this->assertSame(4, $data['contents']['total'], 'El borrador no cuenta.');
        $this->assertSame(['blog' => 2, 'page' => 1, 'project' => 1], collect($data['contents']['types'])->pluck('total', 'slug')->sortKeys()->all());
        $this->assertSame(['Sobre mí'], array_column($data['pages'], 'title'));
    }

    #[Test]
    public function an_author_without_details_has_no_invented_profession(): void
    {
        $author = $this->getJson("/api/v2/platforms/{$this->platform->slug}")->assertOk()->json('data.author');

        $this->assertNull($author['profession']);
        $this->assertNull($author['web']);
    }

    #[Test]
    public function no_platform_response_carries_a_token(): void
    {
        $this->richContent();
        $slug = $this->platform->slug;

        foreach ([
            '/api/v2/platforms',
            "/api/v2/platforms/{$slug}",
            "/api/v2/platforms/{$slug}/categories",
            "/api/v2/platforms/{$slug}/tags",
            "/api/v2/platforms/{$slug}/contents",
            "/api/v2/platforms/{$slug}/contents/highlights",
            "/api/v2/platforms/{$slug}/contents/estacion?include=all",
        ] as $url) {
            $response = $this->getJson($url)->assertOk();

            $this->assertStringNotContainsString('SECRETO', (string) $response->getContent(), $url);
            $this->assertSame([], $this->tokenKeys($response->json()), $url);
        }
    }

    #[Test]
    public function the_file_and_the_categories_are_renewed_when_what_they_show_changes(): void
    {
        $url = "/api/v2/platforms/{$this->platform->slug}";
        $this->getJson($url)->assertOk()->assertJsonPath('data.author.profession', null);
        $this->assertSame([], $this->getJson("{$url}/categories")->assertOk()->json('data'));

        UserDetail::query()->create(['user_id' => $this->admin->id, 'profession' => 'Electrónico']);
        $this->getJson($url)->assertJsonPath('data.author.profession', 'Electrónico');

        $this->admin->update(['name' => 'Raúl']);
        $this->assertStringContainsString('Raúl', (string) $this->getJson($url)->json('data.author.name'));

        $this->platform->update(['description' => 'Descripción nueva']);
        $this->getJson($url)->assertJsonPath('data.description', 'Descripción nueva');

        // Antes, las categorías se guardaban para siempre y sólo se renovaban
        // al guardar una categoría, no al añadirla a la plataforma.
        $category = $this->category('Meteorología');
        $this->assertSame(['meteorologia'], array_column($this->getJson("{$url}/categories")->json('data'), 'slug'));

        PlatformCategory::query()->where('category_id', $category->id)->sole()->delete();
        $this->assertSame([], $this->getJson("{$url}/categories")->json('data'));

        // Un contenido publicado nuevo entra en los recuentos.
        $this->published(['title' => 'Nuevo']);
        $this->getJson($url)->assertJsonPath('data.contents.total', 1);
        $this->assertSame(0, (int) DB::table('jobs')->count());
    }

    /**
     * Claves que terminan en `_token`, en cualquier nivel.
     *
     * @return list<string>
     */
    private function tokenKeys(mixed $json, string $path = ''): array
    {
        if (! is_array($json)) {
            return [];
        }

        $found = [];

        foreach ($json as $key => $value) {
            if (is_string($key) && str_ends_with($key, '_token')) {
                $found[] = ltrim("{$path}.{$key}", '.');
            }

            $found = [...$found, ...$this->tokenKeys($value, "{$path}.{$key}")];
        }

        return $found;
    }
}
