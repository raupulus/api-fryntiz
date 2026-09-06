# Contrato API V2 — Hardware / Energía (Hardware)

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
  Este módulo usa dos abilities distintas (catálogo completo en
  `app/Support/Auth/TokenAbilities.php`):
  - `hardware:read` — leer el inventario de dispositivos (`GET /devices*`).
    Es obligatoria a propósito: antes cualquier token (incluido el de una
    estación meteorológica) podía leer el inventario completo de todos los
    usuarios, con número de serie incluido.
  - `hardware:write` — actualizar el último estado conocido del aparato
    (`PUT .../status`). La lleva un token de dispositivo emitido con
    `POST /auth/tokens/devices` (ver [`auth.md`](./auth.md)).
  - `hardwareenergy:read` — consultar lecturas de energía y solares
    (`GET /energy-readings`, `GET /solar-readings`).
  - `hardwareenergy:write` — subirlas (`POST /energy-readings`,
    `POST /solar-readings`).

  > ⚠️ **Cambio de contrato del 2026-09-06.** Las lecturas de energía y solares
  > se cobraban con `hardware:write` y ahora exigen `hardwareenergy:write`.
  > Energía es un módulo aparte: subir vatios y reescribir el estado del aparato
  > son permisos distintos, y un contador de consumo sólo necesita el primero.
  >
  > **Qué tiene que hacer un cliente:** nada, si su token se emitió antes del
  > cambio — el despliegue les añadió `hardwareenergy:*` a los tokens que ya
  > tenían la de hardware, para que ningún cacharro dejara de subir. **Los
  > tokens nuevos** de un aparato de energía se emiten con
  > `hardwareenergy:write` y **ya no necesitan** `hardware:write` salvo que
  > además manden `PUT .../status`.

  El token puede venir además ligado a un `HardwareDevice` concreto (ability
  `device:{id}`). Cuando lo está, sólo alcanza ese dispositivo: cualquier
  `hardware_device_id` (en el body o en la URL) que no coincida se rechaza. La
  forma en que se rechaza **no es uniforme** — depende de dónde vive la
  comprobación (ver cada endpoint):
  - En `GET /devices/{device}` es un **404** (la política `HardwarePolicy` lo
    trata igual que "no existe": no se confirma la existencia de dispositivos
    ajenos ni de dispositivos fuera de alcance del token).
  - En `PUT /devices/{device}/status`, `POST /energy-readings` y
    `POST /solar-readings` es un **422** de validación (la regla
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
  - `403` token sin la ability `hardwareenergy:write`.
  - `422` validación de los campos de estado; `422` también si
    `hardware_device_id` (el de la URL) no existe en `hardware_devices`, no
    pertenece al usuario del token, o no coincide con el dispositivo al que el
    token está ligado (`device:{id}`) — este último caso **no** es un 403 pese
    a ser un problema de alcance del token, porque la comprobación vive en la
    validación del FormRequest.
  - `429` al superar 60/min con ese token.

---

## Energía (`/hardware/energy-readings`)

### `GET /hardware/energy-readings` — Lecturas de energía, paginadas

- **Auth**: `auth:sanctum` + `ability:hardwareenergy:read`.
- **Rate limit**: `api` — 120 peticiones/min por token.
- **Query**:

| Parámetro | Tipo | Descripción |
|-----------|------|-------------|
| `type` | `load\|generator` | Consumo (por defecto) o generación. Son dos tablas distintas: comparten columnas por el trait `IsEnergyReading`, no la tabla. Cualquier otro valor da `422` |
| `hardware_device_id` | int | Filtra por dispositivo medido |
| `hardware_energy_id` | int | Filtra por elemento de energía |
| `date`, `read_at` | fecha o rango | Filtros de colección estándar (ver [`README.md`](./README.md)) |
| `sort` | `read_at\|date\|id` | Por defecto `-read_at` (lo más reciente primero) |
| `per_page`, `page` | int | Paginación estándar |

- **Alcance**: sólo lecturas de dispositivos del usuario del token. Si el token
  está ligado a dispositivos concretos (`device:{id}`), sólo las de ésos: un
  token de cacharro no lee el resto del parque de su dueño.
- **Respuesta**: colección paginada de lecturas con la misma forma que devuelve
  el `POST` (ver abajo).

---

### `POST /hardware/energy-readings` — Sube lecturas de un monitor de energía

- **Auth**: `auth:sanctum` + `ability:hardwareenergy:write`.
- **Rate limit**: `api-store` — 60 peticiones/min por token.
- **Body**:

| Campo | Tipo | Reglas |
|---|---|---|
| `hardware_device_id` | int | `required`, debe existir en `hardware_devices` y pertenecer al usuario del token (+ ligado a `device:{id}` si aplica) — es el **monitor**, no lo medido |
| `duration` | int\|null | opcional, mín. 1. Segundos que cubre la medición; sin él se guarda la potencia pero no los vatios-hora del periodo |
| `read_at` | date\|null | opcional, por defecto ahora |
| `temperature` | number\|null | opcional. Temperatura del propio monitor |
| `battery_voltage` | number\|null | opcional, mín. 0. Batería del propio dispositivo monitor (no de un canal) |
| `battery_percentage` | int\|null | opcional, entre 0 y 100 |
| `readings` | array | `required`, mín. 1 elemento |
| `readings.*.pos` | int | `required`, mín. 0. Canal → `hardware_energy.sensor_position`; sin él la lectura no se puede asignar a ningún elemento |
| `readings.*.amperage` | number\|null | opcional. Corriente **media** del periodo, no instantánea |
| `readings.*.voltage` | number\|null | opcional |
| `readings.*.duration` | int\|null | opcional, mín. 1. Sustituye a `duration` para ese canal si el montaje tiene cadencias distintas |
| `readings.*.energy_wh` | number\|null | opcional. Si el aparato ya calcula la energía, gana la suya sobre la derivada |
| `readings.*.temperature` | number\|null | opcional |
| `readings.*.fan` | int\|null | opcional, mín. 0 |
| `readings.*.read_at` | date\|null | opcional |
| `readings.*.battery_voltage` | number\|null | opcional, mín. 0. Batería del elemento medido, no la del monitor |
| `readings.*.battery_percentage` | int\|null | opcional, entre 0 y 100 |
| `hardware_device_info` | object\|null | opcional. Mismos campos que `PUT .../status` (`temp`, `voltage`, `battery_level`, `cpu`, `disk`, `ram`, `uptime`, `ip_local`, `extra`; **`ip_public` ya no se acepta**, la pone el servidor); si viene, se aplica como si se hubiera llamado a ese endpoint sobre `hardware_device_id`, en la misma petición |

  Cada `pos` se resuelve contra los elementos **activos** dados de alta en
  `hardware_energy` para ese dispositivo; si `pos` no casa con ninguno, esa
  lectura concreta se descarta (no la petición entera) y aparece en
  `warnings`. Si **ninguna** lectura se pudo asignar, la respuesta es `422`
  en vez de `201` (ver Errores).

- **Respuesta 201** (`EnergyMonitorResource::collection`; puede llevar
  `warnings` a nivel raíz cuando algo se ha guardado pero es raro):

```json
{
  "success": true,
  "message": "Lecturas de energia almacenadas",
  "data": [
    {
      "id": 501,
      "hardware_device_id": 12,
      "hardware_energy_id": 3,
      "measured": {
        "amperage": 1.42,
        "voltage": 12.4,
        "delta_seconds": 300,
        "temperature": 28.5
      },
      "derived": {
        "power": 17.6,
        "energy_wh": 1.47,
        "energy_ah": 0.118
      },
      "sources": {
        "energy": "derived",
        "voltage": "measured"
      },
      "is_suspicious": false,
      "suspicious_reason": null,
      "battery_voltage": null,
      "battery_percentage": null,
      "read_at": "2026-08-30T10:00:00.000000Z",
      "created_at": "2026-08-30T10:00:01.000000Z"
    }
  ],
  "warnings": [
    "«Frigorífico» ha mandado corriente negativa (-0.3 A): revisa el conexionado del sensor."
  ]
}
```

  `warnings` **no** aparece si no hay ninguno (no es una clave vacía). Casos
  que generan warning sin rechazar la lectura: corriente negativa, tensión
  ausente sin tensión nominal de respaldo, tensión medida fuera de lo
  plausible (se usó la nominal), o falta de `duration` (se guarda la potencia
  pero no los vatios-hora).

- **Errores**:
  - `401` sin token o token inválido.
  - `403` token sin la ability `hardwareenergy:write`.
  - `422` validación de campos, incluyendo `hardware_device_id` inexistente,
    ajeno o fuera del alcance del token.
  - `422` (`errorResponse`, no de validación de campos) si `readings` no
    produjo **ninguna** lectura guardable (ningún canal activo dado de alta, o
    ningún `pos` coincide); el body va con `message` explicando el motivo y
    `errors` con la lista de warnings de por qué se descartó cada una.
  - `429` al superar 60/min con ese token.

---

## Controlador solar (`/hardware/solar-readings`)

### `GET /hardware/solar-readings` — Lecturas del controlador solar, paginadas

- **Auth**: `auth:sanctum` + `ability:hardwareenergy:read`.
- **Rate limit**: `api` — 120 peticiones/min por token.
- **Query**: `hardware_device_id`, `hardware_energy_id`, `date`, `read_at`,
  `sort` (`read_at\|date\|id`, por defecto `-read_at`), `per_page`, `page`.
- **Alcance**: el mismo que el índice de energía — dispositivos del usuario y,
  si el token los declara, sólo los suyos.
- **Respuesta**: colección paginada con la forma que devuelve el `POST`.

---

### `POST /hardware/solar-readings` — Sube una lectura de un controlador solar

Pensado específicamente para un Renogy Rover: el FormRequest traduce sus
nombres de campo (`pv_voltage`, `battery_soc`, `today_*`,
`historical_total_*`...) al vocabulario propio. Un campo ausente queda `null`,
nunca se fuerza a `0`.

- **Auth**: `auth:sanctum` + `ability:hardwareenergy:write`.
- **Rate limit**: `api-store` — 60 peticiones/min por token.
- **Body** (nombres del contrato; el Rover puede mandar alias, ver nota):

| Campo | Tipo | Reglas |
|---|---|---|
| `hardware_device_id` | int | `required`, debe existir en `hardware_devices` y pertenecer al usuario del token (+ ligado a `device:{id}` si aplica) |
| `date` | date | `required` (se sintetiza de `read_at`/ahora si no viene, así que nunca da 422 por faltar) |
| `read_at` | date | `required` (idem: se sintetiza si no viene) |
| `hardware` | string\|null | opcional, máx. 255 |
| `version` | string\|null | opcional, máx. 255 |
| `serial_number` | string\|null | opcional, máx. 255 |
| `battery_type` | string\|null | opcional, máx. 255 |
| `battery_voltage` | number\|null | opcional |
| `battery_current` | number\|null | opcional |
| `battery_power` | number\|null | opcional |
| `battery_percentage` | int\|null | opcional, entre 0 y 100 |
| `battery_temperature` | number\|null | opcional |
| `temperature` | number\|null | opcional. Temperatura del propio controlador |
| `voltage` / `amperage` / `power` | number\|null | opcional. Generación (paneles) |
| `delta_seconds` | int\|null | opcional, mín. 1 |
| `charging_status` | int\|null | opcional |
| `charging_status_label` | string\|null | opcional, máx. 255 |
| `light_status` | bool\|null | opcional. Farola gobernada por el controlador |
| `light_brightness` | int\|null | opcional, entre 0 y 100 |
| `load_voltage` / `load_current` / `load_power` | number\|null | opcional. Salida de consumo del controlador |
| `load_fan` | int\|null | opcional, mín. 0 |
| `day_battery_voltage_min` / `day_battery_voltage_max` | number\|null | opcional |
| `day_charging_current_max` / `day_discharging_current_max` | number\|null | opcional |
| `day_charging_power_max` / `day_discharging_power_max` | number\|null | opcional |
| `day_charging_amp_hours` / `day_discharging_amp_hours` | number\|null | opcional |
| `day_power_generation_wh` / `day_power_consumption_wh` | number\|null | opcional |
| `total_operating_days` | int\|null | opcional, mín. 0. Si **baja** respecto a la lectura anterior del mismo dispositivo/`serial_number`, el controlador se ha reseteado |
| `total_battery_over_discharges` / `total_battery_full_charges` | int\|null | opcional, mín. 0 |
| `total_charging_amp_hours` / `total_discharging_amp_hours` | number\|null | opcional, mín. 0 |
| `total_power_generation_wh` / `total_power_consumption_wh` | number\|null | opcional, mín. 0 |
| `system_voltage` / `system_intensity` | number\|null | opcional |
| `nominal_battery_capacity` | int\|null | opcional, mín. 0 |
| `hardware_device_info` | object\|null | opcional. Igual que en `energy-readings`: aplica el estado del dispositivo en la misma petición |

  El FormRequest acepta alias de firmware/V1 para casi todos estos campos
  (p. ej. `pv_voltage`/`solar_voltage`/`energy_voltage` → `voltage`,
  `battery_soc` → `battery_percentage`, `today_power_generation` →
  `day_power_generation_wh`, `historical_cumulative_power_generation` →
  `total_power_generation_wh`); si el nombre nativo y un alias vienen a la vez,
  gana el nombre nativo. La validación se hace siempre sobre los nombres de la
  tabla anterior.

- **Respuesta 201** (`SolarReadingResource`; puede llevar `warnings`):

```json
{
  "success": true,
  "message": "Lectura del controlador solar almacenada",
  "data": {
    "id": 88,
    "hardware_device_id": 15,
    "hardware_energy_id": 4,
    "date": "2026-08-30",
    "read_at": "2026-08-30T10:00:00.000000Z",
    "controller": {
      "hardware": "Renogy Rover 40A",
      "version": "v1.2.0",
      "serial_number": "RNG-40A-0001",
      "temperature": 32.1,
      "system_voltage": 12,
      "system_intensity": 40,
      "nominal_battery_capacity": 100
    },
    "battery": {
      "type": "gel",
      "voltage": 13.2,
      "current": 4.1,
      "power": 54.1,
      "percentage": 92,
      "temperature": 29.4
    },
    "generation": {
      "voltage": 18.6,
      "amperage": 2.9,
      "power": 53.9,
      "energy_wh": null,
      "energy_ah": null,
      "charging_status": 2,
      "charging_status_label": "mppt"
    },
    "load": {
      "voltage": 12.9,
      "current": 0.8,
      "power": 10.3,
      "fan": null
    },
    "street_light": {
      "status": true,
      "brightness": 80
    },
    "day": {
      "battery_voltage_min": 12.6,
      "battery_voltage_max": 13.4,
      "charging_current_max": 3.2,
      "discharging_current_max": 1.1,
      "charging_power_max": 60.5,
      "discharging_power_max": 14.2,
      "charging_amp_hours": 18.4,
      "discharging_amp_hours": 6.1,
      "power_generation_wh": 240.5,
      "power_consumption_wh": 78.2
    },
    "total": {
      "operating_days": 412,
      "battery_over_discharges": 3,
      "battery_full_charges": 210,
      "charging_amp_hours": 9820.4,
      "discharging_amp_hours": 4110.7,
      "power_generation_wh": 118500.2,
      "power_consumption_wh": 49300.9
    },
    "sources": {
      "energy": "device",
      "voltage": "measured"
    },
    "is_suspicious": false,
    "suspicious_reason": null,
    "created_at": "2026-08-30T10:00:01.000000Z"
  }
}
```

  `warnings` puede incluir el aviso de reinicio del controlador (cuando
  `total_operating_days` baja respecto a la lectura anterior — el acumulado
  previo no se pierde, la nueva lectura simplemente abre serie aparte), que no
  hay ningún elemento generador dado de alta para asignar la lectura, o
  corriente negativa.

- **Errores**:
  - `401` sin token o token inválido.
  - `403` token sin la ability `hardwareenergy:write`.
  - `422` validación de campos, incluyendo `hardware_device_id` inexistente,
    ajeno o fuera del alcance del token.
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
