#!/usr/bin/env bash

##
## Comprueba si existe una línea activa VAR=... en un archivo y la actualiza,
## o la añade al final si no existe. Usa ENVIRON de awk para evitar problemas
## con caracteres especiales (&, /, \, +, =, $, comillas) y nunca imprime
## secretos por salida estándar.
##
replace_or_add_var_in_file() {
    local FILE="${1}"
    local VAR="${2}"
    local VALUE_RAW="${3}"

    local TMP_FILE
    TMP_FILE="$(mktemp)"

    _ENV_VAR="${VAR}" _ENV_VAL="${VALUE_RAW}" awk '
        BEGIN {
            var = ENVIRON["_ENV_VAR"]
            val = ENVIRON["_ENV_VAL"]
            found = 0
            pattern = "^[[:space:]]*" var "[[:space:]]*="
        }
        $0 ~ pattern {
            print var "=" val
            found = 1
            next
        }
        { print }
        END {
            if (!found) {
                print var "=" val
            }
        }
    ' "${FILE}" > "${TMP_FILE}"

    cat "${TMP_FILE}" > "${FILE}"
    rm -f "${TMP_FILE}"
}
