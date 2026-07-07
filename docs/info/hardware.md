# Módulo: Hardware y Energía

Módulo IoT para gestionar dispositivos hardware, monitorizar consumos de energía y registrar datos de cargas solares, generadores y consumos con resúmenes diarios e históricos.

## Archivos principales

### Modelos
| Archivo | Tabla | Descripción |
|---------|-------|-------------|
| `app/Models/Hardware/HardwareDevice.php` | `hardware_devices` | Dispositivo hardware principal |
| `app/Models/Hardware/HardwareType.php` | `hardware_types` | Tipos de dispositivo |
| `app/Models/Hardware/HardwareComponent.php` | `hardware_components` | Componentes de un dispositivo |
| `app/Models/Hardware/HardwareAvailableComponent.php` | `hardware_available_components` | Catálogo de componentes disponibles |
| `app/Models/Hardware/HardwareEnergy.php` | `hardware_energy` | Relación monitorización de energía |
| `app/Models/Hardware/HardwarePowerGenerator.php` | `hardware_power_generators` | Datos de generador solar en tiempo real |
| `app/Models/Hardware/HardwarePowerGeneratorToday.php` | `hardware_power_generators_today` | Resumen diario del generador |
| `app/Models/Hardware/HardwarePowerGeneratorHistorical.php` | `hardware_power_generators_historical` | Histórico del generador |
| `app/Models/Hardware/HardwarePowerLoad.php` | `hardware_power_loads` | Datos de consumo en tiempo real |
| `app/Models/Hardware/HardwarePowerLoadToday.php` | `hardware_power_loads_today` | Resumen diario de consumos |
| `app/Models/Hardware/HardwarePowerLoadHistorical.php` | `hardware_power_loads_historical` | Histórico de consumos |
| `app/Models/Hardware/SolarCharge.php` | — | Modelo legacy para cargas solares |

### Controladores
| Archivo | Versión | Descripción |
|---------|---------|-------------|
| `app/Http/Controllers/Api/Hardware/V2/HardwareDeviceController.php` | API V2 | Ver dispositivo, listar computadores |
| `app/Http/Controllers/Api/Hardware/V2/EnergyMonitorController.php` | API V2 | Store datos de energía |
| `app/Http/Controllers/Api/Hardware/V2/SolarChargeController.php` | API V2 | Store carga solar |
| `app/Http/Controllers/Api/Hardware/V2/DeviceStatusController.php` | API V2 | Store del último estado del dispositivo (temp, tensión, batería, CPU, disco, uptime, IPs) |
| `app/Http/Controllers/Hardware/*.php` | Web | Controladores frontend (10 archivos) |

### Servicios
| Archivo | Descripción |
|---------|-------------|
| `app/Services/Hardware/HardwareService.php` | Lógica: info dispositivo, store energía/solar, lista computadores |
| `app/Services/Hardware/DeviceTokenService.php` | Emisión de tokens Sanctum ligados a un dispositivo (fuente única para el comando y Filament) |

### Resources API V2
| Archivo | Descripción |
|---------|-------------|
| `app/Http/Resources/V2/Hardware/HardwareDeviceResource.php` | Resource dispositivo |
| `app/Http/Resources/V2/Hardware/EnergyMonitorResource.php` | Resource energía |
| `app/Http/Resources/V2/Hardware/SolarChargeResource.php` | Resource carga solar |
| `app/Http/Resources/V2/Hardware/DeviceStatusResource.php` | Resource último estado del dispositivo |

### FormRequests V2
| Archivo | Descripción |
|---------|-------------|
| `app/Http/Requests/Api/Hardware/V2/StoreEnergyRequest.php` | Validación store energía |
| `app/Http/Requests/Api/Hardware/V2/StoreSolarChargeRequest.php` | Validación store carga solar |
| `app/Http/Requests/Api/Hardware/V2/StoreDeviceStatusRequest.php` | Validación store estado del dispositivo (acepta `hardware_device_info` agrupado) |

### Otros
| Archivo | Descripción |
|---------|-------------|
| `app/Policies/HardwarePolicy.php` | Política de autorización |
| `app/Enums/HardwareTypeEnum.php` | Enum tipos de hardware |
| `app/Traits/BelongsToHardwareDevice.php` | Trait relación con dispositivo hardware |
| `app/Http/Controllers/Api/Hardware/V2/Concerns/HandlesHardwareDeviceInfo.php` | Trait para adjuntar estado del dispositivo (`hardware_device_info`) en subidas existentes |
| `app/Rules/OwnedHardwareDevice.php` | Regla de validación: pertenencia del dispositivo (por usuario + ligado estricto por token) |
| `app/Console/Commands/IoT/IssueDeviceTokenCommand.php` | Comando `iot:device-token` (usa `DeviceTokenService`) |

## Campos del modelo HardwareDevice

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | bigint | PK |
| `user_id` | int | FK → `users.id` — propietario |
| `hardware_type_id` | int | FK → `hardware_types.id` — tipo de dispositivo |
| `referred_thing_id` | int | FK opcional — dispositivo referenciado |
| `name` | string | Nombre técnico |
| `name_friendly` | string | Nombre amigable |
| `ref` | string | Referencia |
| `model` | string | Modelo del hardware |
| `brand` | string | Marca |
| `software_version` | string | Versión de software |
| `hardware_version` | string | Versión de hardware |
| `serial_number` | string | Número de serie |
| `battery_type` | string | Tipo de batería |
| `battery_nominal_capacity` | string | Capacidad nominal de batería |
| `url_company` | string | URL del fabricante |
| `description` | text | Descripción |
| `buy_at` | date | Fecha de compra |
| `last_seen_at` | timestamp | Última conexión (se actualiza en cada subida de estado) |
| `ip_local` | string | IP local |
| `ip_public` | string | IP pública |
| `temp` | decimal | Último estado: temperatura del dispositivo (ºC) |
| `voltage` | decimal | Último estado: tensión del dispositivo (V) |
| `battery_level` | smallint | Último estado: nivel de batería (0-100) |
| `cpu` | decimal | Último estado: uso de CPU (0-100) |
| `disk` | decimal | Último estado: uso de disco (0-100) |
| `uptime` | bigint | Último estado: tiempo de actividad (segundos) |
| `extra` | json | Último estado: métricas adicionales (RAM, procesos, etc.) |

> **Estado de dispositivo (sin histórico):** las columnas `temp`, `voltage`, `battery_level`, `cpu`, `disk`, `uptime`, `extra`, `ip_local`, `ip_public` y `last_seen_at` reflejan siempre el **último estado conocido** del propio dispositivo. No se guarda histórico. Se actualizan mediante el endpoint dedicado `POST /api/v2/hardware/device-status` o adjuntando una clave opcional `hardware_device_info` en cualquier subida IoT del módulo (energía, carga solar).

## Campos del modelo HardwarePowerGenerator

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `hardware_device_id` | int | FK → `hardware_devices.id` |
| `battery_voltage` | decimal | Voltaje de la batería |
| `battery_temperature` | decimal | Temperatura de la batería |
| `battery_percentage` | int | Porcentaje de batería |
| `charging_status` | int | Estado de carga |
| `charging_status_label` | string | Etiqueta estado de carga |
| `amperage` | decimal | Amperaje |
| `voltage` | decimal | Voltaje |
| `power` | decimal | Potencia |
| `light_status` | int | Estado de la luz |
| `light_brightness` | int | Brillo de la luz |
| `read_at` | timestamp | Fecha de lectura |

## Campos del modelo HardwarePowerLoad

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `hardware_device_id` | int | FK → `hardware_devices.id` |
| `fan` | int | Ventilador |
| `temperature` | decimal | Temperatura |
| `voltage` | decimal | Voltaje |
| `amperage` | decimal | Amperaje |
| `power` | decimal | Potencia |
| `battery_voltage` | decimal | Voltaje batería |
| `battery_percentage` | int | Porcentaje batería |
| `read_at` | timestamp | Fecha de lectura |

## Relaciones

- `HardwareDevice` → `BelongsTo` → `User` (vía `user_id`)
- `HardwareDevice` → `BelongsTo` → `HardwareType` (vía `hardware_type_id`)
- `HardwareDevice` → `HasMany` → `HardwareComponent`
- `HardwareDevice` → `HasMany` → `ApiToken` (vía `apiTokens()`, solo lectura: tokens del usuario propietario con nombre `device:{id}`)
- `HardwareEnergy` → `BelongsTo` → `HardwareDevice` (vía `hardware_device_id`)
- `HardwarePowerGenerator/Load` → `BelongsTo` → `HardwareDevice`

## Rutas API V2

| Método | Ruta | Auth | Throttle | Descripción |
|--------|------|------|----------|-------------|
| GET | `/api/v2/hardware/device/{id}` | Sí | api-store | Ver dispositivo |
| GET | `/api/v2/hardware/computers` | Sí | api-store | Listar computadores |
| POST | `/api/v2/hardware/energy` | Sí | api-store | Store datos energía (admite `hardware_device_info` opcional) |
| POST | `/api/v2/hardware/solar-charge` | Sí | api-store | Store carga solar (admite `hardware_device_info` opcional) |
| POST | `/api/v2/hardware/device-status` | Sí (`ability:hardware:write`) | api-store | Store solo el último estado del dispositivo |

### Estado del dispositivo (`device-status`)

Pensado para NAS, Raspberry Pi, portátiles, etc. que suben periódicamente su
estado. El cuerpo debe incluir siempre `hardware_device_id` (id del dispositivo,
validado con `OwnedHardwareDevice`). Campos opcionales: `temp`, `voltage`,
`battery_level`, `cpu`, `disk`, `uptime`, `ip_local`, `ip_public`, `extra`.
No se guarda histórico: solo se sobrescribe el último estado y se actualiza
`last_seen_at` al momento actual.

> **Nombre canónico del dispositivo:** el identificador del dispositivo es
> **`hardware_device_id`** en entrada y salida en todos los endpoints de
> escritura del módulo (`energy`, `solar-charge`, `device-status`). La columna
> física en `solar_charges` también se llama `hardware_device_id` desde la
> migración `2026_07_06_000002`.

Ejemplo de cuerpo:

```json
{
    "hardware_device_id": 1,
    "temp": 33,
    "voltage": 3.7,
    "battery_level": 48,
    "ip_local": "192.168.1.100",
    "ip_public": "203.0.113.1",
    "cpu": 33,
    "uptime": 123456,
    "disk": 80,
    "extra": {}
}
```

En subidas conjuntas con otros datos, el estado puede agruparse dentro de
`hardware_device_info` (se aplana automáticamente):

```json
{
    "hardware_device_id": 1,
    "hardware_device_info": { "temp": 33, "voltage": 3.7, "uptime": 123456 },
    "data": {}
}
```

## Rutas Web

| Ruta | Descripción |
|------|-------------|
| `/hardware` | Listado de dispositivos |
| `/hardware/energy` | Dashboard de energía con iconos Material Symbols |

### Frontend (Fix 5)

- **Iconos Material Symbols:** Las tarjetas de estadísticas ahora soportan un campo `icon` opcional (fallback a `bolt`, `electric_meter`, `energy_savings_leaf` según sección).
- **Tarjetas de dispositivos:** Icono `solar_power` con colores amber para cada dispositivo hardware.
- **Diseño profesional:** Fondos con gradientes por sección (amber para "Ahora", emerald para "Hoy", indigo para "Histórico").

### Comandos de debug

```bash
# Crear dispositivos hardware sueltos (con último estado de ejemplo:
# temp, voltage, battery_level, cpu, disk, uptime y extra poblados)
php artisan debug:seed-hardware --count=5

# Crear dispositivos y registros de energía solar (voltaje, corriente, potencia)
php artisan debug:seed-energy --devices=5 --records=100
```

> **fix_11:** `debug:seed-energy` crea ahora asociaciones en `hardware_energy`
> (tabla pivote monitor ↔ monitorizado): auto-monitorización, monitorización
> cruzada y bidireccional. Sin estas asociaciones las vistas de energía no pueden
> resolver qué dispositivo monitoriza a cuál.

## Tokens IoT por dispositivo

Las escrituras IoT se autentican con **tokens Sanctum por dispositivo**. Cada
token se crea sobre el **usuario propietario** del dispositivo, se nombra
`device:{id}` y, además de las abilities de módulo (`hardware:write`,
`weatherstation:write`, etc.), incluye la ability **`device:{id}`** que lo liga
de forma estricta a ese dispositivo concreto.

### Emisión

- **Terminal:** `php artisan iot:device-token <id> --abilities=hardware:write [--expires=días]`.
- **Filament:** desde la ficha del dispositivo, pestaña **"Tokens IoT"** →
  botón *Emitir token* (selección de abilities de módulo + expiración opcional).
  El token en claro se muestra una sola vez en una notificación persistente.

Ambas vías usan `DeviceTokenService::issue()`, que añade automáticamente la
ability `device:{id}`. El comando y Filament son equivalentes.

### Validación de pertenencia (`OwnedHardwareDevice`)

Regla aplicada al campo `hardware_device_id` en todos los FormRequest de
escritura IoT (Hardware, WeatherStation, KeyCounter, SmartPlant). Comprueba:

1. **Pertenencia por usuario:** el dispositivo debe pertenecer al usuario
   autenticado.
2. **Ligado estricto por dispositivo:** si el token declara abilities
   `device:{id}`, el dispositivo indicado debe coincidir con una de ellas — un
   dispositivo no puede escribir datos con el `hardware_device_id` de otro.

Los tokens sin ability `device:*` (p. ej. `*` o tokens ajenos a dispositivos)
solo pasan la comprobación por usuario; como la regla vive únicamente en los
FormRequest IoT, no afecta a otros endpoints ni tipos de token. Mantiene
compatibilidad con tokens antiguos (solo ligados al usuario).

## Configuración Filament

Panel **Admin**, grupo de navegación **Hardware**
(`HardwareDeviceResource`). Además de los campos del dispositivo, incluye dos
RelationManagers en la ficha de edición:

- **Componentes instalados** (`ComponentsRelationManager`).
- **Tokens IoT** (`TokensRelationManager`): lista solo los tokens de ESE
  dispositivo (nombre `device:{id}`), permite *emitir* uno nuevo ligado al
  dispositivo y *revocar* los existentes. El listado global de todos los tokens
  sigue disponible en el recurso **API Tokens** (grupo *Sistema*).

Además, la ficha de edición incluye una sección **"Stats de hardware"**
(colapsada, solo lectura) al final del formulario que muestra el último estado
conocido reportado por la API: `temp`, `voltage`, `battery_level`, `cpu`,
`disk`, `uptime`, `ip_local`, `ip_public` y `extra` (JSON formateado).

### Widget del dashboard — Estado de dispositivos

`DeviceStatusWidget` (`app/Filament/Admin/Widgets/DeviceStatusWidget.php`,
vista `resources/views/filament/admin/widgets/device-status-widget.blade.php`)
se muestra en la página principal del panel Admin ocupando **todo el ancho**.
Lista cada dispositivo que reporta al menos un valor no nulo de `temp`,
`voltage`, `cpu` o `disk`, mostrando su nombre a la izquierda y, a la derecha,
un *chip* con icono por cada métrica no nula entre `temp`, `voltage`, `cpu`,
`disk`, `uptime` y `battery_level`. Se refresca cada 60 s.

## Impresoras

El submódulo de impresoras (modelos `Printer`, `PrinterStack`,
`PrinterAvailableType`) se documenta aparte en [printers.md](printers.md). Su
Resource Filament aparece bajo el grupo de navegación **Hardware**.

