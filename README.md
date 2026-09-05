# Api Raupulus

Plataforma multi-API desarrollada con Laravel 13 que centraliza módulos IoT (estación meteorológica, plantas inteligentes, contador de pulsaciones, registro de vuelos, energía solar), gestión de contenidos multi-plataforma, currículum vitae y newsletter.

**Autor:** Raúl Caro Pastorino (@raupulus) — [raupulus.dev](https://raupulus.dev)

> 📍 **¿Retomando el proyecto?** Empieza por [`AGENTS.md`](AGENTS.md) (fuente de verdad de
> arquitectura, rutas y convenciones) y por [`docs/info/`](docs/info/README.md) (documentación
> técnica de cada módulo). `docs/planning/` es material de trabajo local, no se versiona en git.

## Stack Tecnológico

| Componente | Tecnología |
|------------|-----------|
| **Backend** | PHP 8.4, Laravel 13 |
| **Panel Admin** | Filament 5 (Livewire 4) |
| **Frontend** | Blade + Tailwind CSS 4 + Alpine.js 3 |
| **Bundler** | Vite 8 |
| **Base de datos** | PostgreSQL 17 |
| **Caché** | Redis 7 |
| **Autenticación Web** | Laravel Fortify |
| **Autenticación API** | Laravel Sanctum 4 |
| **Imágenes** | Intervention Image 3 |
| **SEO** | Spatie Laravel Sitemap 8 |
| **reCAPTCHA** | Google reCAPTCHA |

## Módulos

| Módulo | Descripción |
|--------|-------------|
| **Weather Station** | Datos meteorológicos de sensores (temperatura, humedad, presión, viento, lluvia, calidad del aire, rayos) + integración AEMET (predicciones, eventos adversos, costas, ozono, radiación solar). |
| **Hardware / Energy** | Gestión de dispositivos hardware, monitorización de energía solar, generadores y consumos con resúmenes diarios. |
| **KeyCounter** | Registro de pulsaciones de teclado, clicks y movimientos de ratón con estadísticas por usuario y dispositivo. |
| **AirFlight** | Registro de aviones detectados, rutas y telemetría. |
| **Smart Plant** | Monitorización de plantas con sensores de humedad del suelo, luz y temperatura. |
| **Content (CMS)** | CMS multi-plataforma y multi-tipo (artículo, tutorial, proyecto, página, reseña) con SEO, categorías, tags, tecnologías y galerías. |
| **CV** | Currículum vitae completo con 16 secciones (experiencia, formación, habilidades, proyectos, repositorios) y descarga del PDF del currículum. |
| **Newsletter** | Gestión de suscriptores con verificación por email y baja por token. |
| **Auth** | Autenticación web (Fortify) y API V2 (Sanctum). |
| **Contact** | Formulario de contacto con validación reCAPTCHA. |
| **Platform** | Gestión de plataformas/sitios. |
| **User** | Gestión de usuarios y perfiles. |
| **Webhook** | Gestión de webhooks externos (GitLab). |

## Arquitectura

- **Patrón:** MVC con Service Layer
- **API:** Única versión **V2 FULL REST** (la V1 legacy fue eliminada), respuestas JSON con JsonResources
- **Admin:** Dos paneles Filament 5 (Admin para superadmin, Tenant para usuarios)
- **Roles:** SuperAdmin (1), Admin (2), User (3)

### Estructura de Directorios

```
app/
├── Actions/              # Operaciones atómicas reutilizables
├── Helpers/              # TextFormatParseHelper (EditorJS → HTML)
├── Console/Commands/     # Comandos Artisan (AEMET, content, project, sitemap, keycounter)
├── Enums/                # PHP 8.4 backed enums
├── Filament/
│   ├── Admin/            # Panel Admin (superadmin)
│   └── Tenant/           # Panel Tenant (usuarios)
├── Http/
│   ├── Controllers/Api/  # Controladores API por módulo/versión
│   ├── Controllers/      # Controladores web públicos
│   ├── Middleware/       # (vacío: los 3 que había estaban muertos, fase 8)
│   ├── Requests/         # FormRequests
│   └── Resources/V2/     # JsonResources para API V2
├── Models/               # Eloquent models por módulo
├── Services/             # Service Layer por módulo
├── Support/              # Lectores y utilidades sin estado (CapWarnings, AemetApiKey, …)
└── Traits/               # Traits compartidos (HasSlug, HasStatus, Filterable, etc.)

support/helpers/          # Helpers globales sin namespace (JsonHelper, AEMETHelper)
_to_delete/               # Código retirado, con el porqué en POR-QUE-ESTAN-AQUI.md.
                          # Se vacía cuando terminen todas las fases, no antes.

routes/
├── web.php               # Rutas frontend público
├── api/v2.php            # API V2 (única versión)
├── console.php           # Scheduler
├── weather_station/      # Rutas Weather Station (web + API)
├── hardware/             # Rutas Hardware/Energy (web + API)
├── keycounter/           # Rutas KeyCounter (web + API)
├── smart_plant/          # Rutas Smart Plant (web + API)
├── airflight/            # Rutas AirFlight (web + API)
├── cv/                   # Rutas CV (web + API)
├── webhook.php           # Rutas Webhook (GitLab)
└── ai.php                # Rutas AI (MCP)

docs/info/                # Documentación técnica de cada módulo
```

## Requisitos

- **PHP** >= 8.4
- **Base de datos:** PostgreSQL >= 17 (**siempre PostgreSQL**, tanto en desarrollo, testing y producción)
- **Caché y Colas:** opcional. Las plantillas de `.env` vienen con
  `CACHE_STORE=file`, `SESSION_DRIVER=file` y `QUEUE_CONNECTION=database`, que
  funcionan sin instalar nada más —la cola necesita las tablas `jobs` y
  `job_batches`, que crean las migraciones—. Redis >= 7 es una mejora de
  rendimiento, no un requisito para arrancar
- **Node.js** >= 20
- **Gestor de paquetes JS:** `pnpm` (recomendado y predeterminado)
- **Composer** >= 2.7

## Variables de entorno que hay que rellenar a mano

Estas no tienen un valor por defecto que sirva en producción. Si se dejan vacías
el sistema arranca igual, pero degradado y **sin dar ningún error**, que es la
peor forma de fallar.

| Variable | Qué pasa si no se pone | Formato |
|---|---|---|
| `TRUSTED_PROXIES` | Detrás de nginx o Cloudflare, `request()->ip()` devuelve la IP del proxy. Todos los rate limit por IP —incluido el del login— pasan a ser **un cupo global compartido por todos los visitantes**: ni frenan a un atacante ni dejan pasar el tráfico legítimo. | IPs o rangos CIDR separados por comas. Por defecto los rangos privados. **Nunca `*`** si la aplicación puede alcanzarse sin pasar por el proxy. |
| `SANCTUM_STATEFUL_DOMAINS` | Los dominios que no estén aquí no reciben cookie de sesión. | **Sólo el dominio**, sin esquema ni `www`, separados por comas: `raupulus.dev,laguialinux.com`. Las variantes las deriva `config/sanctum.php`, y fuera de producción añade solo los `localhost`. |
| `FRONTEND_URLS` | CORS rechaza al frontend. | **URL completa con esquema**, separadas por comas: `https://raupulus.dev,https://laguialinux.com`. Ojo, formato distinto al de arriba. |
| `API_SESSION_DAYS` | Por defecto 30. | Días que vive el token que emite `POST /api/v2/auth/login` para una persona. Los tokens de dispositivo IoT **no caducan a propósito**: su seguridad son las abilities, no el tiempo. |
| `AEMET_API_KEY_EXPIRES_AT` | La clave de AEMET es un JWT que **caduca a los ~100 días**, y su caducidad **no da error**: AEMET responde 200 con el cuerpo vacío, que en los logs es idéntico a «hoy no hay datos». Sin esta fecha nadie se entera hasta que echa de menos un dato semanas después. | Fecha en `Y-m-d`, apuntada a mano al renovar la clave. `aemet:check-api-key` avisa 15 días antes; se renueva en <https://opendata.aemet.es/centrodedescargas/altaUsuario>. |

Las plantillas `.env.example` y `.env.example.production` llevan el mismo texto
en un comentario junto a cada variable.

## Instalación y Despliegue (Desarrollo)

> **Gestor de paquetes JS**: este proyecto utiliza **`pnpm`**. Instálalo con `npm install -g pnpm` si aún no lo tienes.

```bash
# 1. Clonar repositorio
git clone https://gitlab.com/raupulus/api-fryntiz.git api-raupulus
cd api-raupulus

# 2. Configuración de entorno
cp .env.example .env

# 3. Instalación de dependencias
composer install
pnpm install
pnpm run build

# 4. Clave de aplicación y base de datos PostgreSQL
php artisan key:generate
php artisan migrate --seed
php artisan storage:link

# 5. Poblar datos corporativos realistas de ejemplo (opcional para desarrollo/demo)
php artisan project:dummy

# 6. Iniciar servidor local
php artisan serve
```

Alternativamente, el comando `php artisan project:install` automatiza la inicialización inicial del entorno.

## Instalación y Despliegue (Producción)

Ver guía completa y arquitectura de servidores en [`docs/deploys/deploy-vps.md`](docs/deploys/deploy-vps.md) (Docker + bare-metal).

Pasos esenciales de despliegue:

```bash
# 1. Configurar entorno de producción
cp .env.example.production .env
# Configurar credenciales reales de PostgreSQL, URLs y correo en .env, y las
# cuatro variables de la sección "Variables de entorno que hay que rellenar a
# mano" (TRUSTED_PROXIES, SANCTUM_STATEFUL_DOMAINS, FRONTEND_URLS,
# API_SESSION_DAYS).
#
# Sin Redis no hay que tocar nada: la plantilla ya viene con caché y sesión en
# fichero y la cola en base de datos.
#
# Rellenar también RECAPTCHA_SITE_KEY y RECAPTCHA_SECRET_KEY: sin ellas los
# formularios públicos y el login de los paneles se quedan SIN captcha y sin
# ningún aviso.

# 2. Instalar dependencias optimizadas
composer install --optimize-autoloader --no-dev
pnpm install --frozen-lockfile
pnpm run build

# 3. Migraciones, catálogos y enlaces de almacenamiento
php artisan migrate --force

#    ProductionSeeder = DatabaseSeeder MENOS el seeder de usuarios (que crea
#    superadmin@domain.es con la contraseña 123123). Nunca `db:seed` a secas en
#    un servidor. Sus catálogos comprueban si la fila existe antes de insertar y
#    ninguno borra nada: es idempotente y no toca los datos ya presentes.
php artisan db:seed --class=ProductionSeeder --force

php artisan storage:link

#    Primer administrador, sólo en el primer despliegue.
php artisan user:make-admin --superadmin

# 4. Limpieza y compilación de cachés de producción
#
#    OJO: `project:clear` REGENERA LA APP_KEY salvo que se le pase `--no-key`.
#    Es deliberado en este proyecto —cierra todas las sesiones abiertas y obliga
#    a los clientes a recargar—, pero tiene una consecuencia que no se ve venir:
#    el 2FA de Fortify guarda `two_factor_secret` cifrado con esa clave, así que
#    quien lo tuviera activo se queda sin poder completar el segundo factor y hay
#    que volver a dárselo de alta. Los tokens de API de Sanctum NO se ven
#    afectados: se guardan hasheados, no cifrados.
#
#    Para desplegar sin tocar la clave:  php artisan project:clear --production --no-key
php artisan project:clear --production

# 5. Supervisor y Cron  (los dos son obligatorios, no opcionales)
#
# 5a. Worker de colas. Sin él los correos y el contador de visitas se quedan
#     encolados para siempre, porque QUEUE_CONNECTION es `database`:
#     php artisan queue:work --queue=default --sleep=3 --tries=3 --max-time=3600
#
# 5b. Cron del scheduler. Sin él no se actualiza AEMET, ni se publica el
#     contenido programado, ni se genera el sitemap:
#   * * * * * cd /ruta/al/proyecto && php artisan schedule:run >> /dev/null 2>&1
```

> Para comprobar que el planificador está bien: `php artisan schedule:list`
> enseña qué se ejecuta y cuándo. Y `php artisan test --filter=Planificador`
> falla si alguna tarea programada o algún botón del panel de AEMET llama a un
> comando que no existe — que es exactamente lo que pasaba antes y no se veía.

## Docker

```bash
# Desarrollo
docker compose up -d

# Producción
docker compose -f docker-compose.yml -f docker-compose.prod.yml up -d
```

Los servicios disponibles son:

| Servicio | Puerto por defecto | Descripción |
|----------|-------------------|-------------|
| **app** | — | Aplicación PHP-FPM |
| **nginx** | 8080 | Servidor web |
| **postgres** | 5432 | Base de datos PostgreSQL 17 |
| **redis** | 6379 | Caché y colas Redis 7 |

## API

La API tiene una única versión:

- **V2** (`/api/v2/...`): Versión actual y única, FULL REST, con JsonResources, validaciones mejoradas y mayor seguridad. La V1 legacy fue eliminada por completo.

La documentación interactiva se genera con Scribe y se sirve en `/docs`, detrás
de login. **Se genera en local y se sube ya compilada**: Scribe es una
dependencia de desarrollo y en el servidor no se instala. Procedimiento completo
en [`docs/info/scribe.md`](docs/info/scribe.md).

El contrato detallado y copiable de cada módulo (endpoints, auth, parámetros,
forma de la respuesta) vive en [`docs/info/api/v2/`](docs/info/api/v2/), un
archivo por módulo.

### Autenticación API

Los endpoints protegidos requieren un token Bearer de Laravel Sanctum con sus respectivas *abilities* por módulo (`api-session` para usuarios, o tokens dedicados por dispositivo IoT).

## Paneles de Administración

- **Admin** (`/admin`): Gestión completa del sistema — para SuperAdmin, Admin y Editor.
- **Panel** (`/panel`): Panel de usuario / inquilino para acceso a recursos propios.

## Comandos Útiles

Catálogo completo en [`docs/info/commands.md`](docs/info/commands.md). Los comandos principales del proyecto son:

```bash
php artisan project:install              # Inicializar proyecto completo (desarrollo)
php artisan project:clear                # Limpiar cachés, colas, regenerar clave y recomponer autoload
php artisan project:dummy                # Poblar base de datos con contenido corporativo realista
php artisan sitemap:generate             # Generar sitemap.xml navegable del sitio
php artisan content:publish              # Publicar contenidos programados vencidos
php artisan debug:seed-all               # Poblar datos técnicos de depuración por módulo
```

## Verificación de Código y Tests

La suite de pruebas corre siempre contra **PostgreSQL real** (no SQLite), en la base de datos `raupulus_api_test`.
Créala una vez si aún no existe:

```bash
createdb raupulus_api_test        # o: psql -c "CREATE DATABASE raupulus_api_test;"
```

Atajos de calidad en `composer`:

```bash
composer check                            # Pint + Tests compactos automatizados
composer pint                             # Formateo de código PSR-12
composer phpstan                          # Análisis estático (PHPStan nivel 5)
php artisan test --compact                # Ejecutar tests PHPUnit
```

## Documentación

| Recurso | Ubicación |
|---------|-----------|
| Estado actual del proyecto | [`docs/planning/ESTADO-ACTUAL.md`](docs/planning/ESTADO-ACTUAL.md) |
| Roadmap de próximos pasos | [`docs/planning/roadmap/`](docs/planning/roadmap/README.md) |
| Arquitectura y convenciones | [`AGENTS.md`](AGENTS.md) |
| Catálogo de componentes Blade UI | [`docs/info/COMPONENTS.md`](docs/info/COMPONENTS.md) |
| Sistema de diseño (Tailwind CSS v4) | [`docs/info/DESIGN.md`](docs/info/DESIGN.md) |
| Paneles Filament | [`docs/info/filament-panels.md`](docs/info/filament-panels.md) |
| API V2 (contratos por módulo) | [`docs/info/api/v2/`](docs/info/api/v2/) |
| Documentación de módulos | [`docs/info/`](docs/info/) |
| Catálogo de comandos Artisan | [`docs/info/commands.md`](docs/info/commands.md) |
| Despliegue en VPS | [`docs/deploys/deploy-vps.md`](docs/deploys/deploy-vps.md) |
| Integración AEMET | [`docs/info/apis/aemet.md`](docs/info/apis/aemet.md) |
| WebSockets — cómo funciona (implementado, apagado de fábrica) | [`docs/info/websockets.md`](docs/info/websockets.md) |
| WebSockets — puesta en marcha en el VPS | [`docs/deploys/websockets-reverb.md`](docs/deploys/websockets-reverb.md) |
| Documentación de la API (Scribe) | [`docs/info/scribe.md`](docs/info/scribe.md) |
| Citación académica | [`CITATION.txt`](CITATION.txt) |

## Convenciones

- **Idioma del código:** Inglés (variables, métodos, clases)
- **Idioma de documentación:** Español (PHPDoc, comentarios, mensajes de validación)
- **Estilo:** PSR-12, principios SOLID
- **Naming:** Convenciones Laravel (PascalCase clases, camelCase métodos, snake_case tablas/columnas)
- **Base de datos:** Migraciones con `comment` en todas las tablas y columnas, foreign keys explícitas
- **Componentes UI:** Obligatoriedad de reutilizar `<x-button>`, `<x-input>`, etc. (ver `docs/info/COMPONENTS.md`)

## Licencia

Este proyecto está licenciado bajo **GNU General Public License v3.0** — ver archivo [`LICENSE`](LICENSE) para más detalles.

---

> Última revisión de este documento: 2026-08-26
