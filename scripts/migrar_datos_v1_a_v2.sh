#!/usr/bin/env bash
# Copia los datos de raupulus_api_production (V1) a raupulus_api (V2).
#
# Se copian sólo las columnas que existen en LAS DOS bases: V2 añade 96 columnas
# nuevas (todas nullable o con default, así que se quedan a null) y retira 26 que
# ya no usa —los `deleted_at` que nunca se rellenaron y los agregados que ahora
# sustituyen `energy_wh`/`energy_ah`—.
#
# `session_replication_role = replica` desactiva los triggers de clave foránea
# durante la carga: así no hace falta ordenar las tablas por dependencias, y al
# final se validan todas las FK de golpe.
set -euo pipefail

SRC=raupulus_api_production
DST=raupulus_api
export PGPASSWORD=dev
PSQL="psql -U dev -h 127.0.0.1 -v ON_ERROR_STOP=1 -q"

cols_de() {   # $1 = base, $2 = tabla. Nombres crudos, uno por línea, en orden.
  $PSQL -d "$1" -tAc "SELECT column_name FROM information_schema.columns
                      WHERE table_schema='public' AND table_name='$2'
                      ORDER BY ordinal_position"
}

# Tablas que SIEMBRAN las propias migraciones: no se tocan, o se pierde lo
# sembrado. Ya pasó una vez: al truncarlas antes de copiar desaparecieron los
# cinco tipos de fuente de energía y el rol , y con ellos el módulo de
# energía del panel.
SEMBRADAS="energy_source_types"

TABLAS=$($PSQL -d "$SRC" -tAc "
  SELECT relname FROM pg_stat_user_tables
  WHERE n_live_tup > 0 AND relname NOT IN ('migrations','websockets_statistics_entries')
  ORDER BY relname")

for t in $TABLAS; do
  if [[ " $SEMBRADAS " == *" $t "* ]]; then
    echo "  -- $t: la siembra la migración, se respeta"; continue
  fi
  if [[ "$($PSQL -d "$DST" -tAc "SELECT to_regclass('public.$t') IS NOT NULL")" != "t" ]]; then
    echo "  -- $t: no existe en destino, se salta"; continue
  fi

  # Intersección con los nombres CRUDOS, entrecomillando después. Comparar ya
  # entrecomillado fallaba con las palabras reservadas: `quote_ident` devuelve
  # `"time"` y eso no casa nunca con `time`, así que esas columnas se copiaban
  # vacías y la carga reventaba por un NOT NULL que en origen sí tenía dato.
  COMUNES=$(comm -12 <(cols_de "$SRC" "$t" | sort) <(cols_de "$DST" "$t" | sort))
  [[ -z "$COMUNES" ]] && { echo "  -- $t: sin columnas comunes"; continue; }

  LISTA=$(echo "$COMUNES" | sed 's/.*/"&"/' | paste -sd, -)

  # `cv.slug` es NOT NULL en V2 y no existe en V1: la migración que lo añadía lo
  # rellenaba a partir del título. Aquí se hace lo mismo, en el propio SELECT.
  SELECT_LISTA="$LISTA"; DEST_LISTA="$LISTA"
  if [[ "$t" == "cv" ]]; then
    SELECT_LISTA="$LISTA, regexp_replace(lower(trim(coalesce(title,'curriculum'))),'[^a-z0-9]+','-','g')||'-'||id"
    DEST_LISTA="$LISTA, \"slug\""
  fi

  printf '  %-45s ' "$t"
  $PSQL -d "$SRC" -c "\copy (SELECT $SELECT_LISTA FROM public.\"$t\") TO STDOUT" \
    | $PSQL -d "$DST" -c "SET session_replication_role = replica" \
                      -c "\copy public.\"$t\" ($DEST_LISTA) FROM STDIN"
  echo "OK"
done

# Tras un COPY las secuencias se quedan donde estaban: el siguiente INSERT
# chocaría con una clave que ya existe.
echo "Rol editor (la migración no llega a sembrarlo sobre una base recién migrada)…"
$PSQL -d "$DST" -c "
  INSERT INTO user_roles (id, name, display_name, slug, description, created_at, updated_at)
  VALUES (4,'editor','Editor','editor','Edita contenido solo en las plataformas que tenga asignadas',now(),now())
  ON CONFLICT (id) DO NOTHING;"

# V1 guardaba los acumulados del periodo en `amperage` y `power`; V2 los llama
# `energy_ah` y `energy_wh` y retira las columnas viejas. El dato es el mismo y
# sólo cambia de nombre, así que hay que traerlo: sin él,
# `EnergyController::index()` filtra por `energy_wh IS NOT NULL`, no encuentra
# ningún dispositivo y la web dice "No hay dispositivos de energía registrados"
# con 878.000 lecturas en la base.
echo "Recuperando los acumulados de energía que V1 guardaba con otro nombre…"
$PSQL -d "$DST" -c "CREATE EXTENSION IF NOT EXISTS dblink;" > /dev/null
for t in hardware_power_generators_historical hardware_power_loads_historical \
         hardware_power_generators_today hardware_power_loads_today; do
  $PSQL -d "$DST" -c "
    UPDATE public.$t d
    SET energy_ah = v.amperage, energy_wh = v.power
    FROM dblink('dbname=$SRC', 'SELECT id, amperage, power FROM public.$t')
         AS v(id bigint, amperage numeric, power numeric)
    WHERE d.id = v.id;"
done

echo "Ajustando secuencias…"
$PSQL -d "$DST" -tAc "
  SELECT format('SELECT setval(%L, COALESCE((SELECT MAX(%I) FROM public.%I), 1));',
                pg_get_serial_sequence('public.'||quote_ident(table_name), column_name),
                column_name, table_name)
  FROM information_schema.columns
  WHERE table_schema='public'
    AND pg_get_serial_sequence('public.'||quote_ident(table_name), column_name) IS NOT NULL
" | $PSQL -d "$DST" -f - > /dev/null

echo "Validando claves foráneas…"
$PSQL -d "$DST" -c "
DO \$do\$
DECLARE r record;
BEGIN
  FOR r IN SELECT conrelid::regclass AS t, conname FROM pg_constraint
           WHERE contype='f' AND connamespace='public'::regnamespace LOOP
    EXECUTE format('ALTER TABLE %s VALIDATE CONSTRAINT %I', r.t, r.conname);
  END LOOP;
END \$do\$;"
echo "Hecho."
