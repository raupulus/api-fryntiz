#!/usr/bin/env bash
# -*- ENCODING: UTF-8 -*-

##
## @author Raúl Caro Pastorino
## @copyright Copyright © 2017 Raúl Caro Pastorino
## @license https://wwww.gnu.org/licenses/gpl.txt
##

if [[ "$1" = "travis" ]]; then
    psql -U postgres -c "CREATE DATABASE api_fryntiz_test;"
    psql -U postgres -c "CREATE USER api_fryntiz PASSWORD 'api_fryntiz' SUPERUSER;"
else
    if [[ "$1" != "test" ]]; then
        sudo -u postgres dropdb --if-exists api_fryntiz
        sudo -u postgres dropdb --if-exists api_fryntiz_test
        sudo -u postgres dropuser --if-exists api_fryntiz
    fi

    sudo -u postgres psql -c "CREATE USER api_fryntiz PASSWORD 'api_fryntiz' SUPERUSER;"

    if [[ "$1" != "test" ]]; then
        sudo -u postgres createdb -O api_fryntiz api_fryntiz
    fi

    sudo -u postgres createdb -O api_fryntiz api_fryntiz_test

    LINE="localhost:5432:*:api_fryntiz:api_fryntiz"
    FILE="$HOME/.pgpass"

    if [[ ! -f "$FILE" ]]; then
        touch "$FILE"
        chmod 600 "$FILE"
    fi

    if ! grep -qsF "$LINE" $FILE; then
        echo "$LINE" >> "$FILE"
    fi
fi
