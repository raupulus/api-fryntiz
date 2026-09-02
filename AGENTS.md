# AGENTS.md — Api Raupulus

> **Última revisión:** 2026-08-30 · Verificado contra la rama `v2`.
> Todo lo que hay aquí se ha comprobado contra el código. Si encuentras una discrepancia,
> **corrige este fichero en el mismo commit** en el que la detectes.

---

## 0. Antes de empezar — lee esto

| Necesitas saber… | Ve a |
|------------------|------|
| Arquitectura, rutas, comandos y convenciones vigentes | Este fichero |
| Cómo funciona un módulo concreto | [`docs/info/<modulo>.md`](docs/info/README.md) |
| Convenciones de un dominio (API, Filament, migraciones…) | `.claude/skills/<skill>/SKILL.md` — ver §2 |
| Rutas de la API V2 propia (`/api/v2/...`) | [`docs/info/api/v2/README.md`](docs/info/api/v2/README.md) — índice y contrato por módulo |
| Historial de cómo se llegó hasta aquí | `docs/planning/archived/` — **excepción, no fuente de verdad**: ver aviso abajo |

**Estado en una línea:** la API V2 está completa y operativa, la V1 fue eliminada por completo,
y el proyecto está en fase de **consolidación** — limpiar legacy y completar el panel Filament.
No estamos construyendo la V2; la estamos terminando de asentar.

> ⚠️ **`docs/planning/archived/` no es fiable y es la excepción, no la norma.** Es material de
> trabajo local (no versionado, `.gitignore`) que registra cómo se llegó al estado actual;
> **no describe el estado actual**. En un clon nuevo del repositorio **no existe**, y en este
> mismo repositorio se borrará por completo en cuanto se verifique que sus planificaciones están
> implementadas del todo. Solo se consulta si hace falta rescatar el porqué concreto de una
> decisión ya tomada, nunca para saber si algo "sigue abierto": para eso, este fichero y el
> código son la fuente de verdad vigente.

---

## 1. Qué es este proyecto

Plataforma multi-API en Laravel 13 que centraliza los proyectos personales de Raúl Caro
Pastorino (**@raupulus**): módulos IoT (estación meteorológica, plantas inteligentes, contador
de pulsaciones, registro de vuelos ADS-B, energía solar, impresoras), un CMS multi-plataforma,
currículum vitae y newsletter.

**Objetivo del proyecto:** una solución robusta, escalable, segura y **fácil de gestionar desde
la intranet**. Ese último punto es el que justifica el esfuerzo en el panel Filament: todo debe
poder administrarse sin abrir una terminal.

---

## 2. Skills del proyecto — carga automática

El repo incluye skills en `.agents/skills/<nombre>/SKILL.md` (versionadas en git mediante una
excepción de `.gitignore`). Cada una encapsula las **convenciones reales** de su dominio.
En Claude Code se auto-activan por su `description`; cualquier otro agente debe cargar la que
corresponda **antes de escribir código**.

| Skill | Cárgala cuando trabajes en… |
|-------|------------------------------|
| `laravel-backend` | Models, Services, Actions, Jobs, Events/Listeners, Enums, Helpers, Policies, Traits — lógica de negocio bajo `app/` (skill base) |
| `api-rest-v2` | Endpoints: controladores `Api/…/V2`, Resources `V2`, FormRequests `Api`, rutas `*/v2.php`, Sanctum, tokens IoT/abilities, envelope JSON |
| `postgresql-migrations` | `database/migrations/`, esquema, índices, FKs, tipos de columna, rendimiento |
| `filament-admin` | `app/Filament/`: Resources, Pages, Widgets, Clusters, componentes, paneles Admin/Tenant |
| `vue-tailwind-frontend` | `resources/js/`, `resources/css/`, `.vue`, Blade, Tailwind 4, Vite 8, Alpine |
| `design-system` | Criterio visual: paleta, tipografía, jerarquía (Raupulus Slate / Obsidian Flux) |
| `seo` | `ContentSeo`, meta/OG/Twitter, sitemap, JSON-LD, hreflang, canonicals |
| `mcp-server` | `app/Mcp/`, `routes/ai.php`, comandos `mcp:*`, exponer contexto a LLMs (`laravel/mcp`) |
| `printers` | Módulo Impresoras: `Printer`, `PrinterStack`, `PrinterAvailableType`, cola de impresión |
| `module-development` | Flujo de trabajo completo paso a paso para la creación de nuevos módulos |

Varias pueden aplicar a la vez: un endpoint nuevo con su migración usa `api-rest-v2` +
`postgresql-migrations` + `laravel-backend`.

---

## 3. Flujo de trabajo obligatorio

**Ninguna tarea está terminada hasta que estos cuatro pasos pasan en verde.**

```bash
./vendor/bin/pint                    # 1. Formato (PSR-12)
composer phpstan                     # 2. Análisis estático (nivel 5)
php artisan test                     # 3. Tests (PostgreSQL: api_raupulus_testing)
php artisan scribe:generate          # 4. Solo si has tocado la API V2
```

Atajo obligatorio: `composer check` (formato + análisis estático + tests).

> ⚠️ **El paso 2 va por `composer phpstan`, no por `./vendor/bin/phpstan analyse`.**
> A pelo revienta con `Allowed memory size of 134217728 bytes exhausted` en una
> instalación limpia: PHPStan necesita más de los 128 MB que trae PHP por
> defecto, y el script le pasa `--memory-limit=1G`. `phpunit.xml` ya resuelve lo
> mismo para los tests con su `<ini name="memory_limit" value="512M"/>`.
> Quien lo ejecute a pelo verá un fatal error de memoria en vez de un informe, y
> es fácil confundirlo con «PHPStan está roto» y saltárselo (auditoría AR-C01).

### Además, en cada tarea

1. **Actualiza `docs/info/<modulo>.md`** en el mismo commit. Es obligatorio por protocolo, no opcional.
2. **Añade o ajusta tests.** Toda funcionalidad implementada tiene que tener tests automatizados que la validen para seguir funcionando (`php artisan test --compact`). Un cambio de comportamiento sin test es un cambio sin verificar.
3. **Si tocas un contrato de la API**, comprueba qué clientes lo consumen antes de romperlo: los dispositivos IoT no se actualizan solos.
4. **Regla de planificación:** Toda fase o módulo en una planificación lleva al inicio una descripción y al final un checklist (`- [ ] ...`) que solo se marcará como checked (`[x]`) cuando se verifique de verdad que se ha implementado y se cumple perfectamente.
5. **Fechas en la documentación:** Todo archivo dentro de `docs/`, en cualquier subdirectorio, lleva al pie **fecha de creación y fecha de última revisión**, en una sola línea y con este formato exacto:

   ```markdown
   ---

   > Creado: YYYY-MM-DD · Última revisión: YYYY-MM-DD
   ```

   La de creación no se toca nunca una vez puesta. La de revisión se actualiza **en el mismo commit** en que se cambia el documento. Sirve para auditar que la documentación viva se mantiene al día.
6. **Si trabajas sobre una fase del roadmap**, marca su checklist al terminar.
7. **Directriz de Lectura Dirigida y Economía de Tokens**: Al trabajar en un módulo o funcionalidad específica (ej. noticias, páginas, usuarios), el agente debe consultar **únicamente** el archivo técnico correspondiente en `docs/info/[modulo].md` y la guía de diseño en `docs/info/DESIGN.md` en caso de tocar el frontend/vistas. No debe leer el resto de archivos de `docs/info/` a menos que sea estrictamente necesario para resolver una relación cruzada entre modelos.

### Idiomas

> ⛔ **Esta regla se ha incumplido y ha habido que deshacerlo entero.** No es una
> preferencia de estilo: **todo identificador va en inglés y toda explicación en
> español**, sin excepciones y sin preguntar. Si dudas de cómo se dice algo en
> inglés, se busca; no se deja en castellano «de momento».
>
> «Identificador» es **todo lo que el intérprete lee**: clases, traits, enums,
> interfaces, métodos, funciones, variables, propiedades, constantes, argumentos
> con nombre, claves de array internas, claves de configuración, nombres de
> fichero y de directorio, tablas, columnas, nombres de test, canales de
> broadcast, clases CSS e identificadores de JavaScript y de Vue.
>
> **Excepción única:** los campos que devuelve una API de terceros se leen tal y
> como los manda (`$envelope['estado']` de AEMET), porque el nombre no es
> nuestro. El contexto que escribimos alrededor —variables, claves de log— sí va
> en inglés.

| Elemento | Idioma |
|----------|--------|
| Código (clases, métodos, variables, propiedades, constantes, columnas, claves de configuración, nombres de test) | Inglés |
| PHPDoc, comentarios, comentarios de migración | Español |
| Mensajes de validación y de cara al usuario | Español (**con tildes** — hay textos antiguos sin ellas, corrígelos al pasar) |
| Mensajes de log | Inglés |
| Documentación (`docs/`, `README.md`, este fichero) | Español |

### Crédito y autoría

Cuando algo generado deba atribuirse: nick **@raupulus**, email público **public@raupulus.dev**.
No usar ninguna otra dirección.

---

## 4. Stack real

### Backend

| Paquete | Versión | Uso |
|---------|---------|-----|
| `php` | ^8.4 | — |
| `laravel/framework` | ^13.0 | Framework |
| `filament/filament` | ^5.0 | Paneles de administración |
| `laravel/sanctum` | ^4.0 | Autenticación API por tokens |
| `laravel/fortify` | ^1.24 | Autenticación web |
| `laravel/mcp` | ^0.8.1 | Servidor MCP (`app/Mcp/`, `routes/ai.php`) |
| `laravel/tinker` | ^3.0 | REPL |
| `intervention/image` + `-laravel` | ^3.0 / ^1.5 | Procesamiento de imágenes |
| `guzzlehttp/guzzle` | ^7.9 | Cliente HTTP (AEMET) |
| `spatie/laravel-sitemap` | ^8.0 | Sitemap |
| `google/recaptcha` | ^1.3 | reCAPTCHA |

**Dev:** `knuckleswtf/scribe` ^5.11 (doc de API) · `larastan/larastan` ^3.10 ·
`laravel/pint` ^1.18 · `phpunit/phpunit` ^11.0 · `barryvdh/laravel-debugbar` ^4.0 ·
`barryvdh/laravel-ide-helper` ^3.7 · `mockery/mockery` ^1.6 · `fakerphp/faker` ^1.24 ·
`nunomaduro/collision` ^8.0 · `roave/security-advisories`.

### Frontend

| Paquete | Versión |
|---------|---------|
| `vue` | ^3.5 |
| `alpinejs` | ^3.15 |
| `tailwindcss` + `@tailwindcss/vite` | ^4.3 |
| `vite` | ^8.0 |
| `laravel-vite-plugin` | ^3.1 |
| `@vitejs/plugin-vue` | ^6.0 |
| `@tailwindcss/forms` | ^0.5 |

**Node:** >=20 <=26 · **Gestor de paquetes: `pnpm@11.7.0`** (declarado en `packageManager`).
Usar `pnpm`, nunca `npm` ni `yarn`.

**Entrypoints de Vite:** `resources/css/app.css`, `resources/js/app.js`, `resources/js/vue.js`,
`resources/css/filament/admin/theme.css`.

### Infraestructura

PostgreSQL 17 · Redis 7 (recomendado en producción) · Docker (`docker/app` PHP-FPM + supervisord,
`docker/nginx`, `docker/postgres`) · nginx o Apache en bare-metal.

---

## 5. Arquitectura

- **Patrón:** MVC con Service Layer. La lógica de negocio vive en `app/Services/`, no en los
  controladores.
- **API:** una única versión, **V2 FULL REST**, bajo el prefijo `/api/v2`. La V1 fue eliminada
  por completo; `bootstrap/app.php` responde **`410 Gone`** a cualquier petición a `api/v1/*`.
- **Controladores API:** todos extienden `App\Http\Controllers\Api\V2\BaseApiController`, que
  usa el trait `ApiResponseTrait` (`successResponse`, `createdResponse`, `paginatedResponse`
  —colecciones, con bloque `meta`—, `deletedResponse` (204), `errorResponse`,
  `notFoundResponse`, `unauthorizedResponse`, `forbiddenResponse`, `conflictResponse`,
  `withWarnings`).
- **Excepciones:** manejadas globalmente en `bootstrap/app.php` con envelope JSON uniforme.
  Además hay `JsonValidationException` y `JsonAuthorizationException` en `app/Exceptions/`
  que se auto-renderizan.
- **Admin:** dos paneles Filament 5 — `admin` (`/admin`, exige `is_active` y rol SuperAdmin,
  Admin o **Editor** — `User::canAccessPanel()`) y `tenant` (`/panel`, cualquier usuario
  autenticado y activo).
- **Frontend público:** Blade + Tailwind 4 + Alpine 3, con **1** componente Vue puntual
  (`ChipionaWeatherComponent`). Los colores son tokens de `@theme` en
  `resources/css/app.css`: **en las vistas no se escribe un color literal**.
  Ver `docs/info/frontend.md`.
- **Eventos:** uno, `App\Events\WeatherStation\ReadingsReceived` (`readings.received` en el
  canal), emitido desde `SensorReadingController` una vez por subida al canal público
  `weather-station.{id}`. `laravel/reverb`, `laravel-echo` y `pusher-js` **ya están
  instalados**; sigue **apagado por defecto** (`BROADCAST_CONNECTION=null`) porque encenderlo
  es una decisión de despliegue (configurar `.env` y levantar el demonio), no de código.
  Ver `docs/info/websockets.md`.
  Los nueve eventos anteriores colgaban de `$dispatchesEvents` de los modelos y **no se
  emitieron nunca**, porque la escritura usa `insert()` del query builder.
- **Jobs:** `ProcessContentViewJob`. ⚠️ Con `QUEUE_CONNECTION=sync` corre en la petición.
- **MCP:** `app/Mcp/Servers/ApiRaupulusServer.php` con 4 tools (`GetModelInfoTool`,
  `GetSystemStatusTool`, `InspectDatabaseSchemaTool`, `RunSpecificTestTool`).

---

## 6. Estructura de directorios completa

> ⚠️ **Nota:** Esta estructura debe mantenerse estrictamente actualizada tras cualquier adición, eliminación o reestructuración de directorios en el proyecto.

```
api-fryntiz/
├── app/                      # Lógica principal de la aplicación (arquitectura MVC + Service Layer)
│   ├── Actions/              # Operaciones atómicas reutilizables (Fortify, PublishContent, StoreSensorData)
│   ├── Console/Commands/     # 36 comandos Artisan propios (AEMET/, Debug/, IoT/, Mcp/, CV/, Project...)
│   ├── Enums/                # Backed Enums tipados en PHP 8.4 (sufijo Enum)
│   ├── Events/               # Eventos de dominio (WeatherStationUpdateEvent y sub-eventos)
│   ├── Exceptions/           # Excepciones personalizadas (JsonValidationException, JsonAuthorizationException)
│   ├── Filament/             # Configuración de los paneles de Filament
│   │   ├── Admin/            # Panel Admin: 24 Resources, 29 RelationManagers, 4 Clusters, 7 Widgets, 5 Pages
│   │   ├── Tenant/           # Panel Tenant/Usuario: Pages/Dashboard.php
│   │   ├── Components/       # Componentes Filament (EditorJsField, ImageCropperUpload, YoutubeVideoField)
│   │   └── Concerns/         # Traits de Filament (HasImageFileUpload)
│   ├── Helpers/              # Clases auxiliares con namespace (TextFormatParseHelper)
│   ├── Http/                 # Capa de transporte HTTP
│   │   ├── Api/               # CollectionQuery: paginación/filtros/orden comunes a la API V2
│   │   ├── Controllers/Api/  # Controladores API V2 organizados por módulo
│   │   ├── Controllers/      # Controladores web para vistas públicas y streaming de ficheros
│   │   ├── Middleware/       # Vacío. Los 3 que había estaban muertos y se retiraron en la fase 8
│   │   ├── Requests/         # 20 FormRequests con reglas estrictas de validación
│   │   └── Resources/V2/     # 31 JsonResources para transformación de datos en API V2
│   ├── Jobs/                 # Trabajos en cola asíncronos (ProcessContentViewJob)
│   ├── Mail/                 # Clases Mailable para notificaciones y suscripciones por correo
│   ├── Mcp/                  # Implementación del servidor Model Context Protocol (Servers/ y Tools/)
│   ├── Models/               # 107 Modelos Eloquent con PHPDoc completo
│   │   ├── BaseModels/       # BaseModel con métodos y scopes comunes
│   │   ├── WeatherStation/   # Sensores meteorológicos físicos e integración AEMET
│   │   ├── Content/          # CMS: Artículos, Páginas, Metadatos, Categorías, Tags, Tecnologías
│   │   ├── CV/               # Secciones curriculares (Experiencia, Habilidades, Proyectos...)
│   │   ├── Hardware/         # Dispositivos físicos, monitorización de energía solar y generadores
│   │   └── (raíz)            # User, Platform, File, FileThumbnail, Gallery, Printer, Newsletter...
│   ├── Notifications/        # Notificaciones del sistema
│   ├── Policies/             # 16 Policies para autorización de modelos
│   ├── Providers/            # Service Providers de Laravel y paneles Filament (AdminPanelProvider, TenantPanelProvider)
│   ├── Rules/                # Reglas de validación personalizadas (OwnedHardwareDevice, OwnedSmartPlant, KnownSensor)
│   ├── Services/             # Service Layer con toda la lógica de negocio dividida por dominio (15 servicios + el DTO `CaptchaResult`)
│   ├── Support/              # Clases de soporte general (Auth, TokenAbilities, FilamentValidationRules)
│   └── Traits/               # Traits reutilizables (ApiResponseTrait, BelongsToUser, HasSlug, Filterable...)
├── bootstrap/                # Arranque del framework y configuración de excepciones y middlewares (app.php, providers.php)
├── config/                   # Archivos de configuración de Laravel, Filament, Sanctum, CORS, AEMET y base de datos
├── database/                 # Base de datos PostgreSQL
│   ├── factories/            # 105 Factories para generación de datos de prueba
│   ├── migrations/           # 133 migraciones comentadas en todas sus tablas y columnas
│   └── seeders/              # 18 Seeders ordenados para carga de catálogos y datos esenciales
├── docs/                     # Documentación técnica viva del proyecto (con fecha de revisión obligatoria)
│   ├── apis/                 # Documentación técnica de referencia de APIs de terceros (AEMET OpenData)
│   ├── auditorias/           # Informes de auditoría de código internos (excluidos en .gitignore)
│   ├── deploys/              # Guías de despliegue en VPS (Docker y bare-metal)
│   ├── info/                 # Documentación técnica viva de cada módulo, COMPONENTS.md, DESIGN.md y commands.md
│   ├── future/               # Ideas y trabajo decididos pero aplazados. Sólo se lee al retomarlos, no de rutina
│   └── planning/             # Roadmap de fases, dudas/ y estado actual (excluido en .gitignore)
├── lang/                     # Traducciones oficiales en español (es/) e inglés (en/) cargadas por Laravel 13
├── public/                   # Raíz web pública (index.php, assets compilados, robots.txt)
├── resources/                # Recursos de frontend
│   ├── css/                  # Hojas de estilo Tailwind CSS v4 (app.css con tokens @theme y tema oscuro)
│   ├── js/                   # Scripts JavaScript (Vite 8, Alpine.js, componentes Vue puntuales)
│   └── views/                # Vistas Blade públicas, layouts, correos y components/ (<x-button>, <x-input>...)
├── routes/                   # Definición de rutas: web.php, api/v2.php, console.php, ai.php y rutas por módulo
├── support/helpers/          # Funciones auxiliares globales sin namespace (JsonHelper, AEMETHelper)
├── tests/                    # Suite de pruebas automatizadas PHPUnit ejecutadas contra PostgreSQL
│   ├── Feature/              # Pruebas de integración para API V2, autenticación, contratos y persistencia
│   └── Unit/                 # Pruebas unitarias de servicios y lógica aislada
├── .agents/                  # Directorio maestro de configuración de agentes y skills (versionado en git)
│   └── skills/               # Skills del proyecto compartidas entre agentes
├── .claude                   # Enlace simbólico apuntando a .agents (ln -s .agents .claude)
├── .github                   # Enlace simbólico apuntando a .agents (ln -s .agents .github)
└── CLAUDE.md                 # Enlace directo de referencia apuntando a AGENTS.md
```

---

## 6-bis. Mapa completo de rutas

La plataforma expone sus servicios a través de cuatro capas de enrutamiento principales:

### 1. Frontend Web Público (`routes/web.php` y submódulos)
- `GET  /`: Portada corporativa con presentación de servicios y métricas (`home`).
- `GET  /about`: Redirección automática a portada (`302`).
- `GET  /docs`: Documentación interactiva de la API con Swagger/OpenAPI (requiere sesión).
- `GET  /languages/ajax/get/languages`: Consulta asíncrona de idiomas soportados.
- `GET  /file/get/{module}/{id}/{slug?}`: Streaming dinámico de ficheros públicos y privados.
- `GET  /file/thumbnail/get/{module}/{id}/{slug?}`: Generación y entrega de miniaturas WebP.
- `POST /file/upload`: Subida autenticada de archivos al disco configurado.
- `POST /file/delete/{id}`: Eliminación segura y autenticada de archivos y sus miniaturas asociadas.
- `GET  /weatherstation`: Interfaz pública de la estación meteorológica (`weather_station.index`).
- `GET  /weatherstation/sensor/{type}`: Consulta visual de historial y gráficas por sensor meteorológico.
- `GET  /smartplant`: Listado de plantas inteligentes monitorizadas.
- `GET  /smartplant/{smartplant}`: Detalle de sensores de suelo, luz y riego de una planta.
- `GET  /hardware/energy`: Monitorización en tiempo real de balance fotovoltaico y consumos.
- `GET  /keycounter`: Estadísticas agregadas de pulsaciones y actividad de periféricos.
- `GET  /airflight`: Mapa y radar visual de tráfico aéreo ADS-B.
- `GET  /cv/get/pdf/raupulus/default`: Descarga del currículum vitae generado en PDF.
- `ANY  /register*`, `/panel/register*`: **Bloqueo explícito** con respuesta `404 Not Found`.
- `ANY  /dashboard*`: Redirección `301 Moved Permanently` a `/panel`.

### 2. Autenticación Web (Laravel Fortify)
- `GET|POST /login`: Formulario y autenticación web para sesiones de usuario (`login`).
- `POST     /logout`: Cierre de sesión web.
- `GET|POST /two-factor-challenge`: Desafío de autenticación de doble factor.
- `GET|POST /user/two-factor-*`: Gestión de claves de recuperación y códigos QR 2FA.

### 3. Paneles de Administración (Filament 5)
- **Panel Admin (`/admin`):** SuperAdmin, Admin y Editor (`AdminPanelProvider`,
  `User::canAccessPanel()`).
  - Login dedicado en `/admin/login` y perfil en `/admin/profile`.
  - 24 Recursos administrativos: Usuarios, Tokens API, Plataformas, Contenidos, Categorías, Tags, Tecnologías, Dispositivos Hardware, Componentes, Tipos de Hardware, Sistemas de Energía, Energías, Estación Meteorológica, Plantas Inteligentes (Plants + Registers), Vuelos ADS-B (Aviones + Rutas), Currículum, Tipos de repositorio de CV, Emails de contacto, Impresoras, Galerías, Teclado y Ratón (KeyCounter).
- **Panel Tenant / Usuario (`/panel`):** Panel para usuarios autenticados (`TenantPanelProvider`).
  - Dashboard de cliente y visualización de recursos propios.

### 4. API V2 REST (`/api/v2/...`, repartida entre `routes/api/v2.php` y `routes/{airflight,cv,hardware,keycounter,smart_plant,weather_station}/v2.php`)

Full REST: recursos en plural, sub-recursos anidados bajo su padre. El mapa completo de
endpoints, con auth, rate limit y contrato exacto por módulo, vive en
[`docs/info/api/v2/README.md`](docs/info/api/v2/README.md) — **no se duplica aquí**. Consúltalo
y mantenlo actualizado en el mismo commit siempre que trabajes con un endpoint de la API V2.

---

## 7. Autenticación y autorización

### Descripción de Roles de la Plataforma

El sistema implementa un esquema de roles respaldado por `app/Enums/UserRoleEnum.php`:

1. **`SuperAdmin` (Valor: `1`):**
   - Máxima autoridad de la plataforma.
   - El método `Gate::before()` en `AppServiceProvider` le concede **acceso irrestricto total** a todas las acciones, modelos y endpoints sin evaluar las Policies correspondientes.
   - Acceso exclusivo a operaciones críticas del sistema, auditorías, configuración avanzada y al panel `/admin`.
2. **`Admin` (Valor: `2`):**
   - Administrador operativo.
   - Acceso al panel de administración `/admin` mediante la comprobación `User::canAccessPanel()`.
   - Permisos para gestionar usuarios, plataformas, contenidos CMS, dispositivos hardware, supervisión de colas y lectura de estadísticas globales (`view-statistics`).
3. **`User` (Valor: `3`):**
   - Usuario final registrado o cliente corporativo.
   - No tiene acceso al panel `/admin` (su intento de acceso es rechazado con error 403).
   - Acceso al panel de cliente `/panel`, capacidad para consultar sus propios datos de usuario vía `/api/v2/users/me` y administrar los dispositivos o recursos vinculados a su identificador.
4. **`Editor` (Valor: `4`):**
   - Acceso al panel `/admin` mediante `User::canAccessPanel()` (`isAdmin() || isEditor()`),
     igual que Admin, pero **sin** el bypass total de `Gate::before()`: sus permisos dentro
     del panel los deciden las Policies de cada módulo, no un atajo de rol.
   - Pensado para gestionar contenido/operativa del panel sin ser administrador del sistema.

### Web

Laravel Fortify. **El registro público está bloqueado**: `/register`, `/register/*`,
`/panel/register` y `/panel/register/*` devuelven 404 desde `routes/web.php`. El alta de
usuarios es manual desde el panel admin.
`/dashboard` y `/dashboard/*` redirigen a `/panel` (301).

### API

Laravel Sanctum. ⚠️ `config/sanctum.php` tiene `'expiration' => null` — los tokens de sesión de usuario caducan a los 30 días según `config('auth.api_session_days')`, mientras que los tokens de dispositivos IoT están acotados por abilities y no caducan para evitar desconexiones de hardware en campo.

### IoT

Token Sanctum **por dispositivo** con abilities por módulo:

| Ability | Módulo |
|---------|--------|
| `weatherstation:write` | Estación meteorológica |
| `hardware:read` | Hardware / lectura de inventario y estado |
| `hardware:write` | Hardware / energía |
| `keycounter:write` | Contador de pulsaciones |
| `smartplant:write` | Plantas inteligentes |
| `airflight:write` | Registro de vuelos |

Catálogo único en `App\Support\Auth\TokenAbilities`. Los tokens de sesión humana llevan la
ability `session` (nunca la de un módulo); los de dispositivo llevan `device:{id}` + su
ability de módulo.

Emisión:
```bash
php artisan iot:device-token <device_id> --abilities=weatherstation:write --expires=365
```
Los endpoints de escritura llevan `auth:sanctum` + `ability:<scope>` + `throttle:api-store`.

---

## 8. Catálogo de comandos propios

Todos los comandos Artisan personalizados del proyecto están especificados y documentados en detalle en [`docs/info/commands.md`](docs/info/commands.md).

36 comandos propios en total (`find app/Console/Commands -iname '*.php' | grep -v Concerns | wc -l`).

### Comandos del Proyecto
- `php artisan project:install`: Instalación guiada del entorno (migraciones, seeders y storage:link).
- `php artisan project:clear`: Limpieza completa de todas las cachés, colas (`queue:clear`), regeneración segura de clave en `.env` (`key:generate --force`, con confirmación explícita si `app()->environment('production')` y no se pasa `--force`) y recomposición del autoload de Composer. Alias: `xerintel:clear`.
- `php artisan project:dummy`: Generación de datos de ejemplo corporativos realistas en todos los módulos para pruebas de cliente y desarrollo local.
- `php artisan force:clear`: Limpieza agresiva de caché para entornos de desarrollo con inconsistencias.
- `php artisan sitemap:generate`: Generación del sitemap XML público de todos los sitios navegables con metadatos de frecuencia y prioridad.

### Comandos de Módulos e Integraciones
- **AEMET (Estación Meteorológica):** un comando por producto, con la cadencia real de AEMET —
  `aemet:adverse-events`, `aemet:contamination`, `aemet:hourly-prediction`, `aemet:coast`,
  `aemet:beaches`, `aemet:high-sea`, `aemet:sun-radiation`, `aemet:ozone`, `aemet:check-api-key`.
  Los antiguos (`aemet:update*`) ya no existen: se retiraron en la fase 4 (ver
  `_to_delete/POR-QUE-ESTAN-AQUI.md`).
- **AirFlight:** `airflight:fix`.
- **Contenido (CMS):** `content:publish` (publica contenidos programados vencidos).
- **Currículum:** `cv:regenerate-pdfs` (red de seguridad de la regeneración manual desde Filament).
- **KeyCounter:** `keycounter:generate_duration`, `keycounter:remove_duplicate`.
- **IoT:** `iot:device-token` (emisión de tokens de hardware con abilities específicas), `iot:check-silent-devices` (avisa cuando un dispositivo deja de reportar).
- **MCP:** `mcp:inspector` (inspector de servidores MCP; `mcp:start` lo aporta el propio paquete).
- **Usuarios:** `user:make-admin` (crea un administrador; pensado para el primer arranque en un
  servidor, sustituye al recorte de tinker de la guía de despliegue). Opciones: `--email`, `--name`,
  `--password`, `--superadmin`. Sin `--password` la pide por consola con `secret()`, para que no
  quede en el historial del shell ni en la lista de procesos.
- **Datos de prueba técnicos:** `debug:seed-all` y comandos individuales `debug:seed-{airflight,contact,content,cv,energy,hardware,keycounter,newsletter,platform,smartplant,weatherstation}`.

### Comandos sobrescritos
- `php artisan serve`: sobrescribe el `serve` nativo (`App\Console\Commands\ServeCommand`) para
  levantar también `reverb:start` cuando `BROADCAST_CONNECTION=reverb`. Se registra con el mismo
  nombre que el del framework, así que Artisan lo sustituye en el auto-discovery.

Catálogo detallado: [`docs/info/commands.md`](docs/info/commands.md).

### Scheduler (`routes/console.php`)

**Arreglado y protegido por test** (`tests/Feature/Console/SchedulerTest.php` falla si algún
`Schedule::command()` apunta a un comando que no existe). 16 tareas programadas, todas contra
comandos reales: `content:publish` (hora), `sitemap:generate` (diario), los 9 comandos AEMET
con la cadencia de cada producto, `keycounter:remove_duplicate` / `generate_duration`
(semanal), `iot:check-silent-devices` (diario), `cv:regenerate-pdfs` (diario) y
`queue:prune-failed` (diario).

El fallo histórico que motivó ese test: el scheduler programaba cuatro comandos que no
existían (entre ellos `aemet:predictions` y `keycounter:maintenance`, nunca creados con ese
nombre). No es el estado actual.

---

## 9. Rate limiting

Definidos en `AppServiceProvider::boot()` (desactivados en el entorno `testing`):

| Limiter | Límite | Uso |
|---------|--------|-----|
| `api` | 60/min | General |
| `sensor-data` | 120/min | Escritura de sensores |
| `contact` | 5/hora por IP | Formulario de contacto |
| `api-auth` | 10/min por IP | Login, registro, newsletter |
| `api-store` | 60/min | Escrituras IoT |
| `api-store-batch` | 10/min | Escrituras en lote |
| `login`, `two-factor` | — | Definidos en `FortifyServiceProvider` |

---

## 10. Middleware

Aliases registrados en `bootstrap/app.php`:

| Alias | Clase | Estado |
|-------|-------|--------|
| `abilities` | `Laravel\Sanctum\Http\Middleware\CheckAbilities` | ✅ En uso |
| `ability` | `Laravel\Sanctum\Http\Middleware\CheckForAnyAbility` | ✅ En uso en las rutas IoT |

Y ya está: son los dos únicos alias. Había tres más —`cors`, `check.domain` e
`ip.counter.strict`— que **no los pedía ninguna ruta** y que estaban rotos por
dentro; se retiraron en la fase 8 (ver `_to_delete/POR-QUE-ESTAN-AQUI.md`).

El CORS real lo aplica `Illuminate\Http\Middleware\HandleCors` (prepend global) leyendo
`config/cors.php`, que a su vez depende de la variable **`FRONTEND_URLS`**.

---

## 11. Base de datos

- PostgreSQL. 133 migraciones, 105 factories, 19 ficheros de seeder (18 seeders + `DatabaseSeeder`).
- Toda columna lleva `->comment()` en español.
- Foreign keys con `onDelete`/`onUpdate` explícitos.
- Índices en las columnas de búsqueda frecuente.
- **Tests contra PostgreSQL real** (`api_raupulus_testing`), no SQLite: el proyecto usa tipos
  específicos de Postgres.
- `Model::preventLazyLoading()` está **activo fuera de producción**. Un N+1 lanza excepción en
  desarrollo. Usa siempre eager loading explícito.

---

## 12. Trampas conocidas del proyecto

Cosas que sorprenden y hacen perder tiempo. Léelas antes de depurar.

| Trampa | Detalle |
|--------|---------|
| **Lazy loading prohibido** | `Model::preventLazyLoading(! app()->isProduction())`. Lo que funciona en producción puede reventar en local. Es intencional. |
| **`Gate::before` para SuperAdmin** | Un SuperAdmin nunca llega al método de la Policy. Testea con usuarios de rol 2 y 3. |
| **Helpers globales sin namespace** | `JsonHelper` y `AEMETHelper` viven en `support/helpers/` y se usan con `\JsonHelper::…`. No están en `app/`. `RoleHelper` y `MenuHelper` se retiraron (fases 3 y 8). |
| **Traducciones** | Viven en `lang/` (raíz), no en `resources/lang/` (retirado). Laravel 13 las carga desde ahí sin configuración extra. |
| **Scheduler** | Arreglado; `SchedulerTest` impide que vuelva a programar comandos inexistentes. Ver §8. |
| **`Collection::macro('comment')`** | Macro definida en `AppServiceProvider` para poder hacer `$table->timestamps()->comment(...)` en migraciones. |
| **`Sanctum::usePersonalAccessTokenModel(ApiToken::class)`** | El modelo de token es `App\Models\ApiToken`, no el de Sanctum. |
| **Tests sin rate limit** | Todos los limiters devuelven `Limit::none()` en el entorno `testing`. Si testeas rate limiting, ajústalo. |
| **No lances la suite con `serve` o `mcp:start` vivos** | `RefreshDatabase` hace `migrate:fresh`, que pide `AccessExclusiveLock`. Con otro proceso conectado a la BD de test revienta con `Deadlock detected` y la deja a medio migrar: salen `QueryException` por todas partes que **parecen bugs del código y no lo son**. Se arregla cerrando todo y relanzando. |
| **Decisiones ya tomadas** | Antes de "arreglar" el fail-open de reCAPTCHA, los resources sin `whenLoaded` o la subida sin validar del editor, lee [decisiones-tecnicas.md](docs/info/decisiones-tecnicas.md): son deliberadas y tienen tests que las fijan. |
| **`docs/planning` NO va en git, y es a propósito** | Es material de trabajo local para preparar cómo se adapta el proyecto; no es el proyecto. `docs/planning/archived` guarda lo ya ejecutado, **no es fiable como estado actual** y es la excepción, no la norma: no existe en un clon nuevo y se borrará por completo en cuanto se verifiquen sus planificaciones. Lo que sí se versiona es `docs/apis` (oficial de terceros) y `docs/info` (técnica de esta plataforma). **No lo saques del `.gitignore`.** |
| **Envelope de respuesta** | Nunca devuelvas un array pelado desde un controlador API. Usa `BaseApiController`. |
| **Directorios vacíos sueltos** | Quedan algunos tras la fase 8 (`app/Http/Middleware/`, `app/Services/Auth/`…). Git no versiona directorios vacíos, así que sólo están en la copia local. |

---

## 13. Documentación técnica — OBLIGATORIO mantenerla al día

### Qué hay dentro de `docs/`, y qué se versiona

| Directorio | Qué es | ¿git? | ¿Se lee? |
|---|---|---|---|
| `docs/info/` | Documentación técnica de **cada módulo de esta plataforma** | ✅ **sí** | Sí: es la referencia de trabajo |
| `docs/info/apis/<api>.md` | **Cómo usamos nosotros** una API de terceros aquí | ✅ **sí** | Sí, al tocar esa integración |
| `docs/apis/<api>/` | Documentación **oficial y verificada** de esa API de terceros: endpoints, mapeos, erratas y límites reales | ✅ **sí** | **Sólo** al trabajar contra esa API |
| `docs/apis/<api>/src/` | Las fuentes originales descargadas | ✅ sí | **Nunca de rutina.** No se editan |
| `docs/planning/` | Material de trabajo **local** para preparar cómo se adapta el proyecto | ❌ **NO** | Sí, mientras se planifica |
| `docs/planning/archived/` | Planificaciones ya ejecutadas — **excepción, no fiable como estado actual, no existe en un clon nuevo** | ❌ **NO** | **No**, salvo que haga falta rescatar el porqué concreto de una decisión ya tomada |
| `docs/auditorias/` | Auditorías internas | ❌ **NO** | Bajo demanda |
| `docs/future/` | Ideas y trabajo **decididos pero aplazados** (no son deuda técnica ni bugs) | ✅ sí | **Sólo** al ir a documentar, revisar o retomar algo para guardarlo de cara al futuro. **No la leas de rutina** al trabajar en un módulo: mientras no se retome, no aporta nada y es gastar contexto de balde |

> ⛔ **`docs/planning` y `docs/auditorias` van en `.gitignore` y ahí se quedan.**
> No son parte del proyecto: son cómo lo preparamos. Si algún documento de
> planificación dice lo contrario, **manda este fichero**. Ya pasó una vez.
>
> Consecuencia práctica: **nada de `docs/info` puede enlazar a `docs/planning`**,
> porque para quien clone el repositorio sería un enlace roto.



La documentación de cada módulo está en [`docs/info/`](docs/info/README.md). **Actualizarla es
parte de la tarea, no un extra.**

### Reglas

1. **Documentación viva obligatoria:** Cada módulo o funcionalidad del proyecto debe estar documentado en `docs/info/`, y cualquier cambio en un módulo debe actualizar su documentación técnica viva en el mismo commit por protocolo estricto.
2. **Modificas un módulo** (campos, relaciones, lógica, rutas) → actualiza su `.md` en `docs/info/`.
3. **Creas un módulo** → crea su `.md` con la estructura correspondiente, e indéxalo en `docs/info/README.md` y en la tabla de este fichero.
4. **Eliminas un módulo** → elimina su `.md` y quítalo de los índices.
5. **Documentación de APIs:** Las APIs se documentan en su propio directorio dentro de `docs/info/apis/` detallando cómo las usamos e integramos en nuestra aplicación; la documentación oficial destilada de APIs de terceros se coloca en `docs/apis/` como referencia, enlazando desde `docs/info/apis/` hacia `docs/apis/` cuando se haga referencia a especificaciones oficiales.
6. **Diseño visual:** El diseño siempre se documenta en `docs/info/DESIGN.md`, donde debe incluirse toda la información técnica referente al diseño, layout y esquema de colores Tailwind CSS v4 para mantener la coherencia visual.
7. **Componentes reutilizables:** Todos los componentes Blade para frontend se documentan en `docs/info/COMPONENTS.md`, quedando prohibido maquetar botones o inputs manuales si ya existe su equivalente Blade.
8. **Auditorías de código:** Las auditorías que se soliciten sobre el código se documentan en `docs/auditorias/` y quedan excluidas de git en `.gitignore`.
9. **Dudas de planificación e implementación:** Se documentan en `docs/planning/dudas/` agrupadas, enumeradas, con hueco para responder y control de estado (`[ ] Pendiente` | `[x] Resuelta` | `[ ] Ignorada`).
10. **Fechas obligatorias:** Ver la regla 5 de §12. Formato: `> Creado: YYYY-MM-DD · Última revisión: YYYY-MM-DD`, al pie y detrás de un `---`.

### Índice

| Archivo | Módulo / Documento |
|---------|---------------------|
| `weather-station.md` | Estación meteorológica (sensores + AEMET) |
| `hardware.md` | Hardware y energía (dispositivos, cargas solares, generadores) |
| `keycounter.md` | Contador de pulsaciones (teclado y ratón) |
| `smart-plant.md` | Plantas inteligentes |
| `airflight.md` | Registro de vuelos ADS-B |
| `printers.md` | Impresoras y cola de impresión |
| `content.md` | CMS / contenidos |
| `galleries.md` | Galerías de imágenes |
| `cv.md` | Currículum vitae |
| `platform.md` | Plataformas (multi-sitio) |
| `newsletter.md` | Newsletter |
| `auth.md` | Autenticación y usuarios |
| `contact.md` | Formulario de contacto |
| `files.md` | Gestión de archivos |
| `webhooks.md` | Webhooks externos (GitLab) |
| `common.md` | Entidades comunes (categorías, tags, tecnologías, idiomas) |
| `default-images.md` | Imágenes por defecto por módulo |
| `filament-panels.md` | Paneles Filament: Resources, widgets, permisos |
| `commands.md` | Catálogo de comandos Artisan |
| `debug-commands.md` | Comandos de datos de prueba |
| `frontend.md` | Frontend público: vistas, Vite, tokens de color, componentes Vue, errores y SEO |
| `websockets.md` | WebSockets con Reverb: qué se emite, cómo se escucha y cómo se despliega |
| `mcp.md` | Model Context Protocol |
| `apis/aemet.md` | Integración con AEMET OpenData |
| `api/v2/README.md` | Índice del mapa de rutas y contratos de nuestra API V2 |
| `COMPONENTS.md` | Catálogo de componentes Blade UI reutilizables |
| `DESIGN.md` | Sistema de diseño visual y tokens Tailwind CSS v4 |
| `content_builder.md` | Constructor modular de contenido (Filament Builder + Blade) |
| `_MODULE_TEMPLATE.md` | Plantilla oficial para documentar nuevos módulos |
| `../deploys/deploy-vps.md` | Despliegue en VPS |

---

## 13-bis. Documentación de APIs externas (`docs/apis/`)

- Contiene la documentación oficial **DESTILADA Y VERIFICADA** de APIs de terceros.
- **Consúltala SOLO cuando la tarea toque una API externa.** Si no, no la leas: gasta
  contexto sin aportar nada.
- Orden de lectura obligatorio al tocar una API externa:
  1. `docs/apis/<api>/README.md` — índice y normas
  2. `docs/apis/<api>/00-fundamentos.md` + `ERRATAS.md` + `LIMITACIONES.md`
  3. Solo el archivo del dominio concreto que necesites
- **Nunca configures nada a partir de la especificación oficial de una API externa sin
  verificarlo con una petición real.** Está medido: en AEMET la especificación falla en el
  `Content-Type`, la codificación y la forma de los errores.
- `docs/apis/<api>/src/` son las fuentes originales: **NO se editan y NO se leen de rutina.**
- Cada archivo declara sus fuentes al principio, para saber qué volver a descargar al
  actualizar.
- Al documentar **CÓMO integramos** la API en este proyecto (servicios, comandos, modelos,
  caché), hazlo en la carpeta de documentación propia del proyecto, **NO** en `docs/apis/`, y
  **enlaza** al archivo concreto de `docs/apis/` en vez de repetir el dato oficial.

### Lo que hay hoy

| API | Estado |
|---|---|
| [`docs/apis/aemet/`](docs/apis/aemet/) | 64 endpoints verificados con petición real (2026-08-26). 16 archivos + `ERRATAS.md` y `LIMITACIONES.md` |
| [`docs/apis/open-meteo/`](docs/apis/open-meteo/) | **En reserva, sin integrar.** 16 endpoints verificados (2026-09-01), traída de otro proyecto como alternativa o complemento futuro a AEMET. **Ignórala salvo que se pida explícitamente trabajar con Open-Meteo** — hoy no hay ninguna integración en el código. |

### AEMET — las cinco trampas que rompen la integración en silencio

Resumen para no tener que releerlo todo. **El detalle está en los archivos, no aquí.**

1. **Dos saltos.** El endpoint devuelve un sobre `{descripcion, estado, datos, metadatos}`;
   los datos están en la URL `datos`, sin autenticación y **efímera**. Se consume una vez y
   se persiste: **nunca se guarda ni se referencia esa URL**. Excepción: `balancehidrico` y
   `resumenclimatologico` devuelven el PDF directo, sin sobre.
2. **Codificación variable.** La mayoría responde en **ISO-8859-15** y algunos productos en
   UTF-8 real. Hay que **leer el `charset` de la cabecera `Content-Type` y respetarlo**: con
   los primeros `json_decode` devuelve `null` **en silencio**; a los segundos, convertirlos
   los corrompe.
3. **Un HTTP 200 no significa que haya datos.** Puede traer `estado: 404` en el cuerpo,
   cuerpo vacío (falta la `api_key`, o el periodo no existe) o **datos de años atrás**. Hay
   que validar el estado del **cuerpo** y la **frescura** del contenido (`elaborado`, o la
   fecha de cabecera en los productos de texto).
4. **Cuota indocumentada.** Cabecera `Remaining-request-endpoint`: **40 peticiones por
   PLANTILLA de endpoint** (no por URL), menos en productos pesados, y **ligada a la IP**
   además de a la clave. El 429 **no trae `Retry-After`** y la recuperación tarda más de una
   hora. Espaciar, rotar entre familias y hacer backoff a ciegas.
5. **No todo es JSON.** 22 de 64 endpoints devuelven texto plano, y hay GIF, PNG, `tar` sin
   comprimir, gzip, ZIP, PDF y CSV. El `Content-Type` **no** distingue el `tar` del gzip:
   comprobar el magic (`1f8b` = gzip; `ustar` en el offset 257 = tar plano).

**Y dos reglas de operación:**

- La **API Key es un JWT que caduca** (~100 días). Va en `.env` como `AEMET_API_KEY`, en la
  cabecera `api_key` — **nunca en la query string**, que la filtra a los logs. Guardar también
  `AEMET_API_KEY_EXPIRES_AT` para avisar antes de que caduque. Renovar en
  <https://opendata.aemet.es/centrodedescargas/altaUsuario>.
- **La web NUNCA llama a AEMET en la petición del usuario.** Un proceso en segundo plano trae
  los datos y los persiste; la web lee de la base de datos.

---

## 14. Convenciones de código

- **Estilo:** PSR-12, formateado con `./vendor/bin/pint` (o atajo `composer check` / `composer pint`).
- **`declare(strict_types=1);`** en todos los ficheros PHP del proyecto.
- **Tipado estricto:** Todas las funciones y métodos de la aplicación deben estar estrictamente tipados (parámetros y tipos de retorno explícitos) y documentados.
- **Naming:** PascalCase para clases, camelCase para métodos, snake_case para tablas y columnas.
- **Enums:** backed enums de PHP 8.4 en `app/Enums/` con sufijo `Enum`.
- **Traits:** reutiliza los de `app/Traits/` antes de duplicar lógica.
- **Modelos:** extienden `App\Models\BaseModels\BaseModel` y deben estar documentados con PHPDoc completo para propiedades (`@property`), relaciones (`@property-read`) y métodos (`@method`).
- **Migraciones:** Todas las migraciones deben estar comentadas explicando el propósito de tablas y columnas con `->comment('...')`.
- **Controladores API:** extienden `BaseApiController`.
- **Validación:** siempre en FormRequests bajo `app/Http/Requests/`, nunca en el controlador. Formularios con reglas permisivas pero seguras.
- **Filament y formularios:** Todo error de formulario se gestiona mediante notificaciones nativas de Filament, sin exponer bloques de HTML crudo.
- **Subida de imágenes backend:** Todo campo `FileUpload` para imágenes se implementará con cropper/editor por defecto (`->imageEditor()` o componente `ImageCropperUpload`).
- **Metadatos de imágenes:** toda subida limpia los metadatos EXIF/GPS **antes** de escribir los propios de la plataforma. Son dos pasos distintos y explícitos, y el primero se hace **siempre**, aunque la librería de imagen ya los descarte por su cuenta: hoy los pierde el driver por casualidad, y el día que cambie el driver nadie va a volver a mirar esto. Aplica a **todas** las imágenes, privadas y públicas. La rotación se conserva rotando los píxeles de verdad, no dejando el flag EXIF. Se resuelve en `File::addFile()`, no en cada formulario. Ver [decisiones-tecnicas.md](docs/info/decisiones-tecnicas.md) D3-D6.
- **Validación de subida:** `File::addFile()` valida por defecto contra `File::SAFE_MIMES`. Los campos que esperan una imagen usan el valor por defecto; el editor de contenido y los adjuntos llaman con `validate: false` y aceptan lo que sea. **La tabla `file_types` NO es fuente de validación** (D2).
- **Lógica de negocio:** en `app/Services/`, no en controladores ni en modelos.
- **Autorización:** en Policies + `authorize()` en el controlador o en el FormRequest.
- **Tests automatizados:** Toda funcionalidad implementada tiene que tener tests automatizados que la validen para seguir funcionando (`php artisan test --compact`).
- **Base de datos:** Se utiliza **siempre PostgreSQL** (en local, testing y producción).
- **Reglas de validación reutilizables para Filament:** `app/Support/FilamentValidationRules.php`.

---

> Última revisión: 2026-08-30
