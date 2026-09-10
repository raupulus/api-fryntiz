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

3. **Sobre un `SuperAdmin`, el `Select` de rol se pinta deshabilitado.** Quien
   no sea `SuperAdmin` ni siquiera llega al formulario —`UserPolicy::update()`
   responde 403—, pero si llegara, el campo no debe aparecer manipulable:
   `UserResource::esIntocable()` lo deshabilita y le pone `dehydrated(false)`,
   así que el valor tampoco viaja en el guardado. Una interfaz que ofrece lo que
   luego rechaza es una interfaz que miente.

4. **`restore()` y las acciones masivas también miran a quién se está tocando.**
   `UserPolicy::restore()` era el único método de la clase que no comprobaba el
   rol del registro: devolvía `isAdmin()` a secas, y la tabla ofrece
   `RestoreAction`, así que un `Admin` podía devolverle el acceso a un
   `SuperAdmin` borrado. Y `deleteAny()`, `forceDeleteAny()` y `restoreAny()`
   —los métodos contra los que Filament resuelve las acciones en bloque— no
   estaban declarados, de modo que borrar usuarios en masa dependía de un
   comportamiento implícito del framework. Ahora los tres exigen `SuperAdmin`, y
   además cada registro sigue pasando por `delete()`, `forceDelete()` o
   `restore()`.

Todas están fijadas por `tests/Feature/Filament/RoleEscalationTest.php` y
`tests/Unit/Policies/UserPolicyTest.php`.


## Cambios del panel (2026-09-06)

- **Dispositivos de hardware:** fuera del formulario el campo «ID componente
  asociado». Era `referred_thing_id`, la clave foránea a `referred_things` —los
  productos de programas de afiliación—, así que ni la etiqueta ni el sitio eran
  los suyos. **La columna sigue en la base**; sólo se retiró del formulario.
- **Uptime legible:** el campo sigue guardando y mostrando segundos, que es lo
  que manda el dispositivo, pero debajo lleva la lectura en unidades («3 meses,
  12 días»). Se queda en las dos unidades más grandes que apliquen, para no
  alargar la línea. El helper es `HardwareDeviceResource::uptimeLegible()`.
- **Uso de memoria:** campo `ram` (0-100 %) junto a CPU y disco, y una métrica
  más en `DeviceStatusWidget`.
- **Teclados y ratones:** ordenan por `start_at` descendente al entrar; lo último
  subido es lo que se quiere ver.
- **Página de AEMET:** las fechas de última ejecución se pintan en el huso de
  visualización (`app.display_timezone`), no en UTC. `FilamentTimezone` alcanza a
  las columnas y las infolists del panel, **pero no a una vista Blade propia**
  como la de esa página, así que ahí la conversión va explícita. La base sigue
  guardando en UTC.

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

> Creado: 2026-08-30 · Última revisión: 2026-09-10


## Imágenes: por qué el uploader no enseña la que ya hay

**Qué se veía.** En cualquier ficha del panel que tenga imagen —una tecnología,
un dispositivo de hardware, una plataforma, un contenido…— al abrirla para
editarla el recuadro de la imagen aparecía **vacío**, como si el registro no
tuviera ninguna, aunque en la web pública sí se viera. Se notó al traer las
imágenes de la v1 al `storage`, porque parecía que el problema era ése.

**De dónde viene.** Del propio panel, desde que se generó
(`97d8c65`, 2026-05-28, «generate CRUD for all módules admin backend»). El
patrón se copió a cada recurso nuevo según se iban añadiendo (`5baeb90`,
`ce976d8`), así que **el panel nunca ha enseñado una imagen ya guardada**: no es
una regresión ni tiene nada que ver con el `storage` ni con la migración de
datos.

**Por qué pasaba.**

Los recursos suben imágenes con `ImageCropperUpload::makeImage('image_id')`, es
decir, un `FileUpload` de Filament apuntando a una **clave foránea** a la tabla
`files`. Un `FileUpload` espera una ruta dentro de un disco, y la URL real de
una imagen de este proyecto no lo es: `File::getUrlAttribute()` devuelve una
ruta de controlador (`route('file.get', …)`), que además es la única que sirve
para los ficheros privados.

El resultado era que el estado del campo llegaba siendo el id numérico y el
componente intentaba pintar «4» como si fuera una ruta, así que cada página de
edición acabó haciendo `unset($data['image_id'])` en
`mutateFormDataBeforeFill()`. Con eso **el panel dejó de enseñar la imagen
guardada**: sólo el hueco vacío para subir otra. En el frontend sí se veía,
porque allí se resuelve la relación (`$modelo->image->url`), y de ahí que
pareciera un problema de `storage`.

**El primer arreglo (`fc7845e`) tapaba el síntoma, no la causa.** Añadió
`CurrentImage::fromRelation()`, un componente aparte que pintaba la imagen
guardada **encima** del `FileUpload` —que seguía vacío— resolviendo la relación
como en el frontend. Con eso la imagen volvía a **verse**, pero el campo de
subida seguía sin saber nada de ella: no se podía recortar la imagen actual, no
se podía quitar con el botón nativo de Filament (el propio `unset()` de arriba
seguía ahí, y `HasImageFileUpload::resolveImageUpload()` ignoraba un campo vacío
para no desvincular la FK por accidente), y guardar sin tocar el campo dejaba la
sensación de que no había pasado nada, porque la miniatura de `CurrentImage` no
cambiaba nunca. Es justo lo que reportó el usuario: «debería estar cargada en el
propio widget de imagen para también poderse editar».

**El arreglo de verdad (2026-09-10) es que el propio campo resuelva la vista
previa**, con el hook que Filament ya trae para esto —
`BaseFileUpload::getUploadedFileUsing()`— en vez de un componente aparte que
sólo lee. `ImageCropperUpload::asFileRecord()` encadena dos cosas:

```php
ImageCropperUpload::makeImage('image_id')
    ->asFileRecord()
    ->storeFiles(false)
    // …
```

- `fetchFileInformation(false)`: sin esto, `BaseFileUpload::hydrateFiles()`
  comprueba `Storage::disk(...)->exists($id)` para cada valor del estado antes
  de enseñarlo — y un id numérico nunca existe como ruta en el disco, así que
  Filament lo **descartaba en silencio** antes incluso de llegar a pintar nada.
- `getUploadedFileUsing()`: el hook que FilePond llama (vía el método expuesto
  `getUploadedFiles()`) para saber qué ya está subido. Se resuelve a mano desde
  el modelo `File` — nombre, tamaño, tipo y la URL de `route('file.get', …)` —
  en vez de que Filament intente leerlo del disco.

Con las dos cosas, el campo **es** la imagen actual: la enseña, deja abrir el
editor de recorte sobre ella (el lápiz que Filament ya pinta en toda imagen
cargada) y quitarla con su botón nativo. Ya no hace falta `CurrentImage` —se ha
retirado— ni el `unset($data['image_id'])` de cada `mutateFormDataBeforeFill()`:
el id tiene que llegar al campo para que lo resuelva.

Con el campo enseñando de verdad la imagen actual, un estado vacío deja de ser
ambiguo: antes podía significar tanto «nunca se ha rellenado» como «se acaba de
quitar», así que `resolveImageUpload()` lo dejaba tal cual para no arriesgarse a
desvincular la FK sin querer. Ahora sólo puede significar lo segundo, así que
pone la FK a `null`.

Lo que **no** hay que hacer es hidratar el `FileUpload` con la ruta relativa del
disco: funcionaría para los ficheros públicos y dejaría fuera a los privados.

Recursos afectados (10): `PlatformResource`, `TechnologyResource`,
`ContentResource`, `HardwareDeviceResource`, `CurriculumResource`,
`CurriculumAvailableRepositoryTypeResource`, `GalleryResource`,
`CategoryResource`, `ImagesRelationManager` y `PagesRelationManager`.
`FileTypeResource` (`icon16`…`icon128`) y las fotos de perfil de usuario no lo
necesitan: ésos sí son columnas de ruta, no una FK a `files`, y a Filament ya le
basta con lo suyo.

Fijado por `tests/Feature/Filament/ExistingImagePreloadTest.php` (antes
`CurrentImageTest.php`), que comprueba: que el id llega al estado del campo al
editar, que `getUploadedFiles()` resuelve una vista previa real (nombre, tamaño,
URL) a través de `callSchemaComponentMethod()`, que guardar sin tocar el campo
**no** desvincula la FK, y que quitar la imagen con el campo vacío **sí** la
desvincula.

### Checklist al añadir un campo de imagen nuevo

Esto tardó varias vueltas (reporte del usuario → primer arreglo que sólo
tapaba el síntoma → glitch visual al reemplazar → arreglo de verdad) porque
cada paso se investigó desde cero. Antes de tocar un `FileUpload` en el panel,
mira aquí primero.

1. **¿Qué guarda la columna?** Es lo único que importa para decidir el patrón:
   - **Un id que apunta a `files`** (una FK, típicamente `image_id`) → patrón
     "FK": usa `ImageCropperUpload::makeImage('image_id')->asFileRecord()` +
     `->storeFiles(false)`, y en la Page (Create y Edit) el trait
     `HasImageFileUpload::resolveImageUpload($data, 'image_id', 'directorio')`
     en `mutateFormDataBeforeSave()`. **No** añadas
     `mutateFormDataBeforeFill()` para quitar el id: sin `asFileRecord()` el
     campo no sabe pintarlo, pero con él tiene que llegar.
   - **Una ruta de disco en la propia columna** (`profile_photo_path`,
     `icon16`…`icon128`) → patrón "disco": `ImageCropperUpload::makeImage(...)`
     a secas ya funciona, Filament resuelve la ruta contra el disco sin nada
     más. No necesita `asFileRecord()` ni el trait.
   - Si dudas cuál es, mira la migración de la columna (`bigint`/FK vs
     `string`), no el nombre del campo.
2. **No mezcles los dos patrones en el mismo campo.** `asFileRecord()` en un
   campo de ruta de disco (o al revés) es la fuente más probable de un campo
   que se enseña vacío o que no guarda.
3. **Prueba con `callSchemaComponentMethod()`, no sólo mirando el estado.** Un
   test con Livewire que sólo comprueba `assertSet('data.campo', $valor)` no
   detecta que FilePond (JavaScript) sea incapaz de resolver la vista previa:
   el estado puede llegar bien y el campo seguir vacío en el navegador. Hay
   que llamar al método que FilePond invoca de verdad:
   ```php
   $component = Livewire::test(EditX::class, ['record' => $x->getKey()])->instance();
   $preview = $component->callSchemaComponentMethod('form.<campo>', 'getUploadedFiles');
   ```
   La clave es `'form.<nombre_del_campo>'` en una página de edición estándar.
   Ver `ExistingImagePreloadTest.php` para el patrón completo (llega el id,
   resuelve vista previa real, guardar sin tocar no desvincula, quitar sí
   desvincula).
4. **`->maxFiles(1)` en todo campo no `multiple()`.** Sin esto, si el navegador
   añade el archivo nuevo antes de que la retirada del anterior termine de
   sincronizarse con el servidor, el campo llega a tener dos elementos a la
   vez y se ve como si la imagen nueva se superpusiera a la vieja en vez de
   reemplazarla. Ya está en `ImageCropperUpload::makeImage()`, así que sólo
   hace falta pensar en esto si se usa `FileUpload` fuera de ese componente.
5. **Repite el mismo cambio en los dos paneles si aplica.** Este proyecto tiene
   panel `Admin` y panel `Tenant`; un campo de imagen que viva en ambos (como
   la foto de perfil, vía `EditsOwnProfile`) sólo necesita un sitio porque el
   trait ya está compartido — pero si se duplica el formulario en vez de
   compartirlo, hay que arreglarlo en los dos.

## Energía: qué se gestiona en cada pantalla

Un elemento de `hardware_energy` se edita **en una pantalla y sólo en una**. Si
saliera en dos, editarlo en una y mirarlo en la otra daría respuestas distintas.

| Pantalla | Qué gestiona | Criterio |
|---|---|---|
| Ficha del dispositivo · pestaña **Energía** | Los papeles de **ese** aparato, midiéndose a sí mismo | Alta rápida: el papel lo pone el botón y el monitorizado se rellena solo |
| **Instalaciones** · pestaña *Elementos del controlador* | Lo que mide el **controlador solar** de la instalación | El monitor es de tipo `controlador-solar` |
| **Elementos de Energía** | Todo lo demás: monitores de energía y cargas sueltas | El monitor **no** es de tipo `controlador-solar` |

El reparto lo decide el **tipo del dispositivo que mide**
(`hardware_types.slug`), no la fuente de energía: hoy los ocho elementos reales
tienen fuente «Fotovoltaica» y ésa no distingue nada.

`HardwareEnergyResource::getEloquentQuery()` es donde se aplica, **no**
`scopeOwnerQuery()`: el trait `ScopesToOwner` se salta ese método cuando quien
mira es administrador, y este filtro no es de propiedad sino de alcance. Hay un
test que comprueba que los dos listados no se solapan y que ningún elemento se
queda sin pantalla.

### Elementos de Energía se agrupa por el dispositivo monitor

Porque **es lo único que no cambia** entre las filas de un mismo medidor: el
aparato medido, la instalación, la fuente y el `is_active` son de cada canal.
Una Raspberry con un INA puede llevar la batería de 12 V a un ventilador, la de
litio a una lámpara y el cargador de red a un microcontrolador.

### El formulario vive en un sitio

`HardwareEnergyForm` tiene los campos y sus ayudas, y lo usan las tres
pantallas: `deUnDispositivo()` para la ficha —sin preguntar medidor, medido ni
papel, que los pone el contexto— y `completo()` para las otras dos. Copiarlo
tres veces es la forma de que acaben diciendo cosas distintas.

### Dónde se crea cada papel

Hay **dos** sitios, y en los dos con los mismos tres botones, para que se busque
igual se venga de donde se venga:

| Desde | Pestaña | Qué crea |
|---|---|---|
| Ficha de un dispositivo | **Energía** | Papeles de ese aparato, midiéndose a sí mismo |
| Ficha de un elemento energético | **Papeles de este aparato** | Los otros papeles del **mismo medidor** |

El botón de generador y el de batería desaparecen cuando ya existe el suyo; el
de consumo se queda, porque un medidor mide tantas cargas como canales tenga.

⚠️ **Las pestañas «Lecturas de consumo» y «Lecturas de generación» son las
medidas que ha ido mandando el aparato, no sus papeles.** Se llamaban «Cargas de
energía» y «Generadores de energía», y con eso no había forma de dar de alta el
papel de batería: no existe una tabla de lecturas de batería, así que no había
tercera pestaña y parecía que ese papel no se podía crear. Renombradas para que
no se confundan con lo de arriba.

### «Energy» en las migas de pan

`/admin/energy` no es una página: su `mount()` redirige al **primer** elemento de
la subnavegación del clúster. `EnergyDashboard` y `EnergySystemResource` estaban
empatados en `navigationSort`, ganaba Instalaciones, y pulsar «Energy» llevaba
siempre allí — estando ya en esa pantalla, parecía que sólo se recargaba. El
resumen es la portada del módulo, así que va con `navigationSort = 0`.
