#!/usr/bin/env bash

## Crea el directorio de logs si no existiera.
if [[ ! -d 'storage/logs/webhooks' ]]; then
    mkdir 'storage/logs/webhooks'
fi

## Crea el archivo sobre el que escribir los logs si no existiera.
if [[ ! -f 'storage/logs/webhooks/api-deploy.log' ]]; then
    touch 'storage/logs/webhooks/api-deploy.log'
    chmod 775 'storage/logs/webhooks/api-deploy.log'
fi

LOG="storage/logs/script-api-deploy.log"

echo "Script para desplegar API comenzado correctamente" >> $LOG 2>> $LOG

## Pongo en modo mantenimiento.
php artisan down >> $LOG 2>> $LOG

## Descarto cambios realizados sobre archivos trackeados.
git checkout -- . >> $LOG 2>> $LOG

## Actualizo cambios.
git pull >> $LOG 2>> $LOG

## Genero assets con npm.
npm install --production >> $LOG 2>> $LOG

## Actualizo paquetes de composer.
export COMPOSER_HOME=/tmp/composer_home && composer install --no-interaction --no-dev --prefer-dist >> $LOG 2>> $LOG

## Fuerzo actualización de migraciones nuevas.
php artisan migrate --force >> $LOG 2>> $LOG

## Vuelvo a poner el sitio en modo producción normal.
php artisan up >> $LOG 2>> $LOG

echo "Script termina" >> $LOG 2>> $LOG
