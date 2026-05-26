<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Tests\Traits\AuthenticatesForApi;

abstract class ApiTestCase extends TestCase
{
    use RefreshDatabase, AuthenticatesForApi;

    /**
     * Prefijo base de la API. Las subclases lo definen para su módulo.
     */
    protected string $apiPrefix = '';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
        $this->seedLanguages();
        $this->seedContentStatuses();
    }

    private function seedContentStatuses(): void
    {
        if (DB::table('content_available_status')->count() === 0) {
            (new \Database\Seeders\ContentAvailableStatusSeeder())->run();
        }
    }

    /**
     * Construye la URL completa de la API.
     */
    protected function apiUrl(string $path): string
    {
        return '/' . ltrim($this->apiPrefix, '/') . '/' . ltrim($path, '/');
    }

    /**
     * Verifica estructura base de respuesta exitosa.
     */
    protected function assertSuccessResponse($response, int $status = 200): void
    {
        $response->assertStatus($status)
            ->assertJsonStructure(['success', 'message'])
            ->assertJson(['success' => true]);
    }

    /**
     * Verifica estructura de respuesta de error.
     */
    protected function assertErrorResponse($response, int $status): void
    {
        $response->assertStatus($status)
            ->assertJsonStructure(['success', 'message'])
            ->assertJson(['success' => false]);
    }

    /**
     * Verifica estructura de respuesta paginada.
     */
    protected function assertPaginatedResponse($response): void
    {
        $response->assertStatus(200)
            ->assertJsonStructure(['data', 'links', 'meta']);
    }

    /**
     * Crea los roles básicos si no existen (necesarios para FK user_roles).
     */
    private function seedRoles(): void
    {
        if (DB::table('user_roles')->count() === 0) {
            DB::table('user_roles')->insert([
                ['id' => 1, 'name' => 'superadmin', 'display_name' => 'Super Admin', 'slug' => 'super-admin', 'description' => 'Administrador Principal', 'created_at' => now(), 'updated_at' => now()],
                ['id' => 2, 'name' => 'admin', 'display_name' => 'Admin', 'slug' => 'admin', 'description' => 'Administradores', 'created_at' => now(), 'updated_at' => now()],
                ['id' => 3, 'name' => 'user', 'display_name' => 'Usuario', 'slug' => 'usuario', 'description' => 'Usuario normal', 'created_at' => now(), 'updated_at' => now()],
            ]);
        }
    }

    private function seedLanguages(): void
    {
        if (DB::table('languages')->count() === 0) {
            DB::table('languages')->insert([
                'id' => 1,
                'locale' => 'es_ES',
                'iso_locale' => 'es-ES',
                'iso2' => 'es',
                'iso3' => 'esp',
                'name' => 'Español',
                'iso_language' => 'Spanish',
                'created_at' => now(),
            ]);
        }
    }
}
