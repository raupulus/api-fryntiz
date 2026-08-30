---
name: api-rest-v2
description: >-
  Diseño e implementación de la API REST V2 de Api Raupulus (única versión; la V1
  fue eliminada). Cárgala SIEMPRE que crees o modifiques endpoints: controladores
  en app/Http/Controllers/Api/<Modulo>/V2/, JsonResources en
  app/Http/Resources/V2/, FormRequests de API en app/Http/Requests/Api/, rutas en
  routes/<modulo>/v2.php o routes/api/v2.php, autenticación Sanctum, tokens IoT
  por dispositivo con abilities/scopes, throttling, o el formato JSON de
  respuesta {success, message, data}. Úsala en cuanto el trabajo implique
  "endpoint", "API", "JSON response", "recurso REST", "token de dispositivo" o
  "scope", aunque no se mencione "V2". Para la lógica de negocio interna usa
  laravel-backend; para el panel admin usa filament-admin.
---

# API REST V2 — Api Raupulus

API **única V2**, prefijo `/api/v2`, respuestas JSON con **JsonResources**.
Todos los endpoints siguen el mismo contrato de respuesta y la misma cadena de
responsabilidades: **ruta → FormRequest (validación) → Controller (fino) →
Service (lógica) → Resource (serialización)**.

## Contrato de respuesta — innegociable

Toda respuesta usa este envelope, producido por `ApiResponseTrait`
(`app/Traits/ApiResponseTrait.php`):

```json
// Éxito
{ "success": true, "message": "Operacion exitosa", "data": { ... } }
// Error
{ "success": false, "message": "Recurso no encontrado", "errors": { ... } }
```

`errors` solo aparece cuando hay detalle (validación). Los errores de validación
y autorización se transforman a este formato **globalmente** en
`bootstrap/app.php` (`JsonValidationException`, `JsonAuthorizationException`): no
los formatees a mano en el controlador.

## Controlador

Hereda de `app/Http/Controllers/Api/V2/BaseApiController.php` (que ya incluye
`ApiResponseTrait`). El controlador es **fino**: valida vía FormRequest, delega
en un Service, y devuelve un Resource envuelto en un método de respuesta. Nunca
metas lógica de negocio aquí.

```php
class ContentController extends BaseApiController
{
    public function __construct(private ContentService $service) {}

    public function show(string $platformSlug, string $contentSlug): JsonResponse
    {
        $content = $this->service->getBySlug($platformSlug, $contentSlug);

        if (! $content) {
            return $this->notFoundResponse('Contenido no encontrado');
        }

        return $this->successResponse(new ContentResource($content));
    }
}
```

Métodos de respuesta disponibles (úsalos, no `response()->json()` a mano):

`successResponse($data, $message, $status=200)`,
`createdResponse($data, $message, $location=null)` (201, añade cabecera
`Location` si se indica),
`paginatedResponse($paginator, $resourceClass=null, $message)` — colecciones
paginadas: envuelve cada elemento con el Resource indicado y añade el bloque
`meta` (`total`, `per_page`, `current_page`, `last_page`, `from`, `to`). **Toda
colección de la API va paginada con este método**, no con un `Resource::collection()`
suelto.
`deletedResponse()` (204, sin cuerpo — para borrados),
`errorResponse($message, $status=400, $errors=[])`,
`notFoundResponse()` (404), `unauthorizedResponse()` (401),
`forbiddenResponse()` (403), `conflictResponse()` (409),
`withWarnings($jsonResponse, $warnings)` — añade un array `warnings` a una
respuesta ya construida cuando la petición se guarda pero hay algo que avisar
(no es un error).

## FormRequests

En `app/Http/Requests/Api/<Modulo>/V2/`. Validación con **mensajes en español**.
Normaliza datos en `prepareForValidation()` cuando haga falta (casts de
booleanos, trims, etc.). La validación devuelve el envelope de error
automáticamente, así que el controlador no comprueba `$validator->fails()`.

## Resources

En `app/Http/Resources/V2/<Modulo>/`. Mapea explícitamente los campos (no
vuelques el modelo entero). Formatea fechas a ISO 8601 con
`?->toISOString()`. Para colecciones usa `Resource::collection($items)`.

```php
class SolarChargeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'device_id' => $this->device_id,
            'battery_voltage' => $this->battery_voltage,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
```

## Rutas

Cada módulo tiene su archivo: `routes/<modulo>/v2.php`
(`hardware`, `keycounter`, `smart_plant`, `weather_station`, `airflight`, `cv`)
y el agregador `routes/api/v2.php`. Mantén ese reparto por módulo; no metas todo
en un único archivo.

## Autenticación y autorización

- **Usuarios:** Sanctum (`auth:sanctum`).
- **Dispositivos IoT:** token Sanctum **por dispositivo** con **abilities/scopes**
  por módulo: `weatherstation:write`, `hardware:write`, `keycounter:write`,
  `smartplant:write`, `airflight:write`, y expiración.
  Emisión por consola: `php artisan iot:device-token <id> --abilities=... [--expires=días]`.
  Emisión por API (el propio usuario, autenticado con `ability:session`):
  `POST /auth/tokens/devices` — ver `docs/info/api/v2/auth.md`.
- **Endpoints de escritura IoT:** protégelos con la cadena
  `auth:sanctum` + `ability:<scope>` + `throttle:api-store`.
- Aliases de middleware vigentes (`bootstrap/app.php`): solo `ability` y
  `abilities` (`CheckForAnyAbility`/`CheckAbilities`). El CORS global lo lleva
  `HandleCors` con `config/cors.php` (aplicado por `prepend`, no por alias).
  **No existen** `cors`, `cors.allow.all`, `check.domain` ni
  `ip.counter.strict`: se retiraron a propósito por ser código roto (CORS
  reflejaba cualquier origen, `check.domain` era código muerto, y
  `ip.counter.strict` no cerraba nunca su ventana). No los reintroduzcas ni los
  uses como middleware de una ruta nueva.

## Checklist al crear/editar un endpoint

1. Ruta en `routes/<modulo>/v2.php` con middleware correcto.
2. FormRequest (si hay entrada) con mensajes en español.
3. Controlador fino que hereda de `BaseApiController` y delega en Service.
4. Resource V2 para la salida.
5. Respuesta con el envelope vía `ApiResponseTrait`.
6. Test en `tests/Feature/Api/V2/<Modulo>Test.php` (extiende `ApiTestCase`,
   usa `RefreshDatabase`).
7. Actualiza `docs/info/<modulo>.md` (documentación general del módulo) y el
   contrato de endpoints en `docs/info/api/v2/<modulo>.md` (formato
   copiable: método+ruta, auth, rate limit, parámetros, respuesta de éxito y
   errores — usa `docs/info/api/v2/auth.md` como plantilla de formato).
