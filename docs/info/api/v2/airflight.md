# Contrato API V2 — Seguimiento de vuelos (AirFlight)

> Este archivo documenta **solo el contrato HTTP** de este módulo: rutas, auth,
> parámetros y forma exacta de la respuesta. Está pensado para copiarse a otro
> proyecto (o pegarse en el contexto de una IA) y que con eso baste para
> integrar estos endpoints sin leer el código fuente.
>
> Para el diseño interno (modelos, receptor ADS-B, decisiones de producto) ver
> [`docs/info/airflight.md`](../../airflight.md).

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
  Los endpoints de escritura de este módulo requieren un **token de
  dispositivo IoT** con la ability `airflight:write` (catálogo completo en
  `app/Support/Auth/TokenAbilities.php`; se emite con `POST
  /auth/tokens/devices`, ver [`auth.md`](./auth.md)). El token puede venir
  además ligado a un `HardwareDevice` concreto (ability `device:{id}`); si es
  así, solo puede escribir usando ese `hardware_device_id` (ver más abajo).
- **Ruta inexistente**: cualquier método/URL que no esté documentado responde
  `404` con `{ "success": false, "message": "API V2 - Endpoint no encontrado" }`
  (incluso si el método HTTP es el que no cuadra: el contrato es la pareja
  método+ruta, no hay 405).

---

## Receptor (`/airflight/receiver`)

Hay un único receptor ADS-B, así que es un recurso único sin colección (no
`GET /airflight/receivers`).

### `GET /airflight/receiver` — Datos del receptor

- **Auth**: no requiere autenticación.
- **Respuesta 200** (valores fijos de configuración, no vienen de base de datos):

```json
{
  "success": true,
  "message": "Operación exitosa",
  "data": {
    "history": 0,
    "lat": 36.7381,
    "lon": -6.4301,
    "refresh": 5000,
    "version": "api raupulus v2"
  }
}
```

  - `history` siempre es `0`: no se guardan snapshots temporales para
    reproducir el recorrido en el tiempo (solo la última posición por avión),
    así que el mapa desactiva la reproducción de historial con este flag.
  - `lat`/`lon` son la posición fija del receptor.
  - `refresh` es la cadencia sugerida (ms) para que el cliente vuelva a pedir
    `GET /aircrafts`.
  - No hay caso de "receptor no configurado": la respuesta es siempre la
    misma, no lee ningún modelo.

---

## Aviones (`/airflight/aircrafts`)

Un único endpoint de colección sirve tanto al mapa en vivo como al histórico
por fechas; no son recursos distintos.

### `GET /airflight/aircrafts` — Aviones detectados

- **Auth**: no requiere autenticación.
- **Comportamiento según query params** (mutuamente excluyente, decidido por
  si `from` o `to` vienen rellenos):

  - **Sin `from`/`to` (modo mapa en vivo)**: devuelve **todos** los aviones
    activos dentro de la ventana de minutos indicada, **sin paginar** (son
    pocas filas y el mapa las quiere todas de golpe). Incluye el campo
    `trail` relleno.
  - **Con `from` y/o `to` (modo histórico)**: pagina con
    `App\Http\Api\CollectionQuery` sobre `created_at`/`seen_last_at`. El campo
    `trail` sale siempre `[]` en este modo (la relación no se carga para el
    histórico).

- **Query params — modo mapa en vivo** (se usan cuando `from` y `to` están
  ambos vacíos):

| Parámetro | Tipo | Reglas |
|---|---|---|
| `minutes` | int | opcional, por defecto `10`. Se acota entre `1` y `1440` (24h) |

- **Query params — modo histórico** (se activa si `from` y/o `to` vienen
  rellenos; usa el contrato genérico de colecciones de la API V2):

| Parámetro | Tipo | Reglas |
|---|---|---|
| `from` | fecha | alias de `created_at >=` |
| `to` | fecha | alias de `created_at <=` |
| `icao` / `seen_last_at` / `created_at` | — | filtrables por igualdad, `campo=a,b,c` (IN) o `campo[gte]=`/`campo[lte]=`/`campo[gt]=`/`campo[lt]=`/`campo[ne]=` |
| `sort` | string | columnas admitidas: `seen_last_at`, `created_at`. Por defecto `seen_last_at` descendente. Prefijo `-` para descendente, se admite lista separada por comas |
| `page` | int | por defecto `1` |
| `per_page` | int | por defecto `25`, máximo `100` |

  Cualquier parámetro fuera de esta lista se ignora en silencio (no filtra ni
  da error).

- **Respuesta 200 — modo mapa en vivo** (array plano, sin `meta`):

```json
{
  "success": true,
  "message": "Operación exitosa",
  "data": [
    {
      "id": 42,
      "icao": "3443d1",
      "category": null,
      "flight": "IBE1234",
      "squawk": "1000",
      "lat": 36.71,
      "lon": -6.42,
      "altitude": 3500,
      "vert_rate": 0,
      "speed": 210.5,
      "track": 134.2,
      "rssi": -12.5,
      "seen": 3,
      "seen_pos": 3,
      "messages": 128,
      "trail": [
        [-6.44, 36.7],
        [-6.42, 36.71]
      ],
      "created_at": "2026-08-30T10:00:00.000000Z"
    }
  ]
}
```

- **Respuesta 200 — modo histórico** (paginada, con `meta`):

```json
{
  "success": true,
  "message": "Operación exitosa",
  "data": [
    {
      "id": 42,
      "icao": "3443d1",
      "category": null,
      "flight": "IBE1234",
      "squawk": "1000",
      "lat": 36.71,
      "lon": -6.42,
      "altitude": 3500,
      "vert_rate": 0,
      "speed": 210.5,
      "track": 134.2,
      "rssi": -12.5,
      "seen": 3,
      "seen_pos": 3,
      "messages": 128,
      "trail": [],
      "created_at": "2026-08-30T10:00:00.000000Z"
    }
  ],
  "meta": {
    "total": 137,
    "per_page": 25,
    "current_page": 1,
    "last_page": 6,
    "from": 1,
    "to": 25
  }
}
```

  Notas de campos (vienen del avión + su última posición conocida —
  `latestRoute`—, no de dos recursos separados):
  - `id`, `icao`, `category`, `created_at` pertenecen al avión.
  - `flight`, `squawk`, `lat`, `lon`, `altitude`, `vert_rate`, `speed`,
    `track`, `messages` pertenecen a la última posición (`route`); si el avión
    no tiene ninguna posición registrada, todos salen `null`.
  - `rssi` sale `-100.0` (float) si la posición no trae RSSI, nunca `null`.
  - `seen` y `seen_pos` son el mismo valor (segundos transcurridos desde
    `seen_at` hasta ahora): el esquema solo guarda un timestamp por detección,
    así que no hay dos marcas de tiempo distintas que reportar. Si no hay
    posición, ambos salen `null`.
  - `trail` es el recorrido conocido como lista de pares `[lon, lat]`
    (el más antiguo primero), limitado a los últimos 50 puntos. Solo se
    rellena en modo mapa en vivo.

---

### `POST /airflight/aircrafts` — Registra un avión detectado

- **Auth**: `auth:sanctum` + `ability:airflight:write`.
- **Rate limit**: `api-store` — 60 peticiones/min (`RATE_LIMIT_IOT_STORE`,
  config `rate_limits.iot_store_per_minute`), identificado por el **id del
  token** Sanctum usado (no por IP).
- **Body**:

| Campo | Tipo | Reglas |
|---|---|---|
| `hardware_device_id` | int\|null | opcional, debe existir en `hardware_devices` y pertenecer al usuario del token (si el token está ligado a un dispositivo concreto vía ability `device:{id}`, debe coincidir con ese dispositivo) |
| `icao` | string | `required`, máx. 10 |
| `flight` | string\|null | opcional, máx. 20 |
| `squawk` | string\|null | opcional, máx. 10 |
| `lat` | number\|null | opcional, entre -90 y 90 |
| `lon` | number\|null | opcional, entre -180 y 180 |
| `altitude` | number\|null | opcional, mín. 0 |
| `speed` | number\|null | opcional, mín. 0 |
| `track` | number\|null | opcional, entre 0 y 360 |
| `seen` | number\|null | opcional (no se persiste: el esquema guarda `seen_at`, calculado al recibir la petición, no "hace cuántos segundos") |
| `seen_pos` | number\|null | opcional (mismo caso que `seen`, no se persiste) |
| `messages` | int\|null | opcional, mín. 0 |
| `hardware_device_info` | object\|null | opcional. Último estado conocido del receptor (batería, temperatura, uptime...). Mismos campos que `PUT /hardware/devices/{device}/status`; solo tiene efecto si esta misma petición trae también `hardware_device_id` — sin dispositivo no hay a quién aplicarle el estado, y se ignora sin error. Contrato completo en [`hardware.md`](./hardware.md) |

> ⚠️ **`ip_public` ya no se acepta dentro de `hardware_device_info`**
> (2026-09-06): la pone el servidor desde la IP de origen de la petición. Y
> hay campo nuevo, `ram` (0-100 %). Detalle en
> [`hardware.md`](./hardware.md#-cambio-de-contrato--ip_public-2026-09-06).


  El avión se busca/crea por `icao` (no se duplica una fila por cada sondeo
  del mismo aparato): si ya existe, se actualiza `seen_last_at` y se añade una
  nueva fila de posición (`airflight_routes`); solo se crea posición si al
  menos uno de los campos de posición viene relleno.

- **Respuesta 201**:

```json
{
  "success": true,
  "message": "Avion registrado",
  "data": {
    "id": 42,
    "icao": "3443d1",
    "category": null,
    "flight": "IBE1234",
    "squawk": "1000",
    "lat": 36.71,
    "lon": -6.42,
    "altitude": 3500,
    "vert_rate": null,
    "speed": 210.5,
    "track": 134.2,
    "rssi": -100.0,
    "seen": 0,
    "seen_pos": 0,
    "messages": 128,
    "trail": [],
    "created_at": "2026-08-30T10:00:00.000000Z"
  }
}
```

  `trail` sale `[]` aquí porque esta respuesta no carga esa relación (solo la
  carga el modo mapa en vivo de `GET /aircrafts`).

- **Errores**:
  - `401` sin token o token inválido.
  - `403` token sin la ability `airflight:write`.
  - `422` validación de campos, incluyendo `hardware_device_id` que no exista,
    que no sea del usuario, o que no coincida con el dispositivo al que el
    token está ligado.
  - `429` al superar 60/min con ese token.

---

### `POST /airflight/aircrafts/batch` — Registra un lote de aviones detectados

Existe porque el receptor manda hasta 500 aeronaves por barrido; partirlo en
500 peticiones individuales no tiene sentido.

- **Auth**: `auth:sanctum` + `ability:airflight:write`.
- **Rate limit**: `api-store-batch` — 20 peticiones/min
  (`RATE_LIMIT_IOT_BATCH`, config `rate_limits.iot_batch_per_minute`),
  identificado por el **id del token** Sanctum usado (no por IP).
- **Body**:

| Campo | Tipo | Reglas |
|---|---|---|
| `hardware_device_id` | int\|null | opcional, mismas reglas que en el alta individual (pertenencia + ligado al token) |
| `data` | array | `required`, mínimo 1, **máximo 500** elementos |
| `data.*.icao` | string | `required`, máx. 10 |
| `data.*.flight` | string\|null | opcional, máx. 20 |
| `data.*.squawk` | string\|null | opcional, máx. 10 |
| `data.*.lat` | number\|null | opcional, entre -90 y 90 |
| `data.*.lon` | number\|null | opcional, entre -180 y 180 |
| `data.*.altitude` | number\|null | opcional, mín. 0 |
| `data.*.speed` | number\|null | opcional, mín. 0 |
| `data.*.track` | number\|null | opcional, entre 0 y 360 |
| `data.*.seen` | number\|null | opcional (no se persiste) |
| `data.*.seen_pos` | number\|null | opcional (no se persiste) |
| `data.*.messages` | int\|null | opcional, mín. 0 |
| `hardware_device_info` | object\|null | opcional. Igual que en el alta individual: solo tiene efecto si el lote trae también `hardware_device_id` en la raíz |

  El `hardware_device_id` es único para todo el lote (no por elemento). Cada
  elemento de `data` se procesa igual que en el alta individual (buscar/crear
  avión por `icao` + nueva fila de posición si trae algún campo de posición).

- **Respuesta 201** (no devuelve los aviones, solo el recuento):

```json
{
  "success": true,
  "message": "Lote registrado",
  "data": {
    "count": 87
  }
}
```

- **Errores**:
  - `401` sin token o token inválido.
  - `403` token sin la ability `airflight:write`.
  - `422` validación: `data` vacío, con más de 500 elementos, o cualquier
    elemento con campos inválidos (el índice del elemento aparece en la clave
    del error, p. ej. `data.3.icao`).
  - `429` al superar 20/min con ese token.

---

## Lo que ya no existe, y por qué

| Ruta antigua | Qué pasó |
|---|---|
| `GET /airflight/db/{bkey}` | Retirada: siempre devolvía 404 porque el dataset del registro OACI (matrícula/modelo/país a partir del hexadecimal ICAO) no se mantiene. Un endpoint que solo sabe decir "no encontrado" es peor que no tenerlo: parece que existe. |
| `GET /airflight/history` | Retirada: era la misma colección de aviones que `GET /aircrafts`, sin la ventana de actividad reciente. Ahora es `GET /aircrafts?from=&to=` (paginado), sobre el mismo recurso. |

`GET /airflight/receiver` se mantiene tal cual pese a no tener colección: hay
**un** receptor ADS-B, y un recurso único sin colección es correcto en REST,
no una omisión.

El alta por lotes (`/aircrafts/batch`) se mantiene separada del alta
individual (no se sustituye una por otra) porque el receptor manda hasta 500
aeronaves por barrido y meterlas una a una saturaría el límite de
`api-store` (60/min) mucho antes que el de `api-store-batch` (20/min, pero
cada petición trae hasta 500 filas).

---

> Creado: 2026-08-30 · Última revisión: 2026-09-06
