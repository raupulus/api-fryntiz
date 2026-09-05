#!/usr/bin/env bash

## Este script prepara la base de datos: la crea si no existe.
##
## El nombre de la base salía escrito a mano en el script (`api_fryntiz`),
## mientras que la aplicación lo lee de `DB_DATABASE`. Con los dos valores
## desacoplados, cambiar el `.env` dejaba de tener efecto aquí: el script
## comprobaba y creaba una base y Laravel se conectaba a otra.
##
## Recibe como parámetros, o deben estar seteados en el entorno:
## $1 DB_USERNAME
## $2 DB_PASSWORD
## $3 DB_DATABASE (opcional; si no, se toma del entorno)

set -euo pipefail

DB_USERNAME=${DB_USERNAME:-${1:-}}
DB_PASSWORD=${DB_PASSWORD:-${2:-}}
DB_DATABASE=${DB_DATABASE:-${3:-}}

if [[ -z ${DB_DATABASE} ]]; then
    echo 'ERROR: falta DB_DATABASE (ni en el entorno ni como tercer parámetro).' >&2
    exit 1
fi

if [[ -z ${DB_USERNAME} ]]; then
    echo 'ERROR: falta DB_USERNAME (ni en el entorno ni como primer parámetro).' >&2
    exit 1
fi

CONN="user=${DB_USERNAME} password=${DB_PASSWORD}"

## Compruebo si existe la db, en ese caso devolverá "1"
db_exist=$(psql -tAc "SELECT 1 FROM pg_database WHERE datname='${DB_DATABASE}'" "${CONN}")

if [[ ${db_exist} = '1' ]]; then
    echo "Ya existe la base de datos '${DB_DATABASE}', se aborta crearla"
    exit 0
fi

## El nombre va entre comillas dobles en la sentencia SQL: sin ellas, un nombre
## con mayúsculas o guiones rompe el CREATE DATABASE.
psql -c "CREATE DATABASE \"${DB_DATABASE}\"" "${CONN}"
echo "Base de datos '${DB_DATABASE}' creada"
