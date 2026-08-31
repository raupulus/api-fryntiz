<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V2;

use App\Support\Auth\TokenAbilities;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Api\ApiTestCase;

/**
 * `/api/v2/user` — sólo queda `GET /user/me`.
 *
 * La gestión de usuarios es exclusiva del panel de Filament. Los tests de
 * `index`, `store`, `show({user})`, `update` y `destroy` se han quitado porque
 * esas rutas ya no existen: `show({user})` no comprobaba nada y dejaba
 * enumerar usuarios con cualquier token (auditoría A4).
 */
class UserTest extends ApiTestCase
{
    protected string $apiPrefix = 'api/v2';

    #[Test]
    public function returns_the_data_of_the_token_owner(): void
    {
        $user = $this->createAuthenticatedUser();

        $response = $this->getJson($this->apiUrl('users/me'), $this->authenticatedHeaders($user));

        $this->assertSuccessResponse($response);
        $response->assertJsonPath('data.id', $user->id);
        $response->assertJsonPath('data.email', $user->email);
        $response->assertJsonPath('data.role', 'user');
    }

    #[Test]
    public function without_a_token_it_returns_nothing(): void
    {
        $response = $this->getJson($this->apiUrl('users/me'), $this->guestHeaders());

        $this->assertErrorResponse($response, 401);
    }

    #[Test]
    public function a_device_token_does_not_reach_user_data(): void
    {
        $user = $this->createAuthenticatedUser();
        $headers = $this->moduleHeaders($user, TokenAbilities::WEATHERSTATION_WRITE);

        $response = $this->getJson($this->apiUrl('users/me'), $headers);

        $response->assertStatus(403);
    }

    #[Test]
    public function the_user_management_routes_no_longer_exist(): void
    {
        $headers = $this->asSuperAdmin();

        $this->getJson($this->apiUrl('users'), $headers)->assertStatus(404);
        $this->getJson($this->apiUrl('users/1'), $headers)->assertStatus(404);
        $this->putJson($this->apiUrl('users/1'), [], $headers)->assertStatus(404);
        $this->deleteJson($this->apiUrl('users/1'), [], $headers)->assertStatus(404);
        // Y la forma anterior en singular tampoco existe.
        $this->getJson($this->apiUrl('user'), $headers)->assertStatus(404);
    }
}
