# Contrato API V2 — Hardware (el dispositivo)

> Este archivo documenta **solo el contrato HTTP** de este módulo: rutas, auth,
> parámetros y forma exacta de la respuesta. Está pensado para copiarse a otro
> proyecto (o pegarse en el contexto de una IA) y que con eso baste para
> integrar estos endpoints sin leer el código fuente.
>
> Para el diseño interno (modelos, decisiones de producto) ver
> [`docs/info/hardware.md`](../../hardware.md).

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
  borrado (204) no lleva cuerpo en absoluto. Las colecciones paginadas añaden
  además `meta: {total, per_page, current_page, last_page, from, to}`.
- **Autenticación**: Laravel Sanctum, cabecera `Authorization: Bearer <token>`.
  Este módulo usa dos abilities (catálogo completo en
  `app/Support/Auth/TokenAbilities.php`):
  - `hardware:read` — leer el inventario de dispositivos (`GET /devices*`).
    Es obligatoria a propósito: antes cualquier token (incluido el de una
    estación meteorológica) podía leer el inventario completo de todos los
    usuarios, con número de serie incluido.
  - `hardware:write` — actualizar la salud del aparato (`PUT .../status`). La
    lleva un token de dispositivo emitido con `POST /auth/tokens/devices` (ver
    [`auth.md`](./auth.md)).

  **Este módulo es el aparato y nada más.** Lo que el aparato mide está en el
  contrato de su materia: energía en [`energy.md`](./energy.md), sensores en
  [`weather-station.md`](./weather-station.md), pulsaciones en
  [`keycounter.md`](./keycounter.md), plantas en
  [`smart-plant.md`](./smart-plant.md) y aviones en
  [`airflight.md`](./airflight.md).

  > ⚠️ **Cambio del 2026-09-06.** `POST /hardware/energy-readings` y
  > `POST /hardware/solar-readings` **ya no existen aquí**: son
  > `POST /energy/readings` y `POST /energy/solar-readings`, con ability
  > `energy:write`. Ver [`energy.md`](./energy.md).

  El token puede venir además ligado a un `HardwareDevice` concreto (ability
  `device:{id}`). Cuando lo está, sólo alcanza ese dispositivo: cualquier
  `hardware_device_id` (en el body o en la URL) que no coincida se rechaza. La
  forma en que se rechaza **no es uniforme** — depende de dónde vive la
  comprobación (ver cada endpoint):
  - En `GET /devices/{device}` es un **404** (la política `HardwarePolicy` lo
    trata igual que "no existe": no se confirma la existencia de dispositivos
    ajenos ni de dispositivos fuera de alcance del token).
  - En `PUT /devices/{device}/status` es un **422** de validación (la regla
    `OwnedHardwareDevice` del FormRequest falla sobre el campo
    `hardware_device_id`), no un 403.
- **Ruta inexistente**: cualquier método/URL que no esté documentado responde
  `404` con `{ "success": false, "message": "API V2 - Endpoint no encontrado" }`
  (incluso si el método HTTP es el que no cuadra: el contrato es la pareja
  método+ruta, no hay 405).

---

## Dispositivos (`/hardware/devices`)

### `GET /hardware/devices` — Dispositivos del usuario autenticado

- **Auth**: `auth:sanctum` + `ability:hardware:read`.
- **Query params** (contrato genérico de colecciones,
  `App\Http\Api\CollectionQuery`):

| Parámetro | Tipo | Reglas |
|---|---|---|
| `type` | string | opcional. Uno o varios **slugs** de `hardware_types`, separados por coma: `?type=pc-portatil,micro-pc`. Filtra por `whereHas('type', ...whereIn('slug', ...))` |
| `name` / `created_at` / `last_seen_at` | — | filtrables por igualdad, `campo=a,b,c` (IN) o `campo[gte]=`/`campo[lte]=`/`campo[gt]=`/`campo[lt]=`/`campo[ne]=` |
| `from` / `to` | fecha | alias de `created_at >=` / `created_at <=` (solo si `created_at` está en la lista de filtrables, que lo está) |
| `sort` | string | columnas admitidas: `name`, `created_at`, `last_seen_at`. Por defecto `name` ascendente. Prefijo `-` para descendente, lista separada por comas |
| `page` | int | por defecto `1` |
| `per_page` | int | por defecto `25`, máximo `100` |

  Cualquier parámetro fuera de esta lista se ignora en silencio (no filtra ni
  da error).

  **Los valores de `type` son los `slug` reales sembrados en `hardware_types`**,
  no las etiquetas de `App\Enums\HardwareTypeEnum` (`laptop`, `desktop`...): ese
  enum no se usa en este filtro. Los slugs de serie son:
  `monitor-de-energia`, `controlador-solar`, `pc-portatil`, `pc-desktop`,
  `micro-pc`, `estacion-meteorologica`, `telefono`, `tablet`, `coche`,
  `impresora`, `microcontrolador`.

- **Respuesta 200** (paginada; cada elemento es un `HardwareDeviceResource`):

```json
{
  "success": true,
  "message": "Operación exitosa",
  "data": [
    {
      "id": 7,
      "user_id": 1,
      "name": "Raspberry Pi 4b+",
      "name_friendly": "Raspberry en azotea",
      "type": {
        "id": 5,
        "name": "Micro PC",
        "slug": "micro-pc",
        "description": null,
        "created_at": "2022-01-30T22:54:44.000000Z",
        "updated_at": "2022-01-30T22:54:44.000000Z"
      },
      "brand": "Raspberry Pi Foundation",
      "model": "4B+",
      "description": null,
      "hardware_version": "1.4",
      "software_version": "Raspbian 12",
      "serial_number": "100000001abcdef",
      "created_at": "2025-01-01T00:00:00.000000Z",
      "updated_at": "2026-08-30T10:00:00.000000Z"
    }
  ],
  "meta": {
    "total": 3,
    "per_page": 25,
    "current_page": 1,
    "last_page": 1,
    "from": 1,
    "to": 3
  }
}
```

  `type` sale `null` si el dispositivo no tiene `hardware_type_id` asignado.

- **Errores**: `401` sin token o token inválido, `403` token sin la ability
  `hardware:read`.

---

### `GET /hardware/devices/{device}` — Un dispositivo del usuario autenticado

- **Auth**: `auth:sanctum` + `ability:hardware:read`.
- **Autorización**: además de la ability, `HardwarePolicy::view` exige que el
  dispositivo sea del usuario **y** que el token lo alcance (si está ligado a
  `device:{id}`).
- **Respuesta 200** — mismo `HardwareDeviceResource` que en la colección:

```json
{
  "success": true,
  "message": "Operación exitosa",
  "data": {
    "id": 7,
    "user_id": 1,
    "name": "Raspberry Pi 4b+",
    "name_friendly": "Raspberry en azotea",
    "type": {
      "id": 5,
      "name": "Micro PC",
      "slug": "micro-pc",
      "description": null,
      "created_at": "2022-01-30T22:54:44.000000Z",
      "updated_at": "2022-01-30T22:54:44.000000Z"
    },
    "brand": "Raspberry Pi Foundation",
    "model": "4B+",
    "description": null,
    "hardware_version": "1.4",
    "software_version": "Raspbian 12",
    "serial_number": "100000001abcdef",
    "created_at": "2025-01-01T00:00:00.000000Z",
    "updated_at": "2026-08-30T10:00:00.000000Z"
  }
}
```

- **Errores**: `404` tanto si el dispositivo no existe, como si es de otro
  usuario, como si es de este usuario pero el token está ligado a otro
  dispositivo (`device:{id}` distinto) — las tres situaciones dan el mismo
  mensaje "Dispositivo no encontrado" a propósito: no se confirma la
  existencia de dispositivos fuera de alcance. `401`/`403` como en el índice.

---

### `PUT /hardware/devices/{device}/status` — Sustituye el último estado conocido

Es "el último estado conocido": no hay histórico, cada petición **sobrescribe**
el estado anterior. Por eso es `PUT` y no `POST`: repetir la misma petición dos
veces deja el sistema igual (idempotente).

- **Auth**: `auth:sanctum` + `ability:hardware:write`.
- **Rate limit**: `api-store` — 60 peticiones/min por token.
- **Body** — los campos de estado se aceptan sueltos en la raíz, o agrupados
  dentro de `hardware_device_info` (para subidas conjuntas junto a otros
  datos; se aplanan a la raíz antes de validar, y lo que venga ya en la raíz
  gana sobre lo agrupado):

| Campo | Tipo | Reglas |
|---|---|---|
| `temp` | number\|null | opcional |
| `voltage` | number\|null | opcional |
| `battery_level` | int\|null | opcional, entre 0 y 100 |
| `cpu` | number\|null | opcional, entre 0 y 100 |
| `disk` | number\|null | opcional, entre 0 y 100 |
| `ram` | number\|null | opcional, entre 0 y 100. **Nuevo el 2026-09-06** |
| `uptime` | int\|null | opcional, mín. 0 |
| `ip_local` | string\|null | opcional, IP válida |
| ~~`ip_public`~~ | — | **Ya no se acepta (2026-09-06).** Si se manda, se ignora: la pone el servidor. Ver el aviso de abajo |
| `extra` | object\|null | opcional, máx. 30 claves; cada valor debe ser un dato simple (número, texto o booleano), texto máx. 255 caracteres |

> ### ⚠️ Cambio de contrato — `ip_public` (2026-09-06)
>
> **El dispositivo ya no manda su IP pública.** Si va en el cuerpo se descarta
> en silencio; el servidor la resuelve desde la propia petición y sobreescribe
> siempre lo que llegue.
>
> El cacharro conoce su IP de la intranet y la sigue mandando en `ip_local`. La
> pública no la sabe de forma fiable —tendría que preguntársela a un servicio
> externo en cada envío— y, si la manda, no hay manera de comprobar que dice la
> verdad.
>
> La resuelve `App\Support\Http\ClientIp` leyendo la cabecera que escribe el
> proxy, en este orden: `CF-Connecting-IP`, `True-Client-IP`, `X-Forwarded-For`
> (la primera de la lista, que es el cliente original) y `X-Real-IP`. Descarta
> privadas y reservadas. Si no puede determinar ninguna pública —desarrollo, o
> una NAT sin proxy delante— guarda `null`, en vez de meter una privada en una
> columna que dice «pública».
>
> **Qué tiene que hacer el cliente:** quitar `ip_public` del cuerpo. Dejarlo no
> rompe la petición —la validación ya no lo contempla y se ignora—, pero el
> valor que se mande no se guarda. En la **respuesta** el campo sigue existiendo,
> con la IP que ha resuelto el servidor.

> ### `ram` (2026-09-06)
>
> Uso de memoria en porcentaje (0-100), igual que `cpu` y `disk`. Antes sólo
> cabía dentro de `extra`, que es JSON y no se puede ordenar ni graficar.
> Columna `hardware_devices.ram`, migración
> `2026_09_06_000001_add_ram_to_hardware_devices_table`.

  `hardware_device_id` **no** se acepta como campo: viene de la URL
  (`{device}`) y ahí es donde se comprueba la pertenencia/ligado al token
  (regla `OwnedHardwareDevice`). No se puede sobreescribir metiéndolo dentro de
  `hardware_device_info`: esa clave está en una lista blanca de campos de
  estado y `hardware_device_id` no es uno de ellos.

- **Respuesta 200** (`DeviceStatusResource`):

```json
{
  "success": true,
  "message": "Estado del dispositivo actualizado",
  "data": {
    "hardware_device_id": 7,
    "temp": 42.5,
    "voltage": 5.05,
    "battery_level": 78,
    "cpu": 12.3,
    "disk": 44.1,
    "ram": 62.5,
    "uptime": 86400,
    "ip_local": "192.168.1.50",
    "extra": { "wifi_rssi": -61 },
    "last_seen_at": "2026-08-30T10:00:00.000000Z"
  }
}
```

  `last_seen_at` se fija siempre al momento de la petición. Solo se
  sobrescriben las claves presentes en el body: un campo ausente no borra el
  valor previo (no se manda `null` a la fuerza).

- **Errores**:
  - `401` sin token o token inválido.
  - `403` token sin la ability `energy:write`.
  - `422` validación de los campos de estado; `422` también si
    `hardware_device_id` (el de la URL) no existe en `hardware_devices`, no
    pertenece al usuario del token, o no coincide con el dispositivo al que el
    token está ligado (`device:{id}`) — este último caso **no** es un 403 pese
    a ser un problema de alcance del token, porque la comprobación vive en la
    validación del FormRequest.
  - `429` al superar 60/min con ese token.

---

## Lo que ya no existe, y por qué

| Ruta antigua | Qué pasó |
|---|---|
| `GET /hardware/computers` | Retirada: era una ruta propia para lo que ahora es un filtro de la colección, `GET /hardware/devices?type=<slug>` |
| `POST /hardware/device-status` | Retirada: subía el estado como si fuera un recurso nuevo cada vez. Es "el último estado conocido" —se sobrescribe—, así que ahora es `PUT /hardware/devices/{device}/status`: repetir la petición deja el sistema igual (idempotente) |
| `GET /hardware/device/{id}` (sin filtrar por dueño, sin ability) | Ahora es `GET /hardware/devices/{device}`, detrás de `ability:hardware:read` y acotado siempre al usuario del token. Antes, iterando el id, se podía leer el inventario completo de todos los usuarios con número de serie incluido |

---

> Creado: 2026-08-30 · Última revisión: 2026-09-06
