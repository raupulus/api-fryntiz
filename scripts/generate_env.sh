#!/usr/bin/env bash

## Este script genera el archivo .env
## Recibe como parámetros o deben estar seteados en el entorno:
## $1 DB_USERNAME
## $2 DB_PASSWORD

## Parámetros tomados desde el entorno (sólo se escriben los que tengan valor;
## los demás se quedan como en .env.example.production):
## APP_NAME, APP_URL, API_URL
## DB_DATABASE, DB_CONNECTION, DB_PORT
## MAIL_HOST, MAIL_PORT, MAIL_USERNAME, MAIL_PASSWORD, MAIL_ENCRYPTION,
## MAIL_FROM_ADDRESS, MAIL_FROM_NAME, MAIL_ADMIN_ADDRESS
## FRONTEND_URLS, SANCTUM_STATEFUL_DOMAINS
## RECAPTCHA_SITE_KEY, RECAPTCHA_SECRET_KEY, TURNSTILE_SECRET_KEY
## GOOGLE_API_KEY, GOOGLE_DEV_API_KEY
## AEMET_API_KEY, AEMET_API_KEY_EXPIRES_AT

################ Asignación de variables por parámetros ################
echo $DB_USERNAME
echo $DB_PASSWORD

if [[ ! $DB_USERNAME ]]; then
    DB_USERNAME=${1}
fi

if [[ ! $DB_PASSWORD ]]; then
    DB_PASSWORD=${2}
fi

################ Creo variables de trabajo ################

WORKSCRIPT="$(pwd)"

################ Comprueba requisitos ################

## En caso de ser root se aborta.
if [[ $(whoami) = 'root' ]]; then
    echo 'No puedes ejecutar este script como root'
    exit 1
fi

## En caso de no encontrar archivos esenciales para la ejcución se aborta.
if [[ ! -f "${WORKSCRIPT}/.env.example.production" ]] ||
   [[ ! -d "${WORKSCRIPT}/scripts" ]] ||
   [[ ! -f "${WORKSCRIPT}/scripts/functions.sh" ]]; then
    echo 'Este script solo puede ser ejecutado desde la raíz del proyecto.'
    exit 1
fi

## En caso de existir un archivo .env se omiten operaciones.
if [[ -f "${WORKSCRIPT}/.env" ]]; then
    echo 'Ya existe un archivo .env en el proyecto, omitiendo operaciones.'
    exit 0
fi

################ Incluyo archivos de funciones ################

source "${WORKSCRIPT}/scripts/functions.sh"

################ Comienza el flujo de generar .env ################

## Creo el archivo .env a partir del archivo con parámetros predefinidos.

cp "${WORKSCRIPT}/.env.example.production" "${WORKSCRIPT}/.env"
chmod ug+rw "${WORKSCRIPT}/.env"

## Sólo las que lleguen con valor: antes se escribían todas, y una variable que
## no venía en el entorno dejaba vacía la del ejemplo (APP_URL, por ejemplo).
## Varias ya no existían en el proyecto (GOOGLE_CAPTCHA_*, PUSHER_*,
## REDIS_PASSWORD, API_TOKEN…) y faltaban las actuales.
set_if_given() {
    local VAR="${1}"

    if [[ -n "${!VAR}" ]]; then
        replace_or_add_var_in_file "${WORKSCRIPT}/.env" "${VAR}" "${!VAR}"
    fi
}

## General
set_if_given 'APP_NAME'
set_if_given 'APP_URL'
set_if_given 'API_URL'

## DB
set_if_given 'DB_DATABASE'
set_if_given 'DB_USERNAME'
set_if_given 'DB_PASSWORD'
set_if_given 'DB_CONNECTION'
set_if_given 'DB_PORT'

## Mail
set_if_given 'MAIL_HOST'
set_if_given 'MAIL_PORT'
set_if_given 'MAIL_USERNAME'
set_if_given 'MAIL_PASSWORD'
set_if_given 'MAIL_ENCRYPTION'
set_if_given 'MAIL_FROM_ADDRESS'
set_if_given 'MAIL_FROM_NAME'
set_if_given 'MAIL_ADMIN_ADDRESS'

## Webs que consumen la API
set_if_given 'FRONTEND_URLS'
set_if_given 'SANCTUM_STATEFUL_DOMAINS'

## Captcha (reCAPTCHA v3 y Cloudflare Turnstile)
set_if_given 'RECAPTCHA_SITE_KEY'
set_if_given 'RECAPTCHA_SECRET_KEY'
set_if_given 'TURNSTILE_SECRET_KEY'

## Google (Maps/YouTube). Las claves del asistente IA no van en el .env: se
## pegan en el panel (Configuración → Proveedores de IA).
set_if_given 'GOOGLE_API_KEY'
set_if_given 'GOOGLE_DEV_API_KEY'

## AEMET
set_if_given 'AEMET_API_KEY'
set_if_given 'AEMET_API_KEY_EXPIRES_AT'

echo ""
echo "Resultado del .env final:"
cat "${WORKSCRIPT}/.env"
