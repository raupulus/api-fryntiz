<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Enums\AiProviderEnum;
use App\Enums\UserRoleEnum;
use App\Filament\Admin\Resources\AiProviders\AiProviderResource;
use App\Filament\Admin\Resources\AiProviders\Pages\CreateAiProvider;
use App\Filament\Admin\Resources\AiProviders\Pages\EditAiProvider;
use App\Filament\Admin\Resources\AiProviders\Pages\ListAiProviders;
use App\Models\AiProvider;
use App\Models\User;
use Database\Seeders\RolesTableSeeder;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Configuración → Proveedores de IA: sólo el superadmin, con la clave cifrada.
 */
class AiProviderResourceTest extends TestCase
{
    use RefreshDatabase;

    private User $superadmin;

    protected function setUp(): void
    {
        parent::setUp();

        (new RolesTableSeeder)->run();
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $this->superadmin = User::factory()->create(['role_id' => UserRoleEnum::SuperAdmin->value, 'is_active' => true]);
    }

    #[Test]
    public function only_the_superadmin_sees_the_providers(): void
    {
        $this->actingAs($this->superadmin)->get(AiProviderResource::getUrl('index'))->assertOk();

        foreach ([UserRoleEnum::Admin, UserRoleEnum::Editor] as $role) {
            $user = User::factory()->create(['role_id' => $role->value, 'is_active' => true]);

            $this->actingAs($user)->get(AiProviderResource::getUrl('index'))->assertForbidden();
            $this->assertFalse(AiProviderResource::canAccess(), $role->name.' no debe ver el menú');
        }
    }

    #[Test]
    public function the_key_is_stored_encrypted_and_never_serialized(): void
    {
        Livewire::actingAs($this->superadmin)
            ->test(CreateAiProvider::class)
            ->fillForm([
                'provider' => AiProviderEnum::Mistral->value,
                'model' => 'mistral-medium-3-5',
                'api_key' => 'clave-de-mistral',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $provider = AiProvider::query()->sole();
        $raw = (string) DB::table('ai_providers')->value('api_key');

        $this->assertSame('clave-de-mistral', $provider->api_key);
        $this->assertNotSame('clave-de-mistral', $raw);
        $this->assertStringNotContainsString('clave-de-mistral', $raw);
        $this->assertArrayNotHasKey('api_key', $provider->toArray());
        $this->assertTrue($provider->isUsable());
    }

    #[Test]
    public function a_provider_can_only_be_added_once(): void
    {
        AiProvider::factory()->create();

        Livewire::actingAs($this->superadmin)
            ->test(CreateAiProvider::class)
            ->fillForm([
                'provider' => AiProviderEnum::Gemini->value,
                'model' => 'gemini-3.8-flash',
                'api_key' => 'otra',
            ])
            ->call('create')
            ->assertHasFormErrors(['provider']);
    }

    #[Test]
    public function editing_shows_the_key_and_an_empty_field_keeps_it(): void
    {
        $provider = AiProvider::factory()->create(['api_key' => 'clave-buena']);

        Livewire::actingAs($this->superadmin)
            ->test(EditAiProvider::class, ['record' => $provider->id])
            ->assertSchemaStateSet(['api_key' => 'clave-buena'])
            ->fillForm(['api_key' => '', 'model' => 'gemini-3.8-flash'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('clave-buena', $provider->refresh()->api_key);
    }

    #[Test]
    public function a_key_encrypted_with_another_app_key_does_not_break_anything_and_can_be_replaced(): void
    {
        $provider = AiProvider::factory()->create();
        DB::table('ai_providers')->where('id', $provider->id)->update(['api_key' => 'eyJpdiI6ImNpZnJhZGEtY29uLW90cmEta2V5In0=']);
        $provider->refresh();

        $this->assertSame(AiProvider::KEY_UNREADABLE, $provider->keyStatus());
        $this->assertFalse($provider->isUsable());
        $this->assertTrue(AiProvider::usable()->isEmpty());

        $this->actingAs($this->superadmin)->get(AiProviderResource::getUrl('index'))->assertOk()->assertSee('Ilegible');

        Livewire::actingAs($this->superadmin)
            ->test(EditAiProvider::class, ['record' => $provider->id])
            ->assertSee('no se puede leer')
            ->fillForm(['api_key' => 'clave-nueva'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('clave-nueva', $provider->refresh()->api_key);
        $this->assertTrue($provider->isUsable());
    }

    #[Test]
    public function only_one_provider_is_the_default(): void
    {
        $gemini = AiProvider::factory()->create(['is_default' => true]);
        $mistral = AiProvider::factory()->provider(AiProviderEnum::Mistral, 'mistral-medium-3-5')->create(['is_default' => true]);

        $this->assertFalse($gemini->refresh()->is_default);
        $this->assertTrue($mistral->refresh()->is_default);
        $this->assertSame([AiProviderEnum::Mistral, AiProviderEnum::Gemini], AiProvider::usable()->pluck('provider')->all());
    }

    #[Test]
    public function probe_makes_a_real_minimal_call_and_says_what_it_got(): void
    {
        $provider = AiProvider::factory()->create();
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response(
            'data: '.json_encode(['candidates' => [['content' => ['parts' => [['text' => 'OK']]], 'finishReason' => 'STOP']]])."\n\n",
            200,
            ['Content-Type' => 'text/event-stream'],
        )]);

        Livewire::actingAs($this->superadmin)
            ->test(ListAiProviders::class)
            ->callAction(TestAction::make('probe')->table($provider))
            ->assertNotified('Gemini · gemini-3.8-flash responde');

        Http::assertSent(fn ($request): bool => $request['generationConfig']['thinkingConfig']['thinkingLevel'] === 'low');
    }

    #[Test]
    public function the_image_model_is_saved_and_probing_images_asks_first_and_generates_one(): void
    {
        $provider = AiProvider::factory()->create();

        Livewire::actingAs($this->superadmin)
            ->test(ListAiProviders::class)
            ->assertActionHidden(TestAction::make('probeImages')->table($provider));

        Livewire::actingAs($this->superadmin)
            ->test(EditAiProvider::class, ['record' => $provider->id])
            ->fillForm(['image_model' => 'gemini-nano-banana-2.1'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('gemini-nano-banana-2.1', $provider->refresh()->image_model);

        $image = imagecreatetruecolor(16, 9);
        ob_start();
        imagepng($image);
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response(['candidates' => [['content' => ['parts' => [['inlineData' => ['mimeType' => 'image/png', 'data' => base64_encode((string) ob_get_clean())]]]]]]])]);

        Livewire::actingAs($this->superadmin)
            ->test(ListAiProviders::class)
            ->assertActionVisible(TestAction::make('probeImages')->table($provider))
            ->mountAction(TestAction::make('probeImages')->table($provider))
            ->assertActionMounted(TestAction::make('probeImages')->table($provider))
            ->callMountedAction()
            ->assertNotified('Gemini · gemini-nano-banana-2.1 genera imágenes');

        Http::assertSent(fn ($request): bool => str_contains($request->url(), '/models/gemini-nano-banana-2.1:generateContent'));
    }
}
