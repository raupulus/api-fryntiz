# Api Raupulus

Plataforma multi-API desarrollada con Laravel 13 que centraliza módulos IoT (estación meteorológica, plantas inteligentes, contador de pulsaciones, registro de vuelos, energía solar), gestión de contenidos multi-plataforma, currículum vitae y newsletter.

**Autor:** Raúl Caro Pastorino — [raupulus.dev](https://raupulus.dev)

## Stack Tecnológico

| Componente | Tecnología |
|------------|-----------|
| **Backend** | PHP 8.4, Laravel 13 |
| **Panel Admin** | Filament 5 (Livewire 4) |
| **Frontend** | Blade + Tailwind CSS 4 + Alpine.js 3 |
| **Bundler** | Vite 6 |
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
- **API:** Versionada (V1 legacy + V2 actual), respuestas JSON con JsonResources
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
├── api/v1.php            # API V1 (legacy)
├── api/v2.php            # API V2 (actual)
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

```bash
# Clonar repositorio
git clone https://gitlab.com/raupulus/api-fryntiz.git api-raupulus
cd api-raupulus

# Instalación automática
php artisan project:install

# O manualmente:
cp .env.example .env
composer install
npm install
npm run build
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
php artisan serve
```

## Instalación (Producción)

```bash
cp .env.example.production .env
# Editar .env con las credenciales de producción
composer install --optimize-autoloader --no-dev
npm install
npm run build
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

La API tiene dos versiones:

- **V1** (`/api/v1/...`): Versión original, mantenida por compatibilidad
- **V2** (`/api/v2/...`): Versión actual con JsonResources, validaciones mejoradas y mayor seguridad

La documentación detallada de la API V2 está en [`docs/api-v2.md`](docs/api-v2.md).

### Autenticación API

Los endpoints protegidos requieren un token Bearer de Laravel Sanctum. Los tokens se obtienen mediante el endpoint de login.

## Paneles de Administración

- **Admin** (`/admin`): Gestión completa del sistema — solo superadministradores
- **Panel** (`/panel`): Panel de usuario — acceso a recursos propios

## Comandos Útiles

```bash
# Proyecto
php artisan project:install              # Inicializar proyecto completo
php artisan project:clear                # Limpiar cachés
php artisan project:clear --production   # Limpiar y recachear para producción

# Contenido
php artisan content:publish              # Publicar contenidos programados

# SEO
php artisan sitemap:generate             # Generar sitemap.xml

# AEMET (datos meteorológicos de la agencia estatal)
php artisan aemet:daily                  # Obtener datos diarios de AEMET
php artisan aemet:every-10m              # Datos cada 10 minutos
php artisan aemet:every-30m              # Datos cada 30 minutos
php artisan aemet:every-4h               # Datos cada 4 horas

# KeyCounter
php artisan keycounter:generate-duration # Generar duraciones de sesiones
php artisan keycounter:remove-duplicate  # Eliminar registros duplicados

# Limpieza
php artisan force:clear                  # Limpieza forzada de cachés
```

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
| Citación académica | [`CITATION.txt`](CITATION.txt) |

## Convenciones

- **Idioma del código:** Inglés (variables, métodos, clases)
- **Idioma de documentación:** Español (PHPDoc, comentarios, mensajes de validación)
- **Estilo:** PSR-12, principios SOLID
- **Naming:** Convenciones Laravel (PascalCase clases, camelCase métodos, snake_case tablas/columnas)
- **Base de datos:** Migraciones con `comment` en todas las columnas, foreign keys explícitas

## Licencia

Este proyecto está licenciado bajo **GNU General Public License v3.0** — ver archivo [`LICENSE`](LICENSE) para más detalles.
