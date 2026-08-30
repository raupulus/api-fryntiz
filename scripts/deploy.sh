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
set -euo pipefail

cd "$(dirname "$0")/.."

# Pase lo que pase, sacar la aplicación de mantenimiento al salir.
trap 'php artisan up >/dev/null 2>&1 || true' EXIT

echo "==> Modo mantenimiento"
php artisan down --render="errors::503" --retry=60

echo "==> Dependencias PHP"
composer install --optimize-autoloader --no-dev --no-interaction --prefer-dist

echo "==> Migraciones"
php artisan migrate --force

echo "==> Assets"
pnpm install --frozen-lockfile
pnpm run build

echo "==> Cachés"
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

echo "==> Enlace de almacenamiento"
php artisan storage:link || true

echo "==> Reinicio de los workers de cola"
# Los workers se enteran en su siguiente ciclo y el supervisor los relanza con
# el código nuevo. Sin esto seguirían ejecutando el anterior.
php artisan queue:restart

echo "==> Fin"
