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

## Pongo en modo mantenimiento.
php artisan down

## Descarto cambios realizados sobre archivos trackeados.
git checkout -- .

## Actualizo cambios.
git pull

## Genero assets con npm.
npm install --production

## Actualizo paquetes de composer.
composer install --no-interaction --no-dev --prefer-dist

## Fuerzo actualización de migraciones nuevas.
php artisan migrate --force

## Vuelvo a poner el sitio en modo producción normal.
php artisan up
