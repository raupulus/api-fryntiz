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
| `app/Http/Controllers/Api/Hardware/V1/*.php` | API V1 | Controladores V1 legacy |
| `app/Http/Controllers/Hardware/*.php` | Web | Controladores frontend (10 archivos) |

### Servicios
| Archivo | Descripción |
|---------|-------------|
| `app/Services/Hardware/HardwareService.php` | Lógica: info dispositivo, store energía/solar, lista computadores |

### Resources API V2
| Archivo | Descripción |
|---------|-------------|
| `app/Http/Resources/V2/Hardware/HardwareDeviceResource.php` | Resource dispositivo |
| `app/Http/Resources/V2/Hardware/EnergyMonitorResource.php` | Resource energía |
| `app/Http/Resources/V2/Hardware/SolarChargeResource.php` | Resource carga solar |

### FormRequests V2
| Archivo | Descripción |
|---------|-------------|
| `app/Http/Requests/Api/Hardware/V2/StoreEnergyRequest.php` | Validación store energía |
| `app/Http/Requests/Api/Hardware/V2/StoreSolarChargeRequest.php` | Validación store carga solar |

### Otros
| Archivo | Descripción |
|---------|-------------|
| `app/Policies/HardwarePolicy.php` | Política de autorización |
| `app/Enums/HardwareTypeEnum.php` | Enum tipos de hardware |
| `app/Traits/BelongsToHardwareDevice.php` | Trait relación con dispositivo hardware |

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
| `last_seen_at` | timestamp | Última conexión |
| `ip_local` | string | IP local |
| `ip_public` | string | IP pública |

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
- `HardwareEnergy` → `BelongsTo` → `HardwareDevice` (vía `hardware_device_id`)
- `HardwarePowerGenerator/Load` → `BelongsTo` → `HardwareDevice`

## Rutas API V2

| Método | Ruta | Auth | Throttle | Descripción |
|--------|------|------|----------|-------------|
| GET | `/api/v2/hardware/device/{id}` | Sí | api-store | Ver dispositivo |
| GET | `/api/v2/hardware/computers` | Sí | api-store | Listar computadores |
| POST | `/api/v2/hardware/energy` | Sí | api-store | Store datos energía |
| POST | `/api/v2/hardware/solar-charge` | Sí | api-store | Store carga solar |

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
# Crear dispositivos hardware sueltos
php artisan debug:seed-hardware --count=5

# Crear dispositivos y registros de energía solar (voltaje, corriente, potencia)
php artisan debug:seed-energy --devices=5 --records=100
```

