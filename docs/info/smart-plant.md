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
| `app/Filament/Concerns/ScopesToOwner.php` | Usado por `SmartPlantPlantResource`: la tabla sólo muestra las plantas propias; el administrador las ve todas |

### `SmartPlantRegisterResource` es de sólo lectura (2026-09-11)

Los registros los sube el propio dispositivo IoT: no tiene sentido crearlos ni
editarlos a mano desde el panel, ni siquiera como admin o superadmin —editar
una lectura falsearía el histórico sin dejar rastro. `SmartPlantRegisterPolicy`
deniega `create()`/`update()` incondicionalmente (no según el rol), y el
recurso no declara páginas `create`/`edit` en `getPages()`. Lo único que se
puede hacer desde el panel es **ver, filtrar** (por planta y por dispositivo)
y **borrar** —para limpiar las lecturas de una prueba con un dispositivo—,
esto último restringido a admin y superadmin como el resto del módulo.

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

| Método | Ruta | Auth | Throttle | Qué hace |
|--------|------|------|----------|----------|
| GET | `/api/v2/smartplant/plants` | `ability:smartplant:write` | — | Listar plantas |
| GET | `/api/v2/smartplant/plants/{plant}/readings` | `ability:smartplant:write` | — | Lecturas de una planta |
| POST | `/api/v2/smartplant/plants/{plant}/readings` | `ability:smartplant:write` | `api-store` | Registrar una lectura |

Era `POST /smartplant/register`, un verbo. El recurso es la **lectura de una
planta**, y por eso ahora cuelga de ella (fase 5).

El `POST` admite además una clave opcional `hardware_device_info` con el
último estado del propio dispositivo (batería, temperatura, uptime...), igual
que `/energy/readings` y `/energy/solar-readings` (mismo trait
`HandlesHardwareDeviceInfo`). Contrato completo en
[`docs/info/hardware.md`](hardware.md) y en
[`docs/info/api/v2/smart-plant.md`](api/v2/smart-plant.md).

> **Cómo leer la columna «Auth».** Un `ability:` **no** es «hace falta estar
> autenticado»: es «hace falta un token **con esa ability concreta**». Un token
> de otro cacharro está autenticado y aquí no entra. Poner «Sí» a secas —que es
> lo que ponía antes esta tabla— borra justo esa diferencia, que es toda la que
> queda si alguien roba el token de un sensor (**N263**).


## Rutas Web

| Ruta | Descripción |
|------|-------------|
| `/smartplant` | Dashboard público de plantas |

### Frontend (Fix 5)

- **Eager loading:** El controlador web carga las plantas con `with(['registers' => fn($q) => $q->latest()->take(10)])` para limitar a las 10 últimas lecturas por planta.
- **Tarjetas de planta:** Cada planta muestra nombre, descripción y última lectura (humedad tierra, temperatura, humedad aire, luz).

### Perfil de planta: `description` y `details` admiten HTML básico

Los dos campos se escriben desde la intranet como texto con marcado (`<p>`,
`<strong>`, `<br>`...) y se pintan con `@safeHtml()`
(`App\Helpers\HtmlHelper::safeBasic()`), que sanea con `symfony/html-sanitizer`
y deja pasar sólo una lista blanca de etiquetas de formato — nunca `{!! !!}`
a secas sobre un campo que rellena alguien con acceso al panel. Ver
`docs/info/files.md` para el detalle del saneador (es el mismo que usa
`SmartPlantPlant::description`).

`details` es donde de verdad se usa el marcado (secciones «Origen»,
«Ecología»... cada una en su `<p>` con un `<strong>` de cabecera). Hasta el
2026-09-11 se partía a mano por líneas en blanco con `{{ }}` —una convención
de cuando el campo era texto plano—, así que en cuanto alguien empezó a
escribir HTML de verdad, las etiquetas salían escapadas y se leían tal cual:
«&lt;p&gt;».

### Perfil de planta: mínimos/máximos por sensor

`SmartPlantController::buildStatCards()` calcula, para cada sensor que la
planta tenga con algún dato (`soil_humidity` es el único obligatorio en el
hardware; `temperature`, `humidity`, `pressure` y `uv` dependen del kit
instalado), el mínimo y el máximo de **hoy**, **esta semana** (desde el lunes)
y **este mes** (desde el día 1). Se calcula con `MIN()`/`MAX()` agregados sobre
toda la tabla —filtrados por `created_at >=` el inicio de cada ventana—, no
sobre las últimas 50 lecturas que ya carga la vista: no bastarían para cubrir
un mes según la cadencia de subida del dispositivo.

### Comando de debug

```bash
php artisan debug:seed-smartplant --plants=5 --registers=50
```

---

> Creado: 2026-05-25 · Última revisión: 2026-09-11
