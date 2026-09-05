# Paneles Filament

Inventario real de los dos paneles de administración construidos con Filament 5 (Livewire 4).

## Resumen

| Panel | ID | Ruta | Acceso | Estado |
|-------|----|------|--------|--------|
| Admin | `admin` | `/admin` | Admin y SuperAdmin (`User::isAdmin()`) | ✅ Operativo, ~70 % completo |
| Tenant | `tenant` | `/panel` | ⚠️ Cualquier usuario autenticado | ✅ En marcha — 3 páginas y 1 Resource (tokens de API) |

Providers: `app/Providers/Filament/AdminPanelProvider.php` y `TenantPanelProvider.php`.

---

## Panel Admin (`/admin`)

### Configuración

| Opción | Valor |
|--------|-------|
| Panel por defecto | Sí (`->default()`) |
| Login | `App\Filament\Admin\Pages\Login` (custom, con reCAPTCHA v3 — ver [auth.md](auth.md)) |
| Tema | Oscuro por defecto (`ThemeMode::Dark`) |
| Fuente | Figtree |
| Paleta | primary `Sky`, gray `Zinc`, danger `Rose`, info `Blue`, success `Emerald`, warning `Orange` |
| CSS | `resources/css/filament/admin/theme.css` |
| Grupos de navegación | Sistema · Contenido · Hardware · Gestión · Módulos · Configuración · Documentación |
| Descubrimiento | Resources, Pages, Widgets y Clusters automáticos desde `app/Filament/Admin/` |
| Menú de usuario | Entrada "Editar perfil" → `App\Filament\Admin\Pages\Profile` |
| Navegación dinámica | Un ítem por cada `Platform` bajo el grupo Contenido, enlazando al listado de contenidos filtrado |
| Navegación estática extra | "Documentación de la API" → `route('scribe')` (`/docs`), se abre en pestaña nueva |
| Render hooks | `SCRIPTS_AFTER` para `@push('scripts')` y Editor.js en `EditContent`; `AUTH_LOGIN_FORM_BEFORE` para el script de reCAPTCHA v3 del login |

### Resources (24)

| Grupo | Resource | Modelo |
|-------|----------|--------|
| Sistema | `UserResource` | `User` |
| Sistema | `ApiTokenResource` | `ApiToken` |
| Contenido | `ContentResource` | `Content` |
| Contenido | `CategoryResource` | `Category` |
| Contenido | `TagResource` | `Tag` |
| Contenido | `TechnologyResource` | `Technology` |
| Contenido | `PlatformResource` | `Platform` |
| Contenido | `GalleryResource` | `Gallery` |
| Contenido | `FileTypeResource` | `FileType` |
| Gestión | `EmailResource` | `Email` (mensajes de contacto) |
| Gestión | `CurriculumResource` | `Curriculum` |
| Gestión | `CurriculumAvailableRepositoryTypeResource` | `CurriculumAvailableRepositoryType` |
| Hardware | `HardwareDeviceResource` | `HardwareDevice` |
| Hardware | `HardwareTypeResource` | `HardwareType` |
| Hardware | `HardwareAvailableComponentResource` | `HardwareAvailableComponent` |
| Hardware | `HardwareEnergyResource` | `HardwareEnergy` |
| Hardware | `EnergySystemResource` | `EnergySystem` |
| Hardware | `PrinterResource` | `Printer` |
| Módulos | `AirFlightAirPlaneResource` | `AirFlightAirPlane` |
| Módulos | `AirFlightRouteResource` | `AirFlightRoute` |
| Módulos | `KeyboardResource` | `Keyboard` |
| Módulos | `MouseResource` | `Mouse` |
| Módulos | `SmartPlantPlantResource` | `SmartPlantPlant` |
| Módulos | `SmartPlantRegisterResource` | `SmartPlantRegister` |

### RelationManagers (29)

| Resource padre | RelationManagers |
|----------------|------------------|
| `CurriculumResource` | 15 — AcademicComplementary, AcademicComplementaryOnline, AcademicTraining, Collaborations, ExperienceAccredited, ExperienceAdditional, ExperienceNoAccredited, ExperienceOther, ExperienceSelfEmployed, Hobbies, Jobs, Projects, Repositories, Services, Skills |
| `ContentResource` | 5 — Contributors, Files, Galleries, Pages, Related |
| `HardwareDeviceResource` | 2 — Components, Tokens |
| `HardwareEnergyResource` | 2 — PowerGenerators, PowerLoads |
| `AirFlightAirPlaneResource` | 1 — Routes |
| `GalleryResource` | 1 — Images |
| `PrinterResource` | 1 — PrinterStack |
| `SmartPlantPlantResource` | 1 — Registers |
| `UserResource` | 1 — Socials |

### Clusters (4)

`AirFlight` · `Energy` · `KeyCounter` · `SmartPlant`

### Páginas (5)

| Página | Descripción |
|--------|-------------|
| `Dashboard` | Panel principal. ⚠️ No declara `getWidgets()`; los widgets se cargan por descubrimiento |
| `AemetDashboard` | Datos de AEMET |
| `EnergyDashboard` | Métricas de energía solar |
| `Login` | Login custom del panel admin |
| `Profile` | Edición del perfil del usuario |

### Widgets (7)

| Widget | Muestra |
|--------|---------|
| `DashboardStats` | Estadísticas generales |
| `DeviceStatusWidget` | Estado de los dispositivos hardware |
| `EnergyStatsWidget` | Métricas de energía |
| `EnergyHistoricalChart` | Gráfica histórica de energía |
| `AemetMetricsWidget` | Métricas de AEMET |
| `Keyboard30DaysWidget` | Pulsaciones de teclado de 30 días |
| `Mouse30DaysWidget` | Actividad de ratón de 30 días |

### Componentes de formulario custom (3)

| Componente | Uso |
|------------|-----|
| `EditorJsField` | Editor de bloques Editor.js para páginas de contenido |
| `ImageCropperUpload` | Subida de imagen con recorte |
| `YoutubeVideoField` | Búsqueda e inserción de vídeos de YouTube (requiere `GOOGLE_API_KEY`) |

### Concerns y soporte

- `app/Filament/Concerns/HasImageFileUpload.php` — trait de subida de imágenes.
- `app/Filament/Concerns/HasRecaptchaLogin.php` — trait de reCAPTCHA v3 para el
  login, compartido con el panel Tenant (ver [auth.md](auth.md)).
- `app/Support/FilamentValidationRules.php` — reglas de validación reutilizables.

---

## Panel Tenant (`/panel`)

### Estado

```
app/Filament/Tenant/
├── Pages/Dashboard.php     ← título "Mi Panel"
├── Pages/Login.php         ← custom, sólo añade reCAPTCHA v3 (HasRecaptchaLogin)
├── Pages/EditProfile.php   ← edición de los datos propios
└── Resources/ApiTokens/    ← ApiTokenResource + Pages/{CreateApiToken,ListApiTokens}
```

El panel ya **no está vacío**: tiene 3 páginas y un Resource, con el que cada usuario gestiona sus
propios tokens de API sin pasar por el panel de administración. `Widgets/` no existe.

### Configuración

| Opción | Valor |
|--------|-------|
| Login | `App\Filament\Tenant\Pages\Login` (custom, con reCAPTCHA v3 — ver [auth.md](auth.md)) |
| Paleta | primary `Blue` |
| Grupos de navegación declarados | Dispositivos · Mi Cuenta · Documentación |
| Navegación estática | "Documentación de la API" → `route('scribe')` (`/docs`), se abre en pestaña nueva |

### Nota de seguridad

`User::canAccessPanel()` devuelve `true` para cualquier usuario autenticado en el panel `tenant`, así
que **el aislamiento lo pone cada Resource**, no la puerta de entrada.

`ApiTokenResource` ya lo hace: su `getEloquentQuery()` filtra por `tokenable_id`, de modo que cada
usuario ve únicamente sus propios tokens. **Todo Resource nuevo de este panel tiene que hacer lo
mismo**; si no filtra, expone los datos de todos los usuarios a cualquiera que entre.

(La advertencia anterior decía que el filtrado haría falta «en cuanto se añada el primer Resource».
Ese primero ya existe y filtra.)

---

## Modelos sin gestión en ningún panel

| Módulo | Modelos |
|--------|---------|
| Weather Station — sensores | `Temperature`, `Humidity`, `Pressure`, `Wind`, `WindDirection`, `Rain`, `Light`, `Lightning`, `Eco2`, `Tvoc`, `AirQuality`, `MeteorologyUvIndex`, `MeteorologyUva`, `MeteorologyUvb`, `MeteorologyResumeToday`, `MeteorologyResumeHistorical` |
| Weather Station — AEMET | Los 9 modelos de `Models/WeatherStation/AEMET/` |
| Newsletter | `Newsletter` |
| Hardware | `SolarCharge`, `HardwareComponent` |
| Catálogos | `Language`, `UserRole`, `PrinterAvailableType`, `ContentAvailable*` |
| Analítica | `ContentDailyView` |

Se abordan en la fase 05 del roadmap.

---

## Autorización

### La regla que hay que interiorizar

> **En Filament, un modelo sin policy no queda cerrado: queda ABIERTO.**

Si `Gate::getPolicyFor($modelo)` devuelve `null`, Filament entiende que no hay
restricciones a nivel de modelo y autoriza **todas** las acciones —`viewAny`,
`create`, `edit`, `delete`, `deleteAny`— a cualquiera que llegue al panel. Y a
`/admin` llega también el rol `Editor` (`User::canAccessPanel()`).

O sea: olvidarse de registrar una policy al añadir un recurso **no da un error,
da acceso**. Diez modelos estaban así hasta el 2026-09-05, `ApiToken` incluido:
un `Editor` podía listar los tokens de todos, emitirse uno a nombre de un
`SuperAdmin` y revocar en lote los del resto de administradores (AR-SEC-01).

Por eso hay **dos comprobaciones automáticas** que fallan si algún recurso del
panel administra un modelo sin policy:

- `php artisan project:check-config` — se ejecuta en el despliegue.
- `tests/Feature/Filament/PanelAuthorizationTest.php` — rompe la suite.

Añade la policy **a la vez** que el Resource, no después.

### Cómo se registran

Explícitamente, en `app/Providers/AuthServiceProvider.php`, y **no por
descubrimiento**. El descubrimiento por convención no funciona en este proyecto:
para `App\Models\Hardware\HardwareDevice`, Laravel busca
`App\Policies\Hardware\HardwareDevicePolicy`, mientras que aquí las policies
viven planas en `App\Policies\` con nombre de módulo (`HardwarePolicy`). Las
policies de todos los modelos que viven en subcarpetas no existían para el
framework.

El provider tiene tres mapas:

| Mapa | Para qué |
|---|---|
| `POLICIES` | Modelo → policy, uno a uno. |
| `WEATHER_STATION_MODELS` | Los 11 sensores comparten `WeatherStationPolicy`. |
| `CATALOG_MODELS` | Los 5 catálogos globales comparten `AdminCatalogPolicy`. |

### La jerarquía

| Rol | Alcance |
|---|---|
| `SuperAdmin` | Todo. Vía `Gate::before`, sin llegar a las policies. |
| `Admin` | Todo de todo el mundo, **menos lo de un `SuperAdmin`**. Ve y edita. |
| `User` · `Editor` | Sólo lo suyo. |

⚠️ **`Gate::before` sólo implementa el primer escalón.** Un `Admin` no recibe
nada de ese atajo, así que **cada policy tiene que contemplarlo explícitamente**
o el administrador se queda fuera de su propio panel: ve el listado y se lleva un
403 al abrir cualquier ficha ajena (AR-SEC-03).

⚠️ **Y el bypass de administrador es para administradores con sesión, nunca para
tokens de dispositivo.** El dueño de los cacharros es `SuperAdmin`, de modo que
un `|| $user->isAdmin()` sin condiciones convierte el token grabado en una placa
en una llave para los recursos de todo el mundo — justo el agujero que
`Gate::before` evita al devolver `null` en peticiones de dispositivo. El patrón
correcto está en `OwnedResourcePolicy::alcanza()`:

```php
if (TokenAbilities::deviceRequest($user)) {
    return false;              // o la regla estricta de dueño + device:{id}
}

return $esSuyo || $user->isAdmin();
```

Fijado por `PanelAuthorizationTest::el_token_de_un_cacharro_no_hereda_los_permisos_de_administrador`.

### Policies base reutilizables

En vez de una clase por tabla repitiendo el mismo criterio:

| Clase | Cubre | Criterio |
|---|---|---|
| `AdminCatalogPolicy` | `FileType`, `HardwareType`, `HardwareAvailableComponent`, `CurriculumAvailableRepositoryType`, `PrinterAvailableType` | Catálogos globales sin dueño: sólo administrador. |
| `OwnedResourcePolicy` (abstracta) | Base de `PrinterPolicy`, `GalleryPolicy`, `EnergySystemPolicy`, `HardwareEnergyPolicy`, `AirFlightRoutePolicy` | Es tuyo o eres administrador. Cada hija sólo declara `ownerId()`. |
| `ApiTokenPolicy` | `ApiToken` | Admin gestiona tokens salvo los de un `SuperAdmin`. |

Si el recurso nuevo es un catálogo o tiene `user_id`, **no escribas una policy
desde cero**: registra `AdminCatalogPolicy` o extiende `OwnedResourcePolicy`.

### Alcance de las tablas: `viewAny()` no basta

`viewAny()` decide si el recurso **existe** para ti. `view()` decide si puedes
abrir **una** fila. Pero **Filament no ejecuta `view()` fila a fila al pintar un
listado**: la tabla enseña, literalmente, lo que devuelva `getEloquentQuery()`.

Un recurso con `viewAny() === true` y sin scoping filtra cero. Un `Editor` veía
listados los currículums, teclados, ratones, plantas, dispositivos e impresoras
de **todos** los usuarios; podía no abrir la ficha, pero la columna ya contaba el
nombre del cacharro, las horas de actividad y de quién era cada cosa (AR-SEC-02).

Para eso está el trait `app/Filament/Concerns/ScopesToOwner.php`:

```php
class PrinterResource extends Resource
{
    use ScopesToOwner;   // filtra por `user_id`; el administrador ve todo
}
```

Si la propiedad cuelga de una relación en vez de una columna propia, sobrescribe
`scopeOwnerQuery()` — ver `HardwareEnergyResource`, cuyo dueño está en el sistema
energético.

Aplicado en: `CurriculumResource`, `KeyboardResource`, `MouseResource`,
`HardwareDeviceResource`, `SmartPlantPlantResource`, `PrinterResource`,
`GalleryResource`, `EnergySystemResource`, `HardwareEnergyResource`,
`AirFlightRouteResource` y —con su propia consulta— `ApiTokenResource`.

### Widgets, páginas y clusters

Se descubren solos y se **muestran** salvo que digan lo contrario. No hay policy
que los cubra: la restricción es un método en la propia clase.

| Componente | Método |
|---|---|
| Widget | `public static function canView(): bool` |
| Page · Cluster | `public static function canAccess(): bool` |

Sin ellos, un `Editor` veía en su escritorio la telemetría de los servidores
—nombres de nodos, CPU, disco, tensión, uptime—, los consumos eléctricos y los
enlaces de navegación a los clusters de infraestructura (AR-SEC-04). Los 7
widgets, los 4 clusters y `EnergyDashboard` exigen ahora administrador.

### Otros gates

`manage-settings` y `view-statistics`, definidos en `AppServiceProvider`.

`access-admin-panel` **ya no existe**: decía que sólo `SuperAdmin` entra al
panel, no lo consultaba nadie y contradecía a la implementación real. Filament
usa el contrato `FilamentUser::canAccessPanel()` de `App\Models\User`, que abre
`/admin` también a `Admin` y a `Editor`. Un gate huérfano que contradice al
código es peor que ninguno, porque quien audita los providers da por cerrado lo
que está abierto (AR-CODE-02). **El criterio de acceso al panel está, y sólo
está, en `User::canAccessPanel()`.**

- `UserPolicy` protege a los SuperAdmins de ser editados por Admins (commit `66c41b1`).

### Reparto de roles: nadie sube por encima del suyo

Dos reglas que hay que tener presentes al tocar `UserResource` o `UserPolicy`,
porque su ausencia era una **escalada de privilegios real** (auditoría AR-P01,
reproducida antes de arreglarla):

1. **El propio registro no se edita desde el recurso de usuarios.**
   `UserPolicy::update()` devuelve `false` cuando el sujeto y el objeto son el
   mismo usuario. El autoservicio va por «Editar perfil»
   (`Admin\Pages\Profile` y `Tenant\Pages\EditProfile`, ambos sobre el trait
   `EditsOwnProfile`), que **no expone `role_id` ni `is_active`**. Es la misma
   filosofía que D91 con el email.

   Un SuperAdmin sí puede editarse desde el recurso, pero no por la policy:
   `Gate::before` le concede todo antes de evaluarla. En su caso no hay nada que
   escalar.

2. **El `Select` de rol sólo ofrece lo que quien edita puede repartir.** El
   criterio está en `UserRoleEnum::assignableRoles()`:

   | Quien edita | Puede asignar |
   |---|---|
   | SuperAdmin | SuperAdmin · Admin · User · Editor |
   | Admin | Admin · User · Editor |
   | Editor · User | nada |

   Se aplica **en dos capas**: el `modifyQueryUsing` de la relación acota lo que
   se pinta, y una regla `Rule::in()` acota lo que se acepta al guardar. Las dos,
   porque filtrar sólo las opciones se salta cambiando el `<select>` desde las
   herramientas del navegador.

   Sin la segunda regla, un Admin podía **crear** un SuperAdmin con una
   contraseña elegida por él y entrar después con esa cuenta. Cerrar sólo la
   edición del propio registro no habría bastado.

Ambas están fijadas por `tests/Feature/Filament/RoleEscalationTest.php` y
`tests/Unit/Policies/UserPolicyTest.php`.

## Comandos relacionados

```bash
php artisan filament:upgrade          # tras actualizar Filament
php artisan make:filament-resource    # nuevo Resource
php artisan make:filament-cluster     # nuevo Cluster
php artisan make:filament-widget      # nuevo Widget
```

## Tests

- `tests/Feature/Filament/PanelAuthorizationTest.php` — **cobertura de policies,
  alcance de las tablas y visibilidad de widgets y clusters.** Es el que rompe si
  se añade un recurso sin policy.
- `tests/Feature/Filament/RoleEscalationTest.php` — nadie se sube de rol.
- `tests/Feature/Filament/HardwareDeviceSelectOptionsTest.php`
- `tests/Feature/Filament/RecaptchaLoginTest.php` — reCAPTCHA v3 en los dos logins

Cobertura parcial; el resto queda pendiente en la fase 09 del roadmap.

---

> Creado: 2026-08-30 · Última revisión: 2026-09-05
