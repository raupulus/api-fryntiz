# Módulo: Sistema de Afiliados y Referidos (Referred)

> **Módulo:** referred  
> **Estado:** Operativo en backend y panel Filament  

---

## 1. Resumen del Módulo

El módulo **Referred** gestiona los programas de afiliación (Amazon, AliExpress, PcComponentes, etc.) y los enlaces de compra asociados a dispositivos hardware (`HardwareDevice`) y a sus componentes específicos (`HardwareComponent`).

Permite a cada usuario que registra su propio hardware administrar uno o múltiples enlaces de compra de afiliados tanto para el aparato principal como para cada uno de los componentes instalados en él. En un futuro, este sistema alimentará automáticamente las cajas de compra de materiales ("Dónde comprar componentes") en los tutoriales y artículos del CMS que hagan referencia al hardware.

---

## 2. Archivos Principales Involucrados

| Tipo de Componente | Ruta del Archivo | Descripción de Responsabilidad |
|---|---|---|
| **Modelo Eloquent** | `app/Models/Referred/ReferredPlatform.php` | Catálogo de plataformas de afiliación disponibles (Amazon, AliExpress...). |
| **Modelo Eloquent** | `app/Models/Referred/ReferredThing.php` | Enlace de compra afiliado asociado a un dispositivo o componente. |
| **Migración** | `database/migrations/2026_09_18_000001_update_referred_system_for_hardware_and_components.php` | Reestructuración de `referred_things` y retirada de `referred_thing_id` en hardware. |
| **Factory** | `database/factories/Referred/ReferredPlatformFactory.php` | Factoría de pruebas para plataformas de afiliados. |
| **Factory** | `database/factories/Referred/ReferredThingFactory.php` | Factoría de pruebas para enlaces de afiliados con estados. |
| **Seeder** | `database/seeders/ReferredPlatformsSeeder.php` | Siembra del catálogo básico: Amazon, AliExpress y PcComponentes. |
| **Policy** | `app/Policies/AdminCatalogPolicy.php` | Autorización para la gestión del catálogo de plataformas (solo administradores). |
| **Policy** | `app/Policies/ReferredThingPolicy.php` | Autorización de enlaces de compra (dueño del hardware o administradores). |
| **Filament RelationManager** | `app/Filament/Admin/Resources/Hardware/HardwareDevices/RelationManagers/AffiliateLinksRelationManager.php` | Pestaña en la ficha de `HardwareDevice` para administrar todos sus enlaces de compra. |
| **Recurso Filament** | `app/Filament/Admin/Resources/Referred/ReferredPlatforms/ReferredPlatformResource.php` | Catálogo administrativo de plataformas de afiliación. |
| **Tests de Feature** | `tests/Feature/Referred/ReferredHardwareAffiliatesTest.php` | Suite de pruebas de integración para persistencia, cascada, permisos y panel. |

---

## 3. Esquema de Base de Datos

### Tabla: `referred_platforms` (Catálogo de Programas de Afiliación)

| Campo | Tipo | Nullable | Valor por Defecto | Descripción |
|---|---|:---:|---|---|
| `id` | `BIGSERIAL` | No | Auto | Identificador único de la plataforma |
| `image_id` | `BIGINT` | Sí | `null` | FK a `files.id` para el logotipo de la plataforma |
| `name` | `VARCHAR(255)` | No | — | Nombre de la plataforma (ej: Amazon, AliExpress) |
| `slug` | `VARCHAR(255)` | No | — | Slug único para identificación |
| `description` | `TEXT` | Sí | `null` | Descripción del programa de afiliados |
| `url` | `VARCHAR(255)` | Sí | `null` | URL principal de la plataforma |
| `url_panel` | `VARCHAR(255)` | Sí | `null` | Enlace al panel de control de afiliados |
| `url_register` | `VARCHAR(255)` | Sí | `null` | Enlace a la página de registro del programa |
| `created_at` | `TIMESTAMP` | Sí | `CURRENT_TIMESTAMP` | Marca temporal de creación |
| `updated_at` | `TIMESTAMP` | Sí | `CURRENT_TIMESTAMP` | Marca temporal de actualización |
| `deleted_at` | `TIMESTAMP` | Sí | `null` | Borrado lógico (`SoftDeletes`) |

### Tabla: `referred_things` (Enlaces de Compra de Afiliados)

| Campo | Tipo | Nullable | Valor por Defecto | Descripción |
|---|---|:---:|---|---|
| `id` | `BIGSERIAL` | No | Auto | Identificador único del enlace |
| `referred_platform_id` | `BIGINT` | No | — | FK a `referred_platforms.id` (`CASCADE`) |
| `hardware_device_id` | `BIGINT` | No | — | FK a `hardware_devices.id` (`CASCADE`) |
| `hardware_component_id` | `BIGINT` | Sí | `null` | FK a `hardware_components.id` (`CASCADE`) — si es `null`, aplica al dispositivo completo |
| `image_id` | `BIGINT` | Sí | `null` | FK a `files.id` (`SET NULL`) para imagen específica del producto |
| `name` | `VARCHAR(255)` | Sí | `null` | Título o nota opcional (ej: "Pack 2x con disipador") |
| `description` | `TEXT` | Sí | `null` | Descripción adicional de la oferta |
| `url` | `TEXT` | No | — | URL completa de afiliado con parámetros de tracking |
| `price` | `DECIMAL(10,2)` | Sí | `null` | Precio orientativo |
| `currency` | `VARCHAR(3)` | No | `'EUR'` | Código ISO de moneda |
| `is_active` | `BOOLEAN` | No | `true` | Si el enlace se encuentra activo |
| `created_at` | `TIMESTAMP` | Sí | `CURRENT_TIMESTAMP` | Marca temporal de creación |
| `updated_at` | `TIMESTAMP` | Sí | `CURRENT_TIMESTAMP` | Marca temporal de actualización |
| `deleted_at` | `TIMESTAMP` | Sí | `null` | Borrado lógico (`SoftDeletes`) |

---

## 4. Relaciones y Lógica de Negocio

### Relaciones Eloquent

- **`HardwareDevice`**:
  - `affiliateLinks()`: `HasMany<ReferredThing>` — todos los enlaces asociados al proyecto hardware (aparato principal y componentes).
  - `deviceAffiliateLinks()`: `HasMany<ReferredThing>` — enlaces exclusivos del dispositivo completo (`whereNull('hardware_component_id')`).
- **`HardwareComponent`**:
  - `affiliateLinks()`: `HasMany<ReferredThing>` — enlaces asociados específicamente a ese componente instalado.
- **`ReferredThing`**:
  - `platform()`: `BelongsTo<ReferredPlatform>`.
  - `device()`: `BelongsTo<HardwareDevice>`.
  - `component()`: `BelongsTo<HardwareComponent>`.
  - `image()`: `BelongsTo<File>`.

### Scopes y Accesores

- `ReferredThing::query()->active()`: Filtra sólo los enlaces activos.
- `ReferredThing::query()->forDeviceOnly()`: Filtra enlaces del dispositivo completo.
- `ReferredThing::query()->forComponents()`: Filtra enlaces que apuntan a componentes específicos.
- `$thing->target_label`: Devuelve *"Dispositivo completo"* o el nombre del componente específico (`$component->name`).
- `$thing->formatted_price`: Devuelve el precio formateado (ej. *"12,99 EUR"*).

---

## 5. Panel de Administración (Filament 5)

1. **Gestión desde el Dispositivo Hardware (`HardwareDeviceResource`):**
   - El RelationManager `AffiliateLinksRelationManager` se muestra en las pestañas del recurso `HardwareDevice`.
   - Al crear un enlace, el selector *"Aplica a"* permite elegir entre el *"Dispositivo completo (Hardware principal)"* o cualquiera de sus componentes registrados dinámicamente.
   - La tabla muestra la plataforma, el destino (con distintivo azul si es componente o verde si es el dispositivo), precio, enlace externo clicable y conmutador de estado activo.
2. **Catálogo de Plataformas (`ReferredPlatformResource`):**
   - Accesible para administradores en el grupo de navegación *"Configuración"*.
   - Permite dar de alta y editar las plataformas comerciales participantes.

---

## 6. Pruebas y Validación

La suite `tests/Feature/Referred/ReferredHardwareAffiliatesTest.php` valida:
- Creación de enlaces para el dispositivo completo y para componentes.
- Discriminación en las relaciones `affiliateLinks`, `deviceAffiliateLinks` y `component->affiliateLinks`.
- Borrado en cascada (al eliminar un componente se eliminan sus enlaces; al eliminar el dispositivo se eliminan todos sus enlaces).
- Políticas de autorización (un usuario no puede modificar enlaces del hardware de otro usuario; administradores y dueños sí).
- Renderizado y creación en Filament RelationManager y acceso restringido al catálogo de plataformas.

---

> Creado: 2026-09-18 · Última revisión: 2026-09-18
