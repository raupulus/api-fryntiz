# Contrato API V2 — Energía

> Este archivo documenta **solo el contrato HTTP** de este módulo: rutas, auth,
> parámetros y forma exacta de la respuesta. Está pensado para copiarse a otro
> proyecto (o pegarse en el contexto de una IA) y que con eso baste para
> integrar estos endpoints sin leer el código fuente.
>
> Para el diseño interno (modelos, tablas, decisiones) ver
> [`docs/info/energy.md`](../../energy.md).

**Qué es este módulo.** Lo que un aparato *mide* de energía: un controlador
solar (Renogy Rover y compatibles) o un monitor de consumo de varios canales.
Como todos los módulos, sus lecturas cuelgan de un `hardware_device_id`.

**Qué NO es.** La salud del propio aparato —IP, uptime, CPU, RAM, discos,
temperatura— es del módulo Hardware: `PUT /hardware/devices/{id}/status`, ability
`hardware:write`. Ver [`hardware.md`](./hardware.md).

> ⚠️ **Cambio del 2026-09-06.** Estos endpoints estaban bajo `/hardware`
> (`POST /hardware/solar-readings`, `POST /hardware/energy-readings`) y se
> cobraban con `hardware:write`. Ahora son `/energy/*` con ability `energy:*`.
>
> **Un cliente antiguo tiene que cambiar dos cosas: la URL y el token.** Las
> rutas viejas ya no existen y responden 404, y un token con `hardware:write`
> responde 403 aquí. No hay periodo de convivencia.

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
  - `energy:read` — consultar lecturas (`GET /energy/readings`,
    `GET /energy/solar-readings`).
  - `energy:write` — subirlas (`POST` de las mismas). Es la que se le graba a un
    controlador solar o a un contador de consumo. La emite
    `POST /auth/tokens/devices` (ver [`auth.md`](./auth.md)) o el panel.

  **No incluye la salud del aparato.** Eso es `hardware:write` sobre
  `PUT /hardware/devices/{id}/status`. Un cacharro que además quiera mandarla
  necesita las dos abilities — aunque para lo habitual no hace falta: las dos
  subidas de este módulo admiten `hardware_device_info` en el mismo cuerpo.

  El token puede venir además ligado a un `HardwareDevice` concreto (ability
  `device:{id}`). Cuando lo está, sólo alcanza ese dispositivo: un
  `hardware_device_id` que no coincida se rechaza con **422** de validación (la
  regla `OwnedHardwareDevice` del FormRequest falla sobre ese campo), no con 403.
  Lo mismo si el dispositivo es de otra cuenta.
- **Ruta inexistente**: cualquier método/URL que no esté documentado responde
  `404` con `{ "success": false, "message": "API V2 - Endpoint no encontrado" }`
  (incluso si el método HTTP es el que no cuadra: el contrato es la pareja
  método+ruta, no hay 405).

---

## Energía (`/energy/readings`)

### `GET /energy/readings` — Lecturas de energía, paginadas

- **Auth**: `auth:sanctum` + `ability:energy:read`.
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

### `POST /energy/readings` — Sube lecturas de un monitor de energía

- **Auth**: `auth:sanctum` + `ability:energy:write`.
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
| `hardware_device_info` | object\|null | opcional. La salud del aparato en la misma petición, sin necesidad de `hardware:write`. Campos: `temp`, `voltage`, `battery_level` (0-100), `cpu` (**% de uso**, 0-100), `disk` (**%**, 0-100), `ram` (**% de uso**, 0-100 — no megabytes), `uptime` (segundos), `ip_local`, `extra`. **`ip_public` se ignora**: la pone el servidor desde las cabeceras del proxy |

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
  - `403` token sin la ability `energy:write`.
  - `422` validación de campos, incluyendo `hardware_device_id` inexistente,
    ajeno o fuera del alcance del token.
  - `422` (`errorResponse`, no de validación de campos) si `readings` no
    produjo **ninguna** lectura guardable (ningún canal activo dado de alta, o
    ningún `pos` coincide); el body va con `message` explicando el motivo y
    `errors` con la lista de warnings de por qué se descartó cada una.
  - `429` al superar 60/min con ese token.

---

## Controlador solar (`/energy/solar-readings`)

### `GET /energy/solar-readings` — Lecturas del controlador solar, paginadas

- **Auth**: `auth:sanctum` + `ability:energy:read`.
- **Rate limit**: `api` — 120 peticiones/min por token.
- **Query**: `hardware_device_id`, `hardware_energy_id`, `date`, `read_at`,
  `sort` (`read_at\|date\|id`, por defecto `-read_at`), `per_page`, `page`.
- **Alcance**: el mismo que el índice de energía — dispositivos del usuario y,
  si el token los declara, sólo los suyos.
- **Respuesta**: colección paginada con la forma que devuelve el `POST`.

---

### `POST /energy/solar-readings` — Sube una lectura de un controlador solar

Una subida escribe en seis tablas: la lectura cruda, y el resumen del día y el
acumulado tanto de generación como del consumo de su salida de carga. Los
acumulados que manda el aparato mandan sobre los calculados, y **nunca bajan**
(un controlador reiniciado no borra el histórico). Detalle en
[`docs/info/energy.md`](../../energy.md).

Pensado específicamente para un Renogy Rover: el FormRequest traduce sus
nombres de campo (`pv_voltage`, `battery_soc`, `today_*`,
`historical_total_*`...) al vocabulario propio. Un campo ausente queda `null`,
nunca se fuerza a `0`.

- **Auth**: `auth:sanctum` + `ability:energy:write`.
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
  - `403` token sin la ability `energy:write`.
  - `422` validación de campos, incluyendo `hardware_device_id` inexistente,
    ajeno o fuera del alcance del token.
  - `429` al superar 60/min con ese token.

---

---

> Creado: 2026-09-06 · Última revisión: 2026-09-06
