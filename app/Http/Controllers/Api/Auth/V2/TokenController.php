<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Auth\V2;

use App\Http\Api\CollectionQuery;
use App\Http\Controllers\Api\V2\BaseApiController;
use App\Http\Requests\Api\Auth\V2\IssueDeviceTokenRequest;
use App\Http\Requests\Api\Auth\V2\LoginRequest;
use App\Http\Resources\V2\ApiTokenResource;
use App\Http\Resources\V2\UserResource;
use App\Models\Hardware\HardwareDevice;
use App\Models\User;
use App\Services\Hardware\DeviceTokenService;
use App\Support\Auth\TokenAbilities;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * Los tokens de la API como recurso REST.
 *
 * `POST /auth/login` era un verbo colgado de la URL. Modelar el token como
 * recurso no es purismo: es justo lo que hace falta para el panel de usuario
 * (D90, «un panel para usuarios que solo pueden editar sus datos personales y
 * tokens de hardware»). Con `login` como verbo, listar y revocar tokens hay que
 * inventarlo aparte; con `/auth/tokens` como colección salen de los métodos
 * HTTP de siempre.
 *
 *   POST   /auth/tokens              crear un token → 201 (sin autenticar: es el login)
 *   GET    /auth/tokens              mis tokens
 *   POST   /auth/tokens  (con abilities[] y device_id)  emitir token de dispositivo
 *   DELETE /auth/tokens/current      cerrar la sesión en curso
 *   DELETE /auth/tokens/{token}      revocar uno concreto
 */
class TokenController extends BaseApiController
{
    public function __construct(private readonly DeviceTokenService $deviceTokens) {}

    /**
     * Crea un token de sesión a partir de las credenciales.
     *
     * Es lo que antes era `POST /auth/login`. Devuelve 201 porque crea un
     * recurso, y la cabecera `Location` apunta a la colección de tokens.
     */
    public function store(LoginRequest $request): JsonResponse
    {
        $user = User::query()->where('email', $request->validated('email'))->first();

        // Mismo mensaje y mismo código tanto si el email no existe como si la
        // contraseña falla: no es un oráculo de cuentas registradas.
        if (! $user || ! Hash::check($request->validated('password'), $user->password)) {
            return $this->errorResponse('Credenciales inválidas', 401);
        }

        if (! $user->is_active) {
            return $this->errorResponse('La cuenta está desactivada', 403);
        }

        $token = $user->createToken(
            'api-session',
            TokenAbilities::forSession(),
            now()->addDays((int) config('auth.api_session_days', 30))
        );

        return $this->createdResponse(
            [
                'token' => $token->plainTextToken,
                'expires_at' => $token->accessToken->expires_at?->toISOString(),
                'abilities' => TokenAbilities::forSession(),
                'user' => new UserResource($user->loadMissing('role')),
            ],
            'Token creado correctamente',
            route('api.v2.auth.tokens.index')
        );
    }

    /**
     * Lista los tokens del usuario autenticado.
     *
     * Nunca devuelve el token en claro: sólo existe en el momento de crearlo.
     */
    public function index(Request $request): JsonResponse
    {
        // API-03: esto hacía `->get()` sobre todos los tokens del usuario. Con
        // un cacharro por token y varios años de trastear, la lista completa
        // viajaba en cada consulta. Va paginada como el resto de colecciones de
        // la V2, con los 25 por página y el orden descendente por fecha de
        // creación que CollectionQuery ya trae por defecto.
        $collectionQuery = new CollectionQuery(
            filterable: ['name', 'created_at', 'last_used_at'],
            sortable: ['name', 'created_at', 'last_used_at'],
        );

        return $this->paginatedResponse(
            $collectionQuery->paginate($request->user()->tokens()->getQuery(), $request),
            ApiTokenResource::class
        );
    }

    /**
     * Emite un token ligado a un dispositivo IoT.
     *
     * Va por `DeviceTokenService`, que es la fuente única: comprueba que las
     * abilities están en el catálogo, que el dispositivo es del usuario, y
     * añade `device:{id}`. No emite nunca el comodín ni la ability de sesión.
     */
    public function storeDeviceToken(IssueDeviceTokenRequest $request): JsonResponse
    {
        $device = HardwareDevice::query()->findOrFail($request->validated('device_id'));

        $this->authorize('view', $device);

        $token = $this->deviceTokens->issue(
            $device,
            $request->validated('abilities'),
            $request->expiresAt(),
        );

        if ($request->filled('name')) {
            $token->accessToken->forceFill(['name' => $request->validated('name')])->save();
        }

        return $this->createdResponse(
            [
                // Es la única vez que se ve. A partir de aquí sólo queda el hash.
                'token' => $token->plainTextToken,
                'device_token' => new ApiTokenResource($token->accessToken),
            ],
            'Token de dispositivo emitido. Cópialo ahora: no se vuelve a mostrar.',
            route('api.v2.auth.tokens.index')
        );
    }

    /**
     * Revoca el token con el que se ha hecho la petición.
     *
     * Es el antiguo `POST /auth/logout`.
     */
    public function destroyCurrent(Request $request): JsonResponse
    {
        $token = $request->user()?->currentAccessToken();

        // Con `statefulApi()` una petición por cookie devuelve un TransientToken,
        // que no se puede borrar. Sólo se borra un token real.
        if ($token instanceof PersonalAccessToken) {
            $token->delete();
        }

        return $this->deletedResponse();
    }

    /**
     * Revoca un token concreto del usuario autenticado.
     */
    public function destroy(Request $request, int $token): JsonResponse
    {
        $encontrado = $request->user()->tokens()->whereKey($token)->first();

        // Mismo 404 si no existe que si es de otro: no se confirma la
        // existencia de tokens ajenos.
        if (! $encontrado) {
            return $this->notFoundResponse('Token no encontrado');
        }

        $encontrado->delete();

        return $this->deletedResponse();
    }
}
