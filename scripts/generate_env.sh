#!/usr/bin/env bash
set -euo pipefail

## Este script genera el archivo .env a partir de .env.example.production y las
## variables de entorno proporcionadas (por ejemplo, desde GoCD).
##
## Parámetros opcionales:
## $1 DB_USERNAME (si no viene en el entorno)
## $2 DB_PASSWORD (si no viene en el entorno)
## --force        Regenera .env aunque ya exista (automático dentro de GoCD)

FORCE_REGENERATE=0
POSITIONAL=()

for arg in "$@"; do
    case "${arg}" in
        --force)
            FORCE_REGENERATE=1
            ;;
        *)
            POSITIONAL+=("${arg}")
            ;;
    esac
done

if [[ -z "${DB_USERNAME:-}" && ${#POSITIONAL[@]} -ge 1 ]]; then
    DB_USERNAME="${POSITIONAL[0]}"
fi

if [[ -z "${DB_PASSWORD:-}" && ${#POSITIONAL[@]} -ge 2 ]]; then
    DB_PASSWORD="${POSITIONAL[1]}"
fi

WORKSCRIPT="$(pwd)"

## En caso de ser root se aborta.
if [[ "$(whoami)" = 'root' ]]; then
    echo 'No puedes ejecutar este script como root' >&2
    exit 1
fi

## En caso de no encontrar archivos esenciales para la ejecución se aborta.
if [[ ! -f "${WORKSCRIPT}/.env.example.production" ]] ||
   [[ ! -d "${WORKSCRIPT}/scripts" ]] ||
   [[ ! -f "${WORKSCRIPT}/scripts/functions.sh" ]]; then
    echo 'Este script solo puede ser ejecutado desde la raíz del proyecto.' >&2
    exit 1
fi

## En GoCD el directorio de trabajo se reutiliza entre despliegues: regeneramos
## siempre .env para reflejar cambios en las variables del pipeline. En local,
## si ya existe .env y no se pasa --force, se respeta sin sobreescribir.
if [[ -n "${GO_PIPELINE_NAME:-}" ]]; then
    FORCE_REGENERATE=1
fi

if [[ -f "${WORKSCRIPT}/.env" && "${FORCE_REGENERATE}" -ne 1 ]]; then
    echo 'Ya existe un archivo .env en el proyecto, omitiendo operaciones (usa --force para regenerar).'
    exit 0
fi

# shellcheck source=scripts/functions.sh
source "${WORKSCRIPT}/scripts/functions.sh"

cp "${WORKSCRIPT}/.env.example.production" "${WORKSCRIPT}/.env"
chmod 0640 "${WORKSCRIPT}/.env"

APPLIED_VARS=()

set_if_given() {
    local VAR="${1}"
    local VALUE="${!VAR:-}"

    if [[ -n "${VALUE}" ]]; then
        replace_or_add_var_in_file "${WORKSCRIPT}/.env" "${VAR}" "${VALUE}"
        APPLIED_VARS+=("${VAR}")
    fi
}

## General
set_if_given 'APP_NAME'
set_if_given 'APP_ENV'
set_if_given 'APP_KEY'
set_if_given 'APP_DEBUG'
set_if_given 'APP_TIMEZONE'
set_if_given 'APP_DISPLAY_TIMEZONE'
set_if_given 'APP_URL'
set_if_given 'API_URL'
set_if_given 'API_NAME'
set_if_given 'API_SESSION_DAYS'
set_if_given 'LOG_CHANNEL'
set_if_given 'LOG_LEVEL'
set_if_given 'FILESYSTEM_DISK'

## Base de datos
set_if_given 'DB_CONNECTION'
set_if_given 'DB_HOST'
set_if_given 'DB_PORT'
set_if_given 'DB_DATABASE'
set_if_given 'DB_USERNAME'
set_if_given 'DB_PASSWORD'

## Redis / Caché / Colas / Sesiones
set_if_given 'REDIS_CLIENT'
set_if_given 'REDIS_HOST'
set_if_given 'REDIS_PORT'
set_if_given 'REDIS_PASSWORD'
set_if_given 'CACHE_STORE'
set_if_given 'CACHE_DRIVER'
set_if_given 'QUEUE_CONNECTION'
set_if_given 'SESSION_DRIVER'
set_if_given 'SESSION_LIFETIME'
set_if_given 'SESSION_SECURE_COOKIE'
set_if_given 'SESSION_SAME_SITE'
set_if_given 'SESSION_DOMAIN'
set_if_given 'MEMCACHED_HOST'

## Mail
set_if_given 'MAIL_MAILER'
set_if_given 'MAIL_HOST'
set_if_given 'MAIL_PORT'
set_if_given 'MAIL_USERNAME'
set_if_given 'MAIL_PASSWORD'
set_if_given 'MAIL_ENCRYPTION'
set_if_given 'MAIL_FROM_ADDRESS'
set_if_given 'MAIL_FROM_NAME'
set_if_given 'MAIL_ADMIN_ADDRESS'

## Webs que consumen la API y proxies de confianza
set_if_given 'FRONTEND_URLS'
set_if_given 'SANCTUM_STATEFUL_DOMAINS'
set_if_given 'TRUSTED_PROXIES'

## Captcha (reCAPTCHA v3 y Cloudflare Turnstile)
set_if_given 'RECAPTCHA_SITE_KEY'
set_if_given 'RECAPTCHA_SECRET_KEY'
set_if_given 'RECAPTCHA_MIN_SCORE'
set_if_given 'RECAPTCHA_MIN_SCORE_LOGIN'
set_if_given 'TURNSTILE_SITE_KEY'
set_if_given 'TURNSTILE_SECRET_KEY'

## Google (Maps/YouTube)
set_if_given 'GOOGLE_API_KEY'
set_if_given 'GOOGLE_DEV_API_KEY'

## AEMET
set_if_given 'AEMET_API_KEY'
set_if_given 'AEMET_API_KEY_EXPIRES_AT'

## Rate limits
set_if_given 'RATE_LIMIT_API_GLOBAL'
set_if_given 'RATE_LIMIT_IOT_STORE'
set_if_given 'RATE_LIMIT_IOT_BATCH'
set_if_given 'RATE_LIMIT_API'
set_if_given 'RATE_LIMIT_AUTH'
set_if_given 'RATE_LIMIT_API_FALLBACK'
set_if_given 'RATE_LIMIT_FILE_RESIZE'
set_if_given 'RATE_LIMIT_CONTACTO'
set_if_given 'RATE_LIMIT_KEYCOUNTER_SUMMARY'

## WebSockets (Laravel Reverb)
set_if_given 'BROADCAST_CONNECTION'
set_if_given 'REVERB_APP_ID'
set_if_given 'REVERB_APP_KEY'
set_if_given 'REVERB_APP_SECRET'
set_if_given 'REVERB_HOST'
set_if_given 'REVERB_PORT'
set_if_given 'REVERB_SCHEME'
set_if_given 'REVERB_SERVER_HOST'
set_if_given 'REVERB_SERVER_PORT'
set_if_given 'REVERB_ALLOWED_ORIGINS'

echo "Archivo .env generado correctamente (${#APPLIED_VARS[@]} variables aplicadas desde entorno; valores sensibles ocultos)."
