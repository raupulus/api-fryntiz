#!/usr/bin/env bash
# -*- ENCODING: UTF-8 -*-

BASE_DIR=$(dirname $(readlink -f "$0"))

if [[ "$1" != "test" ]]; then
    psql -h localhost -U api_fryntiz -d api_fryntiz < $BASE_DIR/api_fryntiz.sql
    psql -h localhost -U api_fryntiz -d api_fryntiz < $BASE_DIR/api_fryntiz_datos.sql
fi

psql -h localhost -U api_fryntiz -d api_fryntiz_test < $BASE_DIR/api_fryntiz.sql
psql -h localhost -U api_fryntiz -d api_fryntiz_test < $BASE_DIR/api_fryntiz_datos.sql
