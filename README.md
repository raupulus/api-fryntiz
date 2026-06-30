# Api Raupulus

Plataforma multi-API desarrollada con Laravel 13 que centraliza módulos IoT (estación meteorológica, plantas inteligentes, contador de pulsaciones, registro de vuelos, energía solar), gestión de contenidos multi-plataforma, currículum vitae y newsletter.

**Autor:** Raúl Caro Pastorino — [raupulus.dev](https://raupulus.dev)

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
| **Weather Station** | Datos meteorológicos de sensores (temperatura, humedad, presión, viento, lluvia, calidad del aire, rayos) + integración AEMET (predicciones, eventos adversos, costas, ozono, radiación solar) |
| **Hardware / Energy** | Gestión de dispositivos hardware, monitorización de energía solar, generadores y consumos con resúmenes diarios |
| **KeyCounter** | Registro de pulsaciones de teclado, clicks y movimientos de ratón con estadísticas por usuario y dispositivo |
| **AirFlight** | Registro de aviones detectados, rutas y telemetría |
| **Smart Plant** | Monitorización de plantas con sensores de humedad del suelo, luz y temperatura |
| **Content (CMS)** | CMS multi-plataforma y multi-tipo (artículo, tutorial, proyecto, página, reseña) con SEO, categorías, tags, tecnologías y galerías |
| **CV** | Currículum vitae completo con 16 secciones (experiencia, formación, habilidades, proyectos, repositorios) y generación PDF (DomPDF) |
| **Newsletter** | Gestión de suscriptores con verificación por email y baja por token |

## Arquitectura

- **Patrón:** MVC con Service Layer
- **API:** Única versión **V2 FULL REST** (la V1 legacy fue eliminada), respuestas JSON con JsonResources
- **Admin:** Dos paneles Filament 5 (Admin para superadmin, Tenant para usuarios)
- **Roles:** SuperAdmin (1), Admin (2), User (3)

### Estructura de Directorios

```
app/
├── Actions/              # Operaciones atómicas reutilizables
├── Console/Commands/     # Comandos Artisan (AEMET, content, project, sitemap, keycounter)
├── Enums/                # PHP 8.4 backed enums
├── Filament/
│   ├── Admin/            # Panel Admin (superadmin)
│   └── Tenant/           # Panel Tenant (usuarios)
├── Http/
│   ├── Controllers/Api/  # Controladores API por módulo/versión
│   ├── Controllers/      # Controladores web públicos
│   ├── Middleware/        # Cors, DomainCheck, IpCounter
│   ├── Requests/         # FormRequests
│   └── Resources/V2/     # JsonResources para API V2
├── Models/               # Eloquent models por módulo
├── Services/             # Service Layer por módulo
└── Traits/               # Traits compartidos (HasSlug, HasStatus, Filterable, etc.)

routes/
├── web.php               # Rutas frontend público
├── api/v2.php            # API V2 (única versión)
├── console.php           # Scheduler
├── weather_station/      # Rutas Weather Station (web + API)
├── hardware/             # Rutas Hardware/Energy (web + API)
├── keycounter/           # Rutas KeyCounter (web + API)
├── smart_plant/          # Rutas Smart Plant (web + API)
├── airflight/            # Rutas AirFlight (web + API)
└── cv/                   # Rutas CV (web + API)

docs/info/                # Documentación técnica de cada módulo
```

## Requisitos

- PHP >= 8.4
- PostgreSQL >= 15
- Redis >= 7 (opcional, recomendado)
- Node.js >= 20
- Composer >= 2.7

## Instalación (Desarrollo)

> **Gestor de paquetes JS**: este proyecto **prefiere `pnpm`** sobre `npm`. Instálalo con `npm install -g pnpm` si aún no lo tienes.

```bash
# Clonar repositorio
git clone https://gitlab.com/raupulus/api-fryntiz.git api-raupulus
cd api-raupulus

# Instalación automática
php artisan project:install

# O manualmente:
cp .env.example .env
composer install
pnpm install
pnpm run build
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
php artisan serve
```

## Instalación (Producción)

Ver guía completa en [`docs/deploys/deploy-vps.md`](docs/deploys/deploy-vps.md) (Docker + bare-metal).

Resumen rápido:

```bash
cp .env.example.production .env
# Editar .env con las credenciales de producción
composer install --optimize-autoloader --no-dev
pnpm install --frozen-lockfile
pnpm run build
php artisan key:generate
php artisan migrate --force
php artisan db:seed --force
php artisan storage:link
php artisan project:clear --production
```

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

La documentación detallada de la API V2 está en [`docs/api-v2.md`](docs/api-v2.md).

### Autenticación API

Los endpoints protegidos requieren un token Bearer de Laravel Sanctum. Los tokens se obtienen mediante el endpoint de login.

## Paneles de Administración

- **Admin** (`/admin`): Gestión completa del sistema — solo superadministradores
- **Panel** (`/panel`): Panel de usuario — acceso a recursos propios

## Comandos Útiles

Catálogo completo en [`docs/info/commands.md`](docs/info/commands.md). Los más habituales:

```bash
php artisan project:install              # Inicializar proyecto completo
php artisan project:clear                # Limpiar cachés
php artisan content:publish              # Publicar contenidos programados
php artisan sitemap:generate             # Generar sitemap.xml
php artisan debug:seed-all               # Poblar la base de datos para desarrollo
```

Para el listado de comandos AEMET, KeyCounter, debug y resto, ver [`docs/info/commands.md`](docs/info/commands.md).

## Tests

```bash
php artisan test
```

## Documentación

| Recurso | Ubicación |
|---------|-----------|
| Arquitectura y convenciones | [`AGENTS.md`](AGENTS.md) |
| API V2 | [`docs/api-v2.md`](docs/api-v2.md) |
| Documentación de módulos | [`docs/info/`](docs/info/) |
| Catálogo de comandos Artisan | [`docs/info/commands.md`](docs/info/commands.md) |
| Despliegue en VPS | [`docs/deploys/deploy-vps.md`](docs/deploys/deploy-vps.md) |
| Integración AEMET | [`docs/info/apis/aemet.md`](docs/info/apis/aemet.md) |
| WebSockets (Reverb) | [`docs/info/websockets.md`](docs/info/websockets.md) |
| Citación académica | [`CITATION.txt`](CITATION.txt) |

## Convenciones

- **Idioma del código:** Inglés (variables, métodos, clases)
- **Idioma de documentación:** Español (PHPDoc, comentarios, mensajes de validación)
- **Estilo:** PSR-12, principios SOLID
- **Naming:** Convenciones Laravel (PascalCase clases, camelCase métodos, snake_case tablas/columnas)
- **Base de datos:** Migraciones con `comment` en todas las columnas, foreign keys explícitas

## Licencia

Este proyecto está licenciado bajo **GNU General Public License v3.0** — ver archivo [`LICENSE`](LICENSE) para más detalles.
