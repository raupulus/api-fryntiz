#!/usr/bin/env bash
#
# Despliegue en producción.
#
# Cambios respecto a la versión anterior:
#
#  - Usaba `npm run build` y el proyecto es **pnpm** (hay pnpm-lock.yaml y
#    pnpm-workspace.yaml). Con npm se resuelven versiones distintas de las del
#    lockfile y se despliega algo que nadie ha probado.
#  - No tenía `set -e`: si fallaba la migración, seguía cacheando rutas y
#    levantaba la aplicación igual, con la base de datos a medias.
#  - Dejaba la aplicación en mantenimiento para siempre si algo petaba.
#  - No reiniciaba los workers de cola. Un worker es un proceso de PHP de larga
#    vida: se queda con el código viejo en memoria hasta que se le dice que pare.
#
#  - NO llama a `project:clear`: ese comando regenera la APP_KEY salvo que se le
#    pase `--no-key`, y en un pipeline automático eso cerraría la sesión de todo
#    el mundo en cada despliegue. Aquí se cachea directamente lo que hace falta.
#
set -euo pipefail

cd "$(dirname "$0")/.."

# Pase lo que pase, sacar la aplicación de mantenimiento al salir.
trap 'php artisan up >/dev/null 2>&1 || true' EXIT

echo "==> Modo mantenimiento"
php artisan down --render="errors::503" --retry=60

echo "==> Comprobación previa"
# El entorno queda CONGELADO dentro de las cachés que se generan más abajo. Si
# se cachearan las rutas con un `APP_ENV` que no sea `production`, el servidor
# MCP —tres rutas HTTP sin autenticación, con herramientas que leen el esquema
# de la base de datos y ejecutan procesos— entraría en la caché y quedaría
# publicado. `routes/ai.php` sólo se registra fuera de producción, pero esa
# comprobación se evalúa al cachear, no al servir.
APP_ENV_ACTUAL="$(php -r 'echo trim((string) (parse_ini_file(".env")["APP_ENV"] ?? ""));')"
if [ "$APP_ENV_ACTUAL" != "production" ]; then
    echo "ERROR: APP_ENV es '${APP_ENV_ACTUAL:-vacío}' y debe ser 'production'." >&2
    exit 1
fi

echo "==> Dependencias PHP"
# Las cachés de paquetes descubiertos se borran ANTES de instalar. Si el
# workspace viene de una ejecución con dependencias de desarrollo —cosa normal
# en un agente de GoCD, que reutiliza directorio—, `package:discover` intenta
# cargar providers que acaban de desaparecer (Debugbar, Scribe) y aborta el
# despliegue.
rm -f bootstrap/cache/packages.php bootstrap/cache/services.php

composer install --optimize-autoloader --no-dev --no-interaction --prefer-dist

echo "==> Migraciones"
php artisan migrate --force

echo "==> Catálogos"
# `ProductionSeeder` es `DatabaseSeeder` MENOS el seeder de usuarios (que crea
# `superadmin@domain.es` con la contraseña `123123`). Sus seeders comprueban si
# la fila ya existe antes de insertar y ninguno hace `truncate` ni `delete`, así
# que sobre la base de datos poblada del servidor no toca un solo dato: lo único
# que hace es rellenar los catálogos nuevos que traiga la versión desplegada.
php artisan db:seed --class=ProductionSeeder --force

echo "==> Assets"
pnpm install --frozen-lockfile
pnpm run build

echo "==> Cachés"
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
# Componentes de Filament e iconos Blade. Sin esto el panel resuelve cada
# componente en cada petición.
php artisan filament:optimize

echo "==> Enlace de almacenamiento"
php artisan storage:link || true

echo "==> Reinicio de los workers de cola"
# Los workers se enteran en su siguiente ciclo y el supervisor los relanza con
# el código nuevo. Sin esto seguirían ejecutando el anterior.
php artisan queue:restart

echo "==> Fin"
