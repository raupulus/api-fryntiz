# Contrato API V2 — Autenticación y usuario (Auth)

> Este archivo documenta **solo el contrato HTTP** de este módulo: rutas, auth,
> parámetros y forma exacta de la respuesta. Está pensado para copiarse a otro
> proyecto (o pegarse en el contexto de una IA) y que con eso baste para
> integrar estos endpoints sin leer el código fuente.
>
> Para el diseño interno (modelos, roles, Policies, decisiones de producto) ver
> [`docs/info/auth.md`](../../auth.md).

## Base y convenciones comunes a toda la API V2

- **Base URL**: `/api/v2`
- **Todas las respuestas** usan este envelope (`App\Traits\ApiResponseTrait`):

  ```json
  // Éxito
  { "success": true, "message": "Operación exitosa", "data": { ... } }
  // Error
  { "success": false, "message": "Descripción del error", "errors": { "campo": ["detalle"] } }
  ```

  `errors` solo aparece si hay detalle (p. ej. errores de validación 422). Un
  borrado (204) no lleva cuerpo en absoluto.
- **Autenticación**: Laravel Sanctum, cabecera `Authorization: Bearer <token>`.
  Hay dos familias de token que nunca se mezclan:
  - **Token de sesión** (ability `session`): lo emite este módulo. Es el que
    usa una persona desde una app/web propia.
  - **Token de dispositivo IoT** (abilities de módulo: `energy:write`,
    `weatherstation:write`, etc. + `device:{id}`, [catálogo completo
    abajo](#catálogo-de-abilities-de-módulo)): se emite desde aquí mismo
    (`POST /auth/tokens/devices`) pero se usa contra los demás módulos, nunca
    contra las rutas de este archivo salvo que se indique.
- **Ruta inexistente**: cualquier método/URL que no esté documentado responde
  `404` con `{ "success": false, "message": "API V2 - Endpoint no encontrado" }`
  (incluso si el método HTTP es el que no cuadra: el contrato es la pareja
  método+ruta, no hay 405).

---

## Tokens (`/auth/tokens`)

El token es el recurso; no hay verbos sueltos (`login`/`logout`).

### `POST /auth/tokens` — Crear un token de sesión (login)

- **Auth**: no requiere autenticación previa.
- **Rate limit**: `api-auth` — 10 peticiones/min por IP **y** 10/min por email (ambas cuentan a la vez).
- **Body**:

| Campo | Tipo | Reglas |
|---|---|---|
| `email` | string | `required`, formato email, máx. 255 |
| `password` | string | `required`, mín. 6 |

- **Respuesta 201** (cabecera `Location` → `GET /auth/tokens`):

```json
{
  "success": true,
  "message": "Token creado correctamente",
  "data": {
    "token": "1|abcdef...",
    "expires_at": "2026-09-29T12:00:00.000000Z",
    "abilities": ["session"],
    "user": {
      "id": 1,
      "name": "Raúl",
      "nickname": "raupulus",
      "email": "raupulus@example.com",
      "role": "SuperAdmin",
      "email_verified": true,
      "is_active": true,
      "created_at": "2025-01-01T00:00:00.000000Z",
      "updated_at": "2026-01-01T00:00:00.000000Z"
    }
  }
}
```

  `token` solo se ve en esta respuesta; no se puede recuperar después.
  `expires_at` sale de `auth.api_session_days` (30 días por defecto).

- **Errores**: `401` credenciales inválidas (mismo mensaje si el email no
  existe o si la contraseña falla — no es un oráculo de cuentas), `403` cuenta
  desactivada (`is_active = false`), `422` validación.

### `GET /auth/tokens` — Listar mis tokens

- **Auth**: `auth:sanctum` + `ability:session` + `throttle:api`.
- **Respuesta 200** — **paginada** (25 por página, orden descendente por `created_at`). Antes
  devolvía la lista entera con un `->get()`: con un token por cacharro y varios años acumulando,
  viajaba todo en cada consulta.

```json
{
  "success": true,
  "message": "Operación exitosa",
  "data": [
    {
      "id": 3,
      "name": "api-session",
      "abilities": ["session"],
      "device_ids": [],
      "is_device_token": false,
      "last_used_at": "2026-08-29T10:00:00.000000Z",
      "expires_at": "2026-09-29T12:00:00.000000Z",
      "is_expired": false,
      "created_at": "2026-08-30T12:00:00.000000Z"
    }
  ],
  "meta": {
    "total": 1,
    "per_page": 25,
    "current_page": 1,
    "last_page": 1,
    "from": 1,
    "to": 1
  }
}
```

  Admite los parámetros de colección de la V2: `?page=`, `?per_page=` (máximo 100),
  `?sort=-created_at`, y filtros por `name`, `created_at` y `last_used_at`.

  **Nunca incluye el token en claro**, tampoco en el listado. `device_ids` sólo tiene contenido en
  tokens de dispositivo (ver abajo).

### `POST /auth/tokens/devices` — Emitir un token de dispositivo IoT

- **Auth**: `auth:sanctum` + `ability:session` (lo emite una persona para su
  propio dispositivo, no un dispositivo para sí mismo).
- **Autorización**: `Policy::view` sobre el `HardwareDevice` indicado (tiene
  que ser tuyo).
- **Body**:

| Campo | Tipo | Reglas |
|---|---|---|
| `device_id` | int | `required`, debe existir en `hardware_devices` |
| `abilities` | array\<string\> | `required`, mínimo 1, cada valor debe estar en el catálogo de abilities de módulo (ver la tabla de abajo) |
| `name` | string\|null | opcional, máx. 255 |
| `expires_at` | datetime\|null | opcional, debe ser una fecha futura. **Por defecto los tokens de dispositivo no caducan** (a propósito: son cacharros a los que no se sube a reflashear un token) |

- **Respuesta 201**:

```json
{
  "success": true,
  "message": "Token de dispositivo emitido. Cópialo ahora: no se vuelve a mostrar.",
  "data": {
    "token": "7|xyz...",
    "device_token": {
      "id": 7,
      "name": "estacion-3",
      "abilities": ["weatherstation:write", "device:3"],
      "device_ids": [3],
      "is_device_token": true,
      "last_used_at": null,
      "expires_at": null,
      "is_expired": false,
      "created_at": "2026-08-30T12:00:00.000000Z"
    }
  }
}
```

- **Errores**: `403` si el dispositivo no es tuyo, `404` si `device_id` no
  existe, `422` si alguna ability no está en el catálogo (nunca se puede pedir
  el comodín `*` ni `session`).

#### Catálogo de abilities de módulo

Es el de `App\Support\Auth\TokenAbilities::MODULE_ABILITIES`. **Ninguna está
de adorno: cada una la exige alguna ruta.**

| Ability | Qué abre |
|---|---|
| `hardware:read` | Listar dispositivos y ver su ficha (`GET /hardware/devices*`) |
| `hardware:write` | Último estado conocido del aparato (`PUT /hardware/devices/{id}/status`) |
| `energy:read` | Consultar lecturas de energía y solares (`GET /energy/readings`, `GET /energy/solar-readings`) |
| `energy:write` | Subirlas (`POST` de esas dos rutas). Es la de un controlador solar o un contador de consumo |
| `weatherstation:read` | Estaciones y el histórico de sus sensores (`GET /weather-stations*`) |
| `weatherstation:write` | Subir lecturas de sensores (`POST /weather-stations/{id}/*`) |
| `keycounter:read` | Sesiones de teclado y ratón (`GET /keycounter/*`) |
| `keycounter:write` | Subirlas (`POST /keycounter/*`) |
| `smartplant:read` | Plantas y sus lecturas (`GET /smartplant/*`) |
| `smartplant:write` | Subir lecturas de una planta (`POST /smartplant/plants/{id}/readings`) |
| `airflight:read` | Aviones detectados, con historial por fechas (`GET /airflight/*`) |
| `airflight:write` | Registrar aviones (`POST /airflight/aircrafts*`) |

Cambios del **2026-09-06**:

- **Energía se separa de Hardware.** `energy:read/write` son nuevas;
  antes las lecturas de energía y solares iban con `hardware:*`. A los tokens ya
  emitidos se les añadió la nueva en el mismo despliegue, así que ningún
  cacharro dejó de subir. Los tokens nuevos de un aparato de energía llevan
  `energy:write` y no necesitan `hardware:write`.
- **`weatherstation:read` y `airflight:read` empiezan a mandar.** Las lecturas
  de esos dos módulos eran públicas y esas dos abilities no protegían nada.
  Ahora exigen token; lo que consume la web propia se sirve desde el bloque web
  de la aplicación, sin token (ver [`weather-station.md`](./weather-station.md)
  y [`airflight.md`](./airflight.md)).

### `DELETE /auth/tokens/current` — Cerrar la sesión actual (logout)

- **Auth**: `auth:sanctum` + `ability:session`.
- **Respuesta**: `204` sin cuerpo. Si la petición llegó por cookie de sesión
  (no por Bearer token) no hay nada que borrar y también responde `204`.

### `DELETE /auth/tokens/{token}` — Revocar otro token propio

- **Auth**: `auth:sanctum` + `ability:session`. `{token}` es el `id` numérico
  del token (el de la lista de `GET /auth/tokens`), no el valor en claro.
- **Respuesta**: `204` sin cuerpo.
- **Errores**: `404` tanto si el token no existe como si es de otro usuario
  (nunca se confirma la existencia de tokens ajenos).

---

## Usuario (`/users`)

### `GET /users/me` — Mis datos

- **Auth**: `auth:sanctum` + `ability:session`.
- **Respuesta 200**:

```json
{
  "id": 1,
  "name": "Raúl",
  "nickname": "raupulus",
  "email": "raupulus@example.com",
  "role": "SuperAdmin",
  "email_verified": true,
  "is_active": true,
  "created_at": "2025-01-01T00:00:00.000000Z",
  "updated_at": "2026-01-01T00:00:00.000000Z"
}
```

  `email` solo aparece si el que pregunta es el propio usuario o un admin;
  para cualquier otro visor se omite el campo. `role` solo sale si la relación
  está cargada (siempre lo está en este endpoint).

No hay `GET /users`, `GET /users/{id}`, `PUT /users/{id}` ni `DELETE
/users/{id}`: se retiraron porque el `show({id})` original no comprobaba nada
y cualquier token (incluido el de un sensor) podía enumerar usuarios. La
gestión de cuentas de otros usuarios se hace **solo** desde el panel Filament.

---

## Lo que existió y ya no tiene ruta (no lo reimplementes igual)

| Ruta antigua | Qué pasó |
|---|---|
| `POST /auth/login` | Es `POST /auth/tokens` |
| `POST /auth/logout` | Es `DELETE /auth/tokens/current` |
| `POST /auth/signup` | Retirada: el alta de usuarios se hace desde Filament |
| `POST /auth/delete-account` | Retirada: borraba la cuenta y **todos** los tokens sin pedir contraseña (dejaba fuera al dueño de cualquier cacharro). El código sigue en `RegisterController` pero sin ruta activa |
| `GET/PUT/DELETE /user/{id}` | Retiradas: `GET` no comprobaba nada y permitía enumerar usuarios |

---

> Creado: 2026-08-30 · Última revisión: 2026-09-06
