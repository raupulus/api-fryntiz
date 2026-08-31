# Contrato API V2 — Plantas inteligentes (Smart Plant)

> Este archivo documenta **solo el contrato HTTP** de este módulo: rutas, auth,
> parámetros y forma exacta de la respuesta. Está pensado para copiarse a otro
> proyecto (o pegarse en el contexto de una IA) y que con eso baste para
> integrar estos endpoints sin leer el código fuente.
>
> Para el diseño interno (modelos, servicio, decisiones de producto) ver
> [`docs/info/smart-plant.md`](../../smart-plant.md).

## Base y convenciones comunes a toda la API V2

- **Base URL**: `/api/v2`
- **Todas las respuestas** usan este envelope (`App\Traits\ApiResponseTrait`):

  ```json
  // Éxito
  { "success": true, "message": "Operación exitosa", "data": { ... } }
  // Error
  { "success": false, "message": "Descripción del error", "errors": { "campo": ["detalle"] } }
  ```

  `errors` solo aparece si hay detalle (p. ej. errores de validación 422).
- **Autenticación**: Laravel Sanctum, cabecera `Authorization: Bearer <token>`.
  Todos los endpoints de este módulo requieren un **token de dispositivo IoT**
  con la ability `smartplant:write` (catálogo completo en
  `app/Support/Auth/TokenAbilities.php`; se emite con `POST
  /auth/tokens/devices`, ver [`auth.md`](./auth.md)). No hay ability de solo
  lectura separada: el mismo token que escribe lecturas es el que puede listar
  plantas y consultarlas.
- **Ruta inexistente**: cualquier método/URL que no esté documentado responde
  `404` con `{ "success": false, "message": "API V2 - Endpoint no encontrado" }`
  (incluso si el método HTTP es el que no cuadra: el contrato es la pareja
  método+ruta, no hay 405). Esto incluye `{plant}` no numérico: la ruta exige
  `whereNumber('plant')`, así que `GET /smartplant/plants/abc/readings` cae en
  este mismo 404 genérico, no en un 422.

## Nota de diseño importante: de quién es una lectura

La tabla `smartplant_registers` (las lecturas de sensores) **no tiene columna
`user_id`**. La planta (`plant_id`) es el único sitio donde consta de quién es
una lectura. Por eso:

- La planta va **en la URL** (`/plants/{plant}/readings`), nunca como
  `plant_id` suelto en el cuerpo.
- La pertenencia se comprueba dos veces, con comportamientos distintos según el
  verbo:
  - **`GET /plants/{plant}/readings`**: el controlador busca la planta con
    `SmartPlantPlant::find($plant)` y comprueba `$request->user()->cannot('view', $planta)`
    (Policy `SmartPlantPolicy::view` → `isOwnedBy`). Si la planta no existe **o**
    no es tuya, responde **`404` "Planta no encontrada"** en ambos casos (no se
    revela si la planta existe pero es de otro).
  - **`POST /plants/{plant}/readings`**: el `plant_id` (tomado de la URL, no del
    body) se valida como un campo más en `StoreRegisterRequest` con las reglas
    `exists:smartplant_plants,id` + la regla custom `OwnedSmartPlant`. Si la
    planta no existe, no es tuya, o (siendo tuya) cuelga de un
    `hardware_device_id` distinto al que tu token tiene ligado, la petición
    responde **`422`** con el error en la clave `plant_id` — **no** `403` ni
    `404`, a diferencia del `GET`.
- Si un cuerpo de petición incluye un `plant_id` en el body además del de la
  URL, se ignora: el valor de la URL siempre gana (`prepareForValidation` solo
  usa `$this->plant_id` del body si la ruta no trae `{plant}`, cosa que nunca
  ocurre en las rutas activas).

---

## Plantas (`/smartplant/plants`)

### `GET /smartplant/plants` — Mis plantas

- **Auth**: `auth:sanctum` + `ability:smartplant:write`.
- **Rate limit**: ninguno propio.
- **Query params** (contrato genérico de colecciones, `App\Http\Api\CollectionQuery`):

| Parámetro | Tipo | Reglas |
|---|---|---|
| `hardware_device_id` / `created_at` | — | filtrables por igualdad, `campo=a,b,c` (IN) o `campo[gte]=`/`campo[lte]=`/`campo[gt]=`/`campo[lt]=`/`campo[ne]=` |
| `from` | fecha | alias de `created_at >=` |
| `to` | fecha | alias de `created_at <=` |
| `sort` | string | columnas admitidas: `name`, `created_at`, `start_at`. Por defecto `name` **ascendente** (a diferencia del resto de colecciones de la API, que por defecto son descendentes) |
| `page` / `per_page` | int | por defecto `1` / `25`, `per_page` máximo `100` |

  Filtra siempre por el usuario dueño del token (`where('user_id', ...)`).

- **Respuesta 200**:

```json
{
  "success": true,
  "message": "Operación exitosa",
  "data": [
    {
      "id": 4,
      "user_id": 1,
      "name": "Monstera",
      "name_scientific": "Monstera deliciosa",
      "description": "Junto a la ventana del salón",
      "details": null,
      "image_url": "https://api-raupulus.example.com/storage/smartplant/4.jpg",
      "start_at": "2025-03-01T00:00:00.000000Z",
      "created_at": "2025-03-01T10:00:00.000000Z",
      "updated_at": "2026-08-29T08:00:00.000000Z"
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

  `image_url` es un accessor del modelo (`url_image`): resuelve a una imagen
  por defecto si la planta no tiene una propia asignada, nunca sale `null`.
  Este endpoint no incluye las lecturas de la planta (serían potencialmente
  miles de filas); para eso está `GET /plants/{plant}/readings`.

- **Errores**: `401` sin token o token inválido, `403` token sin la ability
  `smartplant:write`.

---

## Lecturas de una planta (`/smartplant/plants/{plant}/readings`)

### `GET /smartplant/plants/{plant}/readings` — Lecturas de una planta

- **Auth**: `auth:sanctum` + `ability:smartplant:write`.
- **Autorización adicional**: `SmartPlantPolicy::view` sobre la planta (debe
  ser tuya; si además tu token está ligado a un dispositivo concreto vía
  ability `device:{id}`, la planta debe colgar de ese mismo dispositivo).
- **Rate limit**: ninguno propio.
- **URL params**:

| Parámetro | Tipo | Reglas |
|---|---|---|
| `plant` | int | numérico (`whereNumber`); id de la planta |

- **Query params**:

| Parámetro | Tipo | Reglas |
|---|---|---|
| `created_at` | — | filtrable por igualdad, `created_at=a,b,c` (IN) o `created_at[gte]=`/`[lte]=`/`[gt]=`/`[lt]=`/`[ne]=` |
| `from` | fecha | alias de `created_at >=` |
| `to` | fecha | alias de `created_at <=` |
| `sort` | string | columnas admitidas: `created_at`, `id`. Por defecto `created_at` descendente |
| `page` / `per_page` | int | por defecto `1` / `25`, `per_page` máximo `100` |

- **Respuesta 200**:

```json
{
  "success": true,
  "message": "Operación exitosa",
  "data": [
    {
      "id": 980,
      "plant_id": 4,
      "hardware_device_id": 3,
      "uv": 2,
      "pressure": 1012.30,
      "temperature": 24.50,
      "humidity": 55.00,
      "soil_humidity": 42,
      "soil_humidity_raw": 610,
      "full_water_tank": true,
      "waterpump_enabled": false,
      "vaporizer_enabled": false,
      "created_at": "2026-08-30T09:00:00.000000Z"
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

  No hay `user_id` en este objeto (`smartplant_registers` no tiene esa
  columna, ver nota de diseño arriba). Para saber de quién es la lectura, usa
  el `user_id` de la planta (`GET /smartplant/plants`).

- **Errores**: `401` sin token o token inválido, `403` token sin la ability
  `smartplant:write`, `404` si la planta no existe **o** no es tuya (mismo
  mensaje en ambos casos, no es un oráculo de existencia).

---

### `POST /smartplant/plants/{plant}/readings` — Registra una lectura

- **Auth**: `auth:sanctum` + `ability:smartplant:write`.
- **Rate limit**: `api-store` — 60 peticiones/min (`RATE_LIMIT_IOT_STORE`,
  config `rate_limits.iot_store_per_minute`), identificado por el **id del
  token** Sanctum usado (no por IP).
- **URL params**:

| Parámetro | Tipo | Reglas |
|---|---|---|
| `plant` | int | numérico (`whereNumber`); id de la planta. Se valida también como campo `plant_id` (ver body) |

- **Body**:

| Campo | Tipo | Reglas |
|---|---|---|
| `hardware_device_id` | number | `required`, debe existir en `hardware_devices`, pertenecer al usuario del token y, si el token está ligado a un dispositivo concreto vía ability `device:{id}`, coincidir con ese dispositivo |
| `soil_humidity` | number | `required` |
| `uv` | number\|null | opcional |
| `pressure` | number\|null | opcional |
| `temperature` | number\|null | opcional |
| `humidity` | number\|null | opcional |
| `soil_humidity_raw` | number\|null | opcional |
| `full_water_tank` | boolean\|null | opcional |
| `waterpump_enabled` | boolean\|null | opcional |
| `vaporizer_enabled` | boolean\|null | opcional |
| `hardware_device_info` | object\|null | opcional. Último estado conocido del propio dispositivo (batería, temperatura, uptime...). Mismos campos que `PUT /hardware/devices/{device}/status`; si viene, se aplica sobre `hardware_device_id` en la misma petición. Contrato completo en [`hardware.md`](./hardware.md) |

  `plant_id` **no se envía en el body**: se toma de `{plant}` en la URL y se
  valida igualmente contra `exists:smartplant_plants,id` + `OwnedSmartPlant`
  (ver la nota de diseño arriba sobre por qué un `422`, no un `403`/`404`, en
  este verbo). No hay `user_id` en este recurso ni se acepta.

- **Respuesta 201**:

```json
{
  "success": true,
  "message": "Registro de planta almacenado",
  "data": {
    "id": 981,
    "plant_id": 4,
    "hardware_device_id": 3,
    "uv": 2,
    "pressure": 1012.30,
    "temperature": 24.50,
    "humidity": 55.00,
    "soil_humidity": 42,
    "soil_humidity_raw": 610,
    "full_water_tank": true,
    "waterpump_enabled": false,
    "vaporizer_enabled": false,
    "created_at": "2026-08-30T09:00:00.000000Z"
  }
}
```

- **Errores**:
  - `401` sin token o token inválido.
  - `403` token sin la ability `smartplant:write`.
  - `422` validación, incluyendo:
    - `plant_id` (el `{plant}` de la URL): no existe, no es tuya, o cuelga de
      un dispositivo distinto al que tu token tiene ligado.
    - `hardware_device_id`: no existe, no es tuyo, o no coincide con el
      dispositivo al que el token está ligado.
    - `soil_humidity` ausente.
  - `404`: solo el genérico de ruta si `{plant}` no es numérico (no hay 404 de
    negocio en este verbo; una planta ajena o inexistente da `422`, ver arriba).
  - `429` al superar 60/min con ese token.

---

## Lo que no existe en este módulo

No hay `PUT`/`DELETE` sobre plantas ni lecturas en la API V2: la gestión del
catálogo de plantas (alta, edición, borrado, imagen) se hace desde el panel
Filament del propio usuario, no desde este contrato IoT. Este archivo solo
cubre lo que un dispositivo necesita: leer sus plantas y escribir lecturas.

---

> Creado: 2026-08-30 · Última revisión: 2026-08-31
