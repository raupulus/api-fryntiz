# AGENTS.md — Información del proyecto Api Raupulus

## Descripción General

Api Raupulus es una plataforma multi-API desarrollada con Laravel 13 que centraliza módulos IoT (estación meteorológica, plantas inteligentes, contador de pulsaciones, registro de vuelos, energía solar), gestión de contenidos multi-plataforma, currículum vitae y newsletter.

## Skills del proyecto — carga automática

Este repo incluye **skills específicas** en `.claude/skills/<nombre>/SKILL.md`
(versionadas en git). Cada skill encapsula las **convenciones reales** de su
dominio en este proyecto. En Claude Code se auto-activan por su campo
`description`; cualquier otro agente que lea este AGENTS.md debe **cargar la skill
correspondiente al empezar a trabajar en ese contexto** según esta tabla:

| Skill | Ruta | Cárgala cuando trabajes en… |
|-------|------|------------------------------|
| `laravel-backend` | `.claude/skills/laravel-backend/SKILL.md` | Models, Services, Actions, Jobs, Events/Listeners, Enums, Helpers, Policies, Traits; lógica de negocio bajo `app/` (skill base del backend) |
| `api-rest-v2` | `.claude/skills/api-rest-v2/SKILL.md` | Endpoints API: controladores `Api/…/V2`, Resources `V2`, FormRequests `Api`, rutas `*/v2.php`, Sanctum, tokens IoT/abilities, envelope JSON |
| `postgresql-migrations` | `.claude/skills/postgresql-migrations/SKILL.md` | `database/migrations/`, esquema, índices, FKs, tipos de columna, rendimiento de consultas |
| `filament-admin` | `.claude/skills/filament-admin/SKILL.md` | `app/Filament/`: Resources, Pages, Widgets, Clusters, componentes y paneles Admin/Tenant |
| `vue-tailwind-frontend` | `.claude/skills/vue-tailwind-frontend/SKILL.md` | `resources/js/`, `resources/css/`, componentes `.vue`, vistas Blade, Tailwind 4, Vite/pnpm, Alpine |
| `design-system` | `.claude/skills/design-system/SKILL.md` | Criterio visual: paleta, tipografía, jerarquía, look&feel, diseñar/revisar pantallas (Raupulus Slate / Obsidian Flux) |
| `seo` | `.claude/skills/seo/SKILL.md` | `ContentSeo`, meta/OG/Twitter en `head.blade.php`, sitemap, JSON-LD, hreflang, canonicals, posicionamiento |
| `mcp-server` | `.claude/skills/mcp-server/SKILL.md` | `app/Mcp/` (Servers/Tools/Resources/Prompts), `routes/ai.php`, comandos `mcp:*`/`make:mcp-*`, exponer contexto del proyecto a LLMs (`laravel/mcp`) |
| `printers` | `.claude/skills/printers/SKILL.md` | Módulo Impresoras: modelos `Printer`/`PrinterStack`/`PrinterAvailableType`, cola de impresión, `PrinterResource` |

Regla práctica: si una tarea cae en uno de esos contextos, **consulta primero la
skill** antes de escribir código, para respetar las convenciones del proyecto.
Varias pueden aplicar a la vez (p. ej. un endpoint nuevo con su migración usa
`api-rest-v2` + `postgresql-migrations` + `laravel-backend`).

## Arquitectura

- **Patrón:** MVC con Service Layer
- **API:** Única versión **V2 FULL REST** (la V1 legacy fue eliminada en fix_1), respuestas JSON con JsonResources
- **Admin:** Dos paneles Filament 5 (Admin para superadmin, Tenant para usuarios)
- **Frontend:** Blade + Tailwind CSS 4 + Alpine.js + Vue.js 3 (Vite 6, prefiere usar pnpm)
- **Build:** Vite 6 con `laravel-vite-plugin`, `@tailwindcss/vite` y `@vitejs/plugin-vue` — entrypoints: `resources/css/app.css`, `resources/js/app.js`, `resources/js/vue.js`, `resources/css/filament/admin/theme.css`
- **Excepciones API:** Excepciones JSON personalizadas (`JsonValidationException`, `JsonAuthorizationException`) en `app/Exceptions/`, configuradas en `bootstrap/app.php`
- **Eventos y WebSockets:** Eventos broadcast (`app/Events/`) con listeners (`app/Listeners/`), con soporte preparado para Laravel Reverb. Sub-eventos granulares en `app/Events/WeatherStation/` (Temperature, Humidity, Pressure, Light, Wind, Rain, Lightning, AirQuality)
- **Jobs:** `ProcessContentViewJob` para vistas de contenido asíncronas
- **Notificaciones:** `NewContactMessage` para formulario de contacto

## Dependencias Principales

| Paquete | Versión | Uso |
|---------|---------|-----|
| `laravel/framework` | ^13.0 | Framework base |
| `filament/filament` | ^5.0 | Paneles de administración |
| `laravel/sanctum` | ^4.0 | Autenticación API (tokens) |
| `laravel/fortify` | ^1.24 | Autenticación web |
| `intervention/image` | ^3.0 | Procesamiento de imágenes |
| `intervention/image-laravel` | ^1.5 | Integración Laravel de Intervention |
| `guzzlehttp/guzzle` | ^7.9 | Cliente HTTP (AEMET, etc.) |
| `spatie/laravel-sitemap` | ^8.0 | Generación de sitemap |
| `google/recaptcha` | ^1.3 | Validación reCAPTCHA |
| `laravel/tinker` | ^3.0 | REPL interactivo |
| `laravel/pint` | ^1.18 | Linting/formatting (dev) |
| `phpunit/phpunit` | ^11.0 | Tests (dev) |
| `barryvdh/laravel-debugbar` | ^4.0 | Debug toolbar (dev) |
| `vue` | ^3.5 | Framework JS reactivo (frontend) |
| `@vitejs/plugin-vue` | ^6.0 | Plugin Vite para Vue SFC |
| `alpinejs` | ^3.14 | Interactividad ligera Blade |

**PHP requerido:** ^8.4
**Node.js requerido:** >=20 <=26 (pnpm@11)

## Comandos de Desarrollo

```bash
# Frontend (usar pnpm preferentemente)
pnpm dev              # Servidor Vite en desarrollo
pnpm build            # Build de producción

# Tests
php artisan test                    # Ejecutar todos los tests
php artisan test --testsuite=Unit   # Solo tests unitarios
# BD de test: api_raupulus_testing (PostgreSQL)

# Linting
./vendor/bin/pint     # Formatear código con Laravel Pint (PSR-12)

# Comandos Artisan del proyecto
php artisan project:install         # Inicializa proyecto completo (keys, BD, storage)
php artisan project:clear           # Limpia cachés (optimizado)
php artisan debug:seed-all          # Poblar la base de datos de datos de prueba
php artisan content:publish         # Publicar contenidos programados
php artisan sitemap:generate        # Generar sitemap
php artisan aemet:*                 # Múltiples comandos AEMET (ver app/Console/Commands/AEMET/)
php artisan keycounter:maintenance  # Mantenimiento de keycounter
```

## Estructura de Directorios Clave

```
app/
├── Actions/            # Operaciones atómicas reutilizables
│   ├── Fortify/        # Acciones de autenticación Fortify (CreateNewUser, ResetUserPassword, etc.)
│   ├── PublishContentAction.php
│   └── StoreSensorDataAction.php
├── Console/Commands/   # Comandos Artisan
│   ├── AEMET/          # 7 comandos AEMET por frecuencia (Daily, Every10m, Every30m, Every4h, etc.)
│   └── Content/        # PublishContentCommand
├── Enums/              # PHP 8.4 backed enums (UserRoleEnum, ContentStatusEnum, ContentTypeEnum, ContentPageRawTypeEnum, CvRepositoryTypeEnum, FileTypeEnum, HardwareTypeEnum, NewsletterStatusEnum, PlatformStatusEnum)
├── Events/             # Eventos de dominio (WeatherStationUpdateEvent) + WeatherStation/ (8 sub-eventos granulares por sensor)
├── Exceptions/         # Excepciones JSON personalizadas (JsonValidationException, JsonAuthorizationException)
├── Filament/
│   ├── Admin/          # Panel Admin: Resources (16 módulos), Pages, Widgets, Clusters (AirFlight, Energy, KeyCounter, SmartPlant)
│   ├── Tenant/         # Panel Tenant: Resources, Pages, Widgets (en desarrollo)
│   ├── Components/     # Campos Filament personalizados (EditorJsField, ImageCropperUpload, YoutubeVideoField)
│   └── Concerns/       # Traits Filament (HasImageFileUpload)
├── Helpers/            # Clases helper globales (AEMETHelper, ContentHelper, GoogleRecaptchaHelper, JsonHelper*, MenuHelper, RoleHelper, TextFormatParseHelper)
├── Http/
│   ├── Controllers/Api/    # Controladores API por módulo + V2/
│   ├── Controllers/        # Controladores web públicos por módulo
│   ├── Middleware/          # Cors, CorsAllowAll, DomainCheckMiddleware, IpCounterStrict
│   ├── Requests/           # FormRequests con validación en español (Api/, Cv/, Dashboard/)
│   └── Resources/          # JsonResources (raíz: Content*, User) + V2/ por módulo
├── Jobs/               # Jobs asíncronos (ProcessContentViewJob)
├── Listeners/          # Event listeners (BroadcastWeatherStationUpdate)
├── Mail/               # Mailables (ContactMail, GenericMail, NewsletterVerification, NewsletterUnsubscribe, RetryEmailMessage)
├── Models/             # Eloquent models organizados por módulo
│   ├── BaseModels/     # BaseModel, BaseAbstractModelWithTableCrud
│   ├── WeatherStation/ # 18 modelos de sensores + AEMET/ (9 modelos AEMET)
│   ├── Hardware/       # 12 modelos (dispositivos, energía, cargas solares)
│   ├── Content/        # 16 modelos (CMS completo)
│   ├── CV/             # 18 modelos (currículum)
│   ├── KeyCounter/     # 3 modelos (BaseKeyCounter, Keyboard, Mouse)
│   ├── SmartPlant/     # 2 modelos (SmartPlantPlant, SmartPlantRegister)
│   ├── AirFlight/      # 2 modelos (AirFlightAirPlane, AirFlightRoute)
│   ├── WebHooks/       # 2 modelos (GitlabWebhook, SimpleWebhookModel)
│   └── (raíz)          # Printer, PrinterAvailableType, PrinterStack, User, File, Platform, Newsletter, Category, Tag, Technology, etc.
├── Notifications/      # Notificaciones (NewContactMessage)
├── Policies/           # Authorization policies (16 policies por módulo)
├── Providers/          # Service providers
│   ├── Filament/       # AdminPanelProvider, TenantPanelProvider
│   ├── AppServiceProvider.php
│   ├── AuthServiceProvider.php
│   └── FortifyServiceProvider.php
├── Services/           # Service Layer por módulo (11 subdirectorios + RecaptchaService)
├── Support/            # Clases de soporte (FilamentValidationRules — reglas de validación reutilizables para Filament)
└── Traits/             # Traits compartidos (HasSlug, HasStatus, Filterable, HasTimestampScopes, ApiResponseTrait, BelongsToUser, BelongsToHardwareDevice)

routes/
├── web.php             # Rutas frontend público
├── api/
│   └── v2.php          # API V2 (única, prefijo /api/v2)
├── console.php         # Scheduler (tareas programadas)
├── dashboard.php       # Panel legacy AdminLTE (prefijo /dashboard)
├── webhook.php         # Webhooks (GitLab, etc.)
├── hardware/           # v2.php, web.php
├── keycounter/         # v2.php, web.php
├── smart_plant/        # v2.php, web.php
├── weather_station/    # v2.php, web.php
├── airflight/          # v2.php, web.php
└── cv/                 # v2.php, web.php
```

**Nota:** `JsonHelper.php`, `RoleHelper.php`, `AEMETHelper.php` y `MenuHelper.php` son clases globales (sin namespace) y se autocargan vía `composer.json` → `autoload.files` desde `support/helpers/` (fuera del raíz PSR-4 `app/` para no romper el estándar PSR-4).

## Middleware

Aliases registrados en `bootstrap/app.php`:

| Alias | Clase | Uso |
|-------|-------|-----|
| `cors` | `Cors` | CORS estándar |
| `cors.allow.all` | `CorsAllowAll` | CORS permisivo (desarrollo/IoT) |
| `check.domain` | `DomainCheckMiddleware` | Verificación de dominio |
| `ip.counter.strict` | `IpCounterStrict` | Contador de IPs estricto |

## Scheduler (routes/console.php)

```
content:publish          → hourly
sitemap:generate         → daily
aemet:adverse-events     → everySixHours
aemet:contamination      → everySixHours
aemet:predictions        → everySixHours
keycounter:maintenance   → weekly
```

## Módulos

### Weather Station
- Sensores: temperatura, humedad, presión, luz, viento, dirección viento, lluvia, eco2, tvoc, calidad aire, rayos
- Integración AEMET: predicciones, eventos adversos, costas, altamar, ozono, contaminación, radiación solar, playas
- Resúmenes diarios e históricos

### Hardware / Energy
- Dispositivos hardware con tipos y componentes
- Monitorización de energía: cargas solares, generadores, consumos
- Resúmenes diarios e históricos

### KeyCounter
- Registro de pulsaciones de teclado por tecla
- Registro de clicks y movimientos de ratón
- Estadísticas por usuario y dispositivo

### Smart Plant
- Plantas con sensores de humedad, luz, temperatura
- Registros periódicos de datos

### AirFlight
- Registro de aviones detectados
- Rutas y telemetría

### Content (CMS)
- Multi-plataforma, multi-tipo (artículo, tutorial, proyecto, página, reseña)
- Páginas paginadas con contenido raw (HTML/Markdown/JSON)
- SEO, metadata, categorías, tags, tecnologías, contribuidores, archivos, galerías
- Contenido relacionado, vistas diarias

### CV (Currículum)
- 16 secciones: experiencia (acreditada, no acreditada, autónomo, adicional, otra), formación (reglada, complementaria, online), habilidades, proyectos, repositorios, servicios, colaboraciones, hobbies, trabajos
- Generación de PDF con DomPDF

### Newsletter
- Suscripción con verificación por email
- Baja por token

## Convenciones

- **Package Manager:** Referencia principal de frontend es `pnpm`, usarlo en lugar de npm/yarn.
- **Idioma del código:** Inglés (variables, métodos, clases)
- **Idioma de documentación:** Español (PHPDoc, comments de migraciones, mensajes de validación)
- **Estilo:** PSR-12, principios SOLID — formatear con `./vendor/bin/pint`
- **Naming:** Convenciones Laravel (PascalCase clases, camelCase métodos, snake_case tablas/columnas)
- **Enums:** Usar PHP 8.4 backed enums en `app/Enums/` (sufijo `Enum`, e.g. `UserRoleEnum`, `ContentStatusEnum`)
- **Traits:** Reutilizar traits de `app/Traits/` antes de duplicar lógica (`HasSlug`, `HasStatus`, `Filterable`, `HasTimestampScopes`, `ApiResponseTrait`, `BelongsToUser`, `BelongsToHardwareDevice`)
- **Modelos base:** Extender `BaseModel` o `BaseAbstractModelWithTableCrud` de `app/Models/BaseModels/`
- **API Responses:** Las excepciones de API se manejan globalmente en `bootstrap/app.php` — devuelven JSON con estructura `{success, message}` o `{success, message, errors}`
- **Resources:** Todos en `app/Http/Resources/V2/` organizados por módulo (los legacy de raíz se eliminaron con la V1)

## Autenticación

- Web: Laravel Fortify (login, registro, reset password, verificación email)
- API: Laravel Sanctum (tokens)
- **IoT (dispositivos):** token Sanctum **por dispositivo** con **abilities/scopes** por módulo (`weatherstation:write`, `hardware:write`, `keycounter:write`, `smartplant:write`, `airflight:write`) y expiración. Emisión con `php artisan iot:device-token <id> --abilities=... [--expires=días]`. Alias de middleware `ability`/`abilities` registrados en `bootstrap/app.php`. Endpoints de escritura protegidos con `auth:sanctum` + `ability:<scope>` + `throttle:api-store`.
- Roles: 1=SuperAdmin, 2=Admin, 3=User
- Panel Admin: solo SuperAdmin
- Panel Tenant: cualquier usuario autenticado

## Base de datos

- PostgreSQL con migraciones documentadas (comment en todas las columnas)
- Foreign keys con onDelete/onUpdate explícitos
- Índices en columnas de búsqueda frecuente

## ⚠️ Documentación técnica de módulos — OBLIGATORIO mantener actualizada

La documentación técnica de cada módulo se encuentra en `docs/info/`. **Es obligatorio actualizarla cuando se modifique un módulo existente o se cree uno nuevo.**

### Índice de módulos (`docs/info/`)

| Archivo | Módulo |
|---------|--------|
| `weather-station.md` | Estación Meteorológica (sensores + AEMET) |
| `hardware.md` | Hardware y Energía (dispositivos, cargas solares, generadores) |
| `printers.md` | Impresoras (gestión de impresoras y cola de impresión) |
| `keycounter.md` | Contador de Pulsaciones (teclado y ratón) |
| `smart-plant.md` | Plantas Inteligentes (sensores de plantas) |
| `airflight.md` | Registro de Vuelos (aviones detectados) |
| `content.md` | CMS / Contenidos (artículos, tutoriales, proyectos) |
| `cv.md` | Currículum Vitae (16 secciones + PDF) |
| `platform.md` | Plataformas (multi-sitio) |
| `newsletter.md` | Newsletter (suscripción, verificación, baja) |
| `auth.md` | Autenticación y Usuarios (Sanctum, Fortify, roles) |
| `contact.md` | Formulario de Contacto (email + recaptcha) |
| `files.md` | Gestión de Archivos (uploads, thumbnails, redimensión) |
| `common.md` | Entidades Comunes (categorías, tags, tecnologías, idiomas) |
| `default-images.md` | Imágenes por defecto (catálogo por módulo) |
| `debug-commands.md` | Comandos de Debug (inserción de datos de prueba) |
| `commands.md` | Catálogo completo de comandos Artisan |
| `websockets.md` | WebSockets con Laravel Reverb |
| `mcp.md` | Model Context Protocol (MCP) (servidores y herramientas de IA) |
| `apis/aemet.md` | API AEMET OpenData (integración técnica) |

### Reglas de actualización de `docs/info/`

1. **Al modificar un módulo existente** (nuevos campos, nuevas relaciones, cambios en lógica, nuevas rutas, etc.), actualizar el archivo `.md` correspondiente en `docs/info/` reflejando los cambios.

2. **Al crear un módulo nuevo**, crear un nuevo archivo `.md` en `docs/info/` con la misma estructura (resumen, archivos, campos, relaciones, rutas, etc.) y añadirlo al índice en `docs/info/README.md` y a la tabla de este archivo.

3. **Al eliminar un módulo**, eliminar el archivo `.md` correspondiente y quitarlo de los índices.

4. Cada archivo en `docs/info/` debe contener:
   - Resumen breve del módulo (1-2 frases) al inicio
   - Archivos principales involucrados (modelo, controlador, resource, vistas, etc.)
   - Campos del modelo con tipos y descripciones
   - Relaciones, scopes y métodos relevantes
   - Rutas (web y API) si aplica
   - Configuración Filament si aplica
