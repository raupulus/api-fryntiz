#!/usr/bin/env bash
#
# Despliegue disparado por webhook.
#
# Lo que estaba mal y por qué importa:
#
#  - `npm install --production` en un proyecto pnpm, y encima sin construir
#    nada: instalaba dependencias y no generaba ni un asset.
#  - Escribía el log en `storage/logs/script-api-deploy.log` después de haberse
#    molestado en crear `storage/logs/webhooks/api-deploy.log`. El fichero que
#    preparaba no se usaba.
#  - `git checkout -- .` descarta cambios locales sin avisar. Se mantiene porque
#    en el servidor no debe haber cambios locales, pero deja constancia.
#  - Sin `set -e`: si fallaba `git pull` o la migración, seguía y levantaba la
#    aplicación igual.
#  - No cacheaba nada ni reiniciaba los workers, así que quedaban ejecutando el
#    código anterior.
#
set -euo pipefail

cd "$(dirname "$0")/../.."

LOG_DIR='storage/logs/webhooks'
LOG="${LOG_DIR}/api-deploy.log"

mkdir -p "$LOG_DIR"
touch "$LOG"
chmod 664 "$LOG" || true

exec >> "$LOG" 2>&1

echo "===================================================================="
echo "$(date '+%Y-%m-%d %H:%M:%S')  Despliegue por webhook: comienzo"

# Pase lo que pase, sacar la aplicación de mantenimiento al salir.
trap 'php artisan up || true; echo "$(date "+%Y-%m-%d %H:%M:%S")  Fin"' EXIT

php artisan down --retry=60

# En el servidor no debe haber cambios locales: si los hay, se descartan.
git checkout -- .
git pull --ff-only

export COMPOSER_HOME=/tmp/composer_home
composer install --no-interaction --no-dev --prefer-dist --optimize-autoloader

pnpm install --frozen-lockfile
pnpm run build

php artisan migrate --force

php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# Los workers son procesos de larga vida: sin esto seguirían con el código viejo.
php artisan queue:restart

echo "$(date '+%Y-%m-%d %H:%M:%S')  Despliegue terminado sin errores"
