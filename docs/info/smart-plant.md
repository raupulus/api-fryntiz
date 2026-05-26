# Módulo: Plantas Inteligentes (SmartPlant)

Módulo IoT para monitorizar plantas mediante sensores de humedad del suelo, luz UV, temperatura, presión y controlar la bomba de agua y vaporizador automáticamente.

## Archivos principales

### Modelos
| Archivo | Tabla | Descripción |
|---------|-------|-------------|
| `app/Models/SmartPlant/SmartPlantPlant.php` | `smartplant_plants` | Planta registrada |
| `app/Models/SmartPlant/SmartPlantRegister.php` | `smartplant_registers` | Registro periódico de datos de sensores |

### Controladores
| Archivo | Versión | Descripción |
|---------|---------|-------------|
| `app/Http/Controllers/Api/SmartPlant/V2/SmartPlantRegisterController.php` | API V2 | Store registro de planta |
| `app/Http/Controllers/Api/SmartPlant/V1/SmartPlantController.php` | API V1 | Controlador V1 legacy |
| `app/Http/Controllers/Api/SmartPlant/V1/SmartPlantRegisterController.php` | API V1 | Store V1 legacy |
| `app/Http/Controllers/SmartPlant/SmartPlantController.php` | Web | Frontend público |

### Servicios
| Archivo | Descripción |
|---------|-------------|
| `app/Services/SmartPlant/SmartPlantService.php` | Lógica: store registro, consultas |

### Resources API V2
| Archivo | Descripción |
|---------|-------------|
| `app/Http/Resources/V2/SmartPlant/SmartPlantRegisterResource.php` | Resource JSON registro |

### FormRequests V2
| Archivo | Descripción |
|---------|-------------|
| `app/Http/Requests/Api/SmartPlant/V2/StoreRegisterRequest.php` | Validación store registro |

### Otros
| Archivo | Descripción |
|---------|-------------|
| `app/Policies/SmartPlantPolicy.php` | Política de autorización planta |
| `app/Policies/SmartPlantRegisterPolicy.php` | Política de autorización registro |

## Campos del modelo SmartPlantPlant

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | bigint | PK |
| `user_id` | int | FK → `users.id` — propietario |
| `hardware_device_id` | int | FK → `hardware_devices.id` — dispositivo sensor |
| `name` | string | Nombre de la planta |
| `name_scientific` | string | Nombre científico |
| `description` | text | Descripción |
| `details` | text | Detalles adicionales |
| `image` | string | Ruta de imagen |
| `start_at` | datetime | Fecha de inicio de monitorización |

## Campos del modelo SmartPlantRegister

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | bigint | PK |
| `plant_id` | int | FK → `smartplant_plants.id` — planta asociada |
| `hardware_device_id` | int | FK → `hardware_devices.id` — dispositivo |
| `uv` | decimal | Índice UV |
| `pressure` | decimal | Presión atmosférica |
| `temperature` | decimal | Temperatura ambiente |
| `humidity` | decimal | Humedad ambiental |
| `soil_humidity` | decimal | Humedad del suelo (requerido) |
| `soil_humidity_raw` | decimal | Humedad del suelo valor crudo |
| `full_water_tank` | boolean | ¿Tanque de agua lleno? |
| `waterpump_enabled` | boolean | ¿Bomba de agua activa? |
| `vaporizer_enabled` | boolean | ¿Vaporizador activo? |

## Relaciones

- `SmartPlantPlant` → `BelongsTo` → `User` (vía `user_id`)
- `SmartPlantPlant` → `BelongsTo` → `HardwareDevice` (vía `hardware_device_id`)
- `SmartPlantPlant` → `HasMany` → `SmartPlantRegister` (vía `plant_id`)
- `SmartPlantRegister` → `BelongsTo` → `SmartPlantPlant` (vía `plant_id`)
- `SmartPlantRegister` → `BelongsTo` → `HardwareDevice` (vía `hardware_device_id`)

## Rutas API V2

| Método | Ruta | Auth | Throttle | Descripción |
|--------|------|------|----------|-------------|
| POST | `/api/v2/smartplant/register` | Sí | api-store | Store registro de sensores |

## Rutas Web

| Ruta | Descripción |
|------|-------------|
| `/smartplant` | Dashboard público de plantas |

### Frontend (Fix 5)

- **Eager loading:** El controlador web carga las plantas con `with(['registers' => fn($q) => $q->latest()->take(10)])` para limitar a las 10 últimas lecturas por planta.
- **Tarjetas de planta:** Cada planta muestra nombre, descripción y última lectura (humedad tierra, temperatura, humedad aire, luz).

### Comando de debug

```bash
php artisan debug:seed-smartplant --plants=5 --registers=50
```
