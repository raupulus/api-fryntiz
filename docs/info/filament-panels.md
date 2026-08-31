# Paneles Filament

Inventario real de los dos paneles de administración construidos con Filament 5 (Livewire 4).

## Resumen

| Panel | ID | Ruta | Acceso | Estado |
|-------|----|------|--------|--------|
| Admin | `admin` | `/admin` | Admin y SuperAdmin (`User::isAdmin()`) | ✅ Operativo, ~70 % completo |
| Tenant | `tenant` | `/panel` | ⚠️ Cualquier usuario autenticado | ❌ **Vacío** — solo una página Dashboard |

Providers: `app/Providers/Filament/AdminPanelProvider.php` y `TenantPanelProvider.php`.

---

## Panel Admin (`/admin`)

### Configuración

| Opción | Valor |
|--------|-------|
| Panel por defecto | Sí (`->default()`) |
| Login | `App\Filament\Admin\Pages\Login` (custom) |
| Tema | Oscuro por defecto (`ThemeMode::Dark`) |
| Fuente | Figtree |
| Paleta | primary `Sky`, gray `Zinc`, danger `Rose`, info `Blue`, success `Emerald`, warning `Orange` |
| CSS | `resources/css/filament/admin/theme.css` |
| Grupos de navegación | Sistema · Contenido · Hardware · Gestión · Módulos · Configuración |
| Descubrimiento | Resources, Pages, Widgets y Clusters automáticos desde `app/Filament/Admin/` |
| Menú de usuario | Entrada "Editar perfil" → `App\Filament\Admin\Pages\Profile` |
| Navegación dinámica | Un ítem por cada `Platform` bajo el grupo Contenido, enlazando al listado de contenidos filtrado |
| Render hooks | `SCRIPTS_AFTER` para `@push('scripts')` y para cargar Editor.js en `EditContent` |

### Resources (23)

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
- `app/Support/FilamentValidationRules.php` — reglas de validación reutilizables.

---

## Panel Tenant (`/panel`)

### Estado

```
app/Filament/Tenant/
├── Pages/Dashboard.php     ← único fichero (14 líneas, título "Mi Panel")
├── Resources/              ← vacío
└── Widgets/                ← vacío
```

### Configuración

| Opción | Valor |
|--------|-------|
| Login | Genérico de Filament (`->login()`) |
| Paleta | primary `Blue` |
| Grupos de navegación declarados | Dispositivos · Mi Cuenta (sin contenido) |

### Advertencia de seguridad

`User::canAccessPanel()` devuelve `true` para cualquier usuario autenticado en el panel
`tenant`. Como no hay Resources, hoy no se filtra ninguna información, pero **en cuanto se
añada el primero habrá que implementar el filtrado por usuario** en `getEloquentQuery()`.

El futuro de este panel (construirlo o eliminarlo) se decide en la
fase 06 del roadmap.

---

## Modelos sin gestión en ningún panel

| Módulo | Modelos |
|--------|---------|
| Weather Station — sensores | `Temperature`, `Humidity`, `Pressure`, `Wind`, `WindDirection`, `Rain`, `Light`, `Lightning`, `Eco2`, `Tvoc`, `AirQuality`, `MeteorologyUvIndex`, `MeteorologyUva`, `MeteorologyUvb`, `MeteorologyResumeToday`, `MeteorologyResumeHistorical` |
| Weather Station — AEMET | Los 9 modelos de `Models/WeatherStation/AEMET/` |
| Newsletter | `Newsletter` |
| Hardware | `SolarCharge`, `HardwareComponent` |
| Webhooks | `GitlabWebhook`, `SimpleWebhookModel` |
| Catálogos | `Language`, `UserRole`, `PrinterAvailableType`, `ContentAvailable*` |
| Analítica | `ContentDailyView` |

Se abordan en la fase 05 del roadmap.

---

## Autorización

- `Gate::before()` en `AppServiceProvider` concede acceso total al SuperAdmin (`role_id = 1`).
- `UserPolicy` protege a los SuperAdmins de ser editados por Admins (commit `66c41b1`).
- Las 16 Policies de `app/Policies/` se aplican por descubrimiento automático.
- Gates auxiliares: `access-admin-panel`, `manage-settings`, `view-statistics`.

## Comandos relacionados

```bash
php artisan filament:upgrade          # tras actualizar Filament
php artisan make:filament-resource    # nuevo Resource
php artisan make:filament-cluster     # nuevo Cluster
php artisan make:filament-widget      # nuevo Widget
```

## Tests

Ninguno a fecha de esta revisión. Pendiente en la
fase 09 del roadmap.

---

> Creado: 2026-08-30 · Última revisión: 2026-08-30
