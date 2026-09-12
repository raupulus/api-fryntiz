# Contrato API V2 — Energía

> Este archivo documenta **solo el contrato HTTP** de este módulo: rutas, auth,
> parámetros y forma exacta de la respuesta. Está pensado para copiarse a otro
> proyecto (o pegarse en el contexto de una IA) y que con eso baste para
> integrar estos endpoints sin leer el código fuente.
>
> Para el diseño interno (modelos, tablas, decisiones de arquitectura) ver
> [`docs/info/energy.md`](../../energy.md).

**Qué es este módulo.** Lo que un aparato *mide* de energía: un controlador
solar (Renogy Rover y compatibles) o un monitor de consumo de varios canales.
Como todos los módulos, sus lecturas cuelgan de un `hardware_device_id`.

**Qué NO es.** La salud del propio aparato —IP, uptime, CPU, RAM, discos,
temperatura— es del módulo Hardware: `PUT /hardware/devices/{id}/status`, ability
`hardware:write`. Ver [`hardware.md`](./hardware.md).

---

## Base y convenciones comunes a toda la API V2

- **Base URL**: `/api/v2`
- **Todas las respuestas** usan este envelope (`App\Traits\ApiResponseTrait`):

  ```json
  // Éxito
  { "success": true, "message": "Operación exitosa", "data": { ... } }
  // Error
  { "success": false, "message": "Descripción del error", "errors": { "campo": ["detalle"] } }
  ```

  `errors` solo aparece si hay detalle (p. ej. errores de validación 422). Las
  colecciones paginadas añaden además `meta: {total, per_page, current_page, last_page, from, to}`.
- **Autenticación**: Laravel Sanctum, cabecera `Authorization: Bearer <token>`.
  Este módulo usa dos abilities (catálogo completo en
  `app/Support/Auth/TokenAbilities.php`):
  - `energy:read` — consultar lecturas (`GET /energy/readings`, `GET /energy/solar-readings`).
  - `energy:write` — subirlas (`POST` de las mismas). Es la que se le graba a un
    controlador solar o a un contador de consumo IoT.
- **Sin campos `read_at`**: La API no utiliza ni exige fechas `read_at` en payloads ni tablas.
  El momento de la medición queda determinado por el `created_at` del servidor.

---

## Endpoints de Energía (`/energy/readings`)

### `GET /energy/readings` — Consulta de lecturas de energía paginadas

- **Auth**: `auth:sanctum` + `ability:energy:read`.
- **Rate limit**: `api` — 120 peticiones/min por token.
- **Query Parameters**:

| Parámetro | Tipo | Descripción |
|---|---|---|
| `hardware_device_id` | int | Filtra lecturas del dispositivo medidor |
| `hardware_energy_id` | int | Filtra por el elemento energético concreto |
| `role` | string | Filtra por rol de elemento (`generator`, `battery`, `load`) |
| `is_suspicious` | bool | `true` o `false` para filtrar muestras marcadas |
| `sort` | string | Campo de ordenación (`id`, `created_at`). Prefijo `-` para orden descendente. Por defecto `-id` |
| `per_page` | int | Elementos por página (por defecto 15, máx 100) |
| `page` | int | Número de página |

- **Respuesta 200** (`EnergyReadingResource::collection`):

```json
{
  "success": true,
  "message": "Lecturas obtenidas correctamente",
  "data": [
    {
      "id": 1024,
      "hardware_device_id": 15,
      "hardware_energy_id": 4,
      "element": {
        "id": 4,
        "role": "generator",
        "sensor_position": 0,
        "name": "Panel Solar Principal"
      },
      "voltage": 34.5,
      "amperage": 4.2,
      "power": 144.9,
      "delta_seconds": 60,
      "energy_wh": 2.415,
      "energy_ah": 0.07,
      "sources": {
        "energy": "derived",
        "voltage": "measured"
      },
      "battery": {
        "voltage": null,
        "percentage": null
      },
      "status": {
        "temperature": null,
        "fan": null,
        "charging_status": 3,
        "charging_status_label": "mppt",
        "light_status": false,
        "light_brightness": null
      },
      "is_suspicious": false,
      "suspicious_reason": null,
      "created_at": "2026-09-12T12:00:00.000000Z"
    }
  ],
  "meta": {
    "total": 1,
    "per_page": 15,
    "current_page": 1,
    "last_page": 1,
    "from": 1,
    "to": 1
  }
}
```

---

### `POST /energy/readings` — Subida de telemetría de energía universal

- **Auth**: `auth:sanctum` + `ability:energy:write`.
- **Rate limit**: `api-store` — 60 peticiones/min por token.
- **Body**:

| Campo | Tipo | Reglas / Descripción |
|---|---|---|
| `hardware_device_id` | int | `required`, identificador del dispositivo que envía la telemetría |
| `duration` | int | opcional (mín. 1). Segundos que cubre la integración de potencia/energía (por defecto 60) |
| `energy` | object | Bloque universal de telemetría energética |
| `energy.duration` | int | opcional (mín. 1). Sobrescribe `duration` a nivel de bloque |
| `energy.generator` | object | opcional. Datos del generador solar/eólico (ver sub-tabla) |
| `energy.battery` | object | opcional. Datos de la batería o banco acumulador (ver sub-tabla) |
| `energy.loads` | array | opcional. Lista de consumos monitorizados por canal (ver sub-tabla) |
| `device` | object | opcional. Estado físico del dispositivo medidor (`temp`, `voltage`, `cpu`, `ram`, `uptime`...) |

#### Sub-bloque `energy.generator`:
- `voltage` (float): Tensión medida de entrada (V).
- `amperage` (float): Corriente medida (A).
- `power` (float): Potencia (W). Si se omite, se calcula $V \cdot A$.
- `temperature` (float): Temperatura del controlador/generador (°C).
- `fan` (int): Estado/velocidad del ventilador (0/1).
- `charging_status` (int): Código numérico de estado de carga.
- `charging_status_label` (string): Etiqueta textual (ej. `mppt`, `boost`, `float`).
- `light_status` (bool): Estado de salida de alumbrado / farola.
- `light_brightness` (int): Nivel de brillo de alumbrado (0-100%).
- `today_energy_wh` (float): Acumulado de generación del día en Wh.
- `historical_energy_wh` (float): Acumulado total de generación del odómetro en Wh.

#### Sub-bloque `energy.battery`:
- `voltage` (float): Tensión de batería (V).
- `amperage` (float): Corriente neta de batería (A).
- `power` (float): Potencia de batería (W).
- `soc` o `battery_percentage` (int, 0-100): Estado de carga en porcentaje. Si se omite, se infiere de la curva de tensión del elemento.
- `temperature` (float): Temperatura de la batería (°C).
- `charging_status` (int): Código numérico de estado.
- `charging_status_label` (string): Etiqueta textual.
- `today_energy_ah` (float): Amperios-hora acumulados en el día (Ah).
- `historical_energy_ah` (float): Amperios-hora acumulados totales en el odómetro (Ah).
- `battery_full_charges` (int): Contador de cargas completas.
- `battery_over_discharges` (int): Contador de sobredescargas.

#### Sub-bloque `energy.loads` (array de objetos):
- `channel` o `sensor_position` (int): Número de canal de consumo (0, 1, 2...).
- `voltage` (float): Tensión del canal (V). Si se omite, recurre a `nominal_voltage` del elemento.
- `amperage` (float): Corriente consumida (A).
- `power` (float): Potencia consumida (W).
- `temperature` (float): Temperatura del canal/sensor (°C).
- `fan` (int): Estado del ventilador.
- `today_energy_wh` (float): Consumo acumulado en el día en Wh.
- `historical_energy_wh` (float): Consumo acumulado total en Wh.

#### Ejemplo de Petición Completa:
```json
{
  "hardware_device_id": 15,
  "duration": 60,
  "energy": {
    "generator": {
      "voltage": 34.5,
      "amperage": 4.2,
      "power": 144.9,
      "charging_status": 3,
      "charging_status_label": "mppt",
      "today_energy_wh": 1250.0,
      "historical_energy_wh": 45000.0
    },
    "battery": {
      "voltage": 13.4,
      "soc": 92,
      "temperature": 24.5,
      "today_energy_ah": 40.0,
      "historical_energy_ah": 1500.0
    },
    "loads": [
      {
        "channel": 0,
        "voltage": 12.1,
        "amperage": 2.5,
        "today_energy_wh": 310.0,
        "historical_energy_wh": 12500.0
      }
    ]
  }
}
```

- **Respuesta 201 Created**:
```json
{
  "success": true,
  "message": "Telemetría de energía almacenada correctamente",
  "data": [
    {
      "id": 1024,
      "hardware_device_id": 15,
      "hardware_energy_id": 4,
      "element": {
        "id": 4,
        "role": "generator",
        "sensor_position": 0,
        "name": "Panel Solar Principal"
      },
      "voltage": 34.5,
      "amperage": 4.2,
      "power": 144.9,
      "delta_seconds": 60,
      "energy_wh": 2.415,
      "energy_ah": 0.07,
      "sources": {
        "energy": "derived",
        "voltage": "measured"
      },
      "created_at": "2026-09-12T12:00:00.000000Z"
    }
  ],
  "warnings": []
}
```

---

## Endpoints de Compatibilidad Transitoria

### `POST /api/v2/energy/solar-readings`
Mantenido temporalmente para dar soporte directo a firmwares existentes del controlador Renogy Rover mientras se completa su actualización al bloque universal `energy`. Internamente realiza dual-write e ingesta en la persistencia unificada.

---

> Creado: 2026-09-06 · Última revisión: 2026-09-12
