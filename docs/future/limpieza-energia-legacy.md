# Limpieza del andamiaje del esquema de energía

> **Estado:** grupos 1 y 2 **hechos** el 2026-09-14 (comando y tablas viejas borrados,
> `2026_09_14_000004_drop_legacy_power_tables`). Queda el grupo 3.
> **Decidido el:** 2026-09-13

Cuando el esquema unificado de energía lleve un tiempo funcionando en producción,
sobra todo lo que se montó **para llegar hasta él**: el comando de traspaso, las
tablas del esquema viejo y las migraciones que fueron añadiendo columnas una a
una. Nada de esto molesta hoy, pero es andamiaje y conviene que no se quede.

**El orden importa** y está al final: quitar las tablas viejas antes que el
comando deja el comando roto, y tocar las migraciones sin reconciliar la tabla
`migrations` de producción deja el despliegue sin poder migrar.

---

## 1 · El comando de traspaso — ✅ hecho (2026-09-14)

`energy:migrate-legacy-data` existe para una sola ejecución: llenar las tablas
nuevas desde las viejas. Ya se ejecutó. Volver a lanzarlo **vacía las tres tablas
nuevas** (empieza por un `TRUNCATE`), así que a partir de cierto punto sólo puede
hacer daño.

Qué se va:

| Fichero |
|---|
| `app/Console/Commands/Energy/MigrateLegacyEnergyDataCommand.php` |
| `tests/Feature/Console/Energy/MigrateLegacyEnergyDataCommandTest.php` |

Y sus menciones en `docs/info/commands.md` y en `docs/info/energy.md`.

**No confundir con `energy:aggregate-daily`**, que es el cierre nocturno y se
queda.

---

## 2 · Las tablas del esquema viejo — ✅ hecho (2026-09-14)

Borradas con `2026_09_14_000004_drop_legacy_power_tables`, que también quita sus
migraciones de creación y sus filas de `migrations`. La copia es el volcado de
producción del 2026-09-14.

Siete tablas, **unos 456 MB**, sin una sola línea de código que las lea desde que
se retiraron sus modelos:

| Tabla | Filas | Peso |
|---|---|---|
| `hardware_power_generators` | 878.221 | 234 MB |
| `hardware_power_loads` | 868.091 | 221 MB |
| `hardware_power_generators_solar` | 1.401 | 720 kB |
| `hardware_power_loads_today` | 917 | 432 kB |
| `hardware_power_generators_today` | 916 | 400 kB |
| `hardware_power_loads_historical` | 6 | 40 kB |
| `hardware_power_generators_historical` | 4 | 40 kB |

**Son el respaldo en frío de toda la historia cruda.** Mientras existan, el
traspaso se puede repetir; en cuanto se borren, lo único que queda es el volcado
que haya guardado. Por eso van las últimas y con una copia verificada delante.

`energy_systems` ya no está: la quitó `2026_09_14_000002_drop_energy_systems_table`
(2026-09-14). Su migración de creación sigue porque la de `hardware_energy` de
2022 le pone una FK; sale con la consolidación del grupo 3, junto con esa FK.

Con ellas se van sus migraciones:

```
2022_01_30_232819_create_hardware_power_loads_table.php
2022_01_30_232820_create_hardware_power_loads_today_table.php
2022_01_30_232821_create_hardware_power_loads_historical_table.php
2022_01_30_232822_create_hardware_power_generators_table.php
2022_01_30_232823_create_hardware_power_generators_today_table.php
2022_01_30_232824_create_hardware_power_generators_historical_table.php
2026_07_06_000002_create_hardware_power_generators_solar_table.php
```

---

## 3 · Consolidar las migraciones de energía

Hoy hay **doce** para dejar tres tablas y ajustar una cuarta, y la mitad son
parches encima del parche anterior:

| Migración | Qué hace |
|---|---|
| `2022_01_30_225448_create_hardware_energy_table` | Crea `hardware_energy` con `energy_system_id`, `capacity_mah`, `capacity_wh` |
| `2026_09_07_000003_clean_roles_in_hardware_energy_table` | Arregla valores de `role` |
| `2026_09_12_000001..3` | Crean las tres tablas nuevas |
| `2026_09_12_000004_update_hardware_energy_table` | Quita lo que creó la de 2022 y añade `capacity_ah` y `auto_calculate_history` |
| `2026_09_13_000001..4` | Añaden las columnas de origen, `default_interval_seconds` y el último odómetro del aparato |

Lo razonable es dejar **cuatro**: una por tabla, con las columnas definitivas
desde el principio. Una instalación nueva no tiene por qué crear una columna para
borrarla dos migraciones después.

### Reconciliar la tabla `migrations` en producción

Es el paso que se olvida y el que rompe el despliegue siguiente. Consolidar
ficheros deja en `migrations` filas que ya no existen y le falta la nueva, así
que Laravel intentaría volver a crear tablas que ya están.

El procedimiento, **con la aplicación parada y copia hecha**:

```sql
-- 1. Qué hay registrado hoy
SELECT id, migration, batch FROM migrations
WHERE migration LIKE '%energy%' OR migration LIKE '%power%'
ORDER BY id;

-- 2. Fuera las que se consolidan (ajustar la lista a lo que se borre de verdad)
DELETE FROM migrations WHERE migration IN ( … );

-- 3. Dentro las nuevas, dadas por ejecutadas: las tablas ya existen
INSERT INTO migrations (migration, batch)
VALUES ('2026_XX_XX_000001_create_hardware_energy_table', 1), … ;
```

Y después, **comprobar antes de dar por bueno**:

```bash
php artisan migrate:status   # ninguna Pending, ninguna fila sin fichero
```

---

## Orden

1. **Quitar el comando de traspaso** (grupo 1). Sin él ya no hay forma de volver
   a vaciar las tablas nuevas por accidente.
2. **Consolidar las migraciones** del esquema nuevo (grupo 3) y reconciliar
   `migrations` en producción.
3. **Borrar las tablas viejas** (grupo 2) y sus migraciones, con una copia
   verificada delante.

El 3 va al final a propósito: mientras esas tablas estén, cualquier error de los
pasos anteriores se puede deshacer volviendo a traspasar.

---

## Cuándo hacerlo

Disparadores razonables, cualquiera de ellos:

- El esquema unificado lleva **tres meses** en producción sin incidencias y los
  totales del panel cuadran con lo que dice el controlador.
- Los 456 MB de las tablas viejas empiezan a molestar en el disco o en los
  backups.
- Hay que tocar las migraciones de energía por otro motivo y ya que se está.

**Ninguno es urgente.** El coste de dejarlo es medio giga de disco y unos
ficheros de más; el de precipitarse es perder el único respaldo crudo de cuatro
años de lecturas.

---

## Lo que NO se toca

Para que no se lleve nada por delante quien haga esto:

| | Por qué se queda |
|---|---|
| `energy:aggregate-daily` | Es el cierre nocturno, no andamiaje |
| `debug:seed-energy` | Datos de prueba para desarrollo |
| `energy_source_types` | La tabla de tipos de fuente sigue en uso desde `hardware_energy` |
| `hardware_energy` | Es el catálogo del esquema nuevo, no del viejo |
| La columna `energy_source` de `hardware_energy_readings` | Es el origen del delta de cada lectura, del esquema actual |
| [`docs/deploys/energia-v2.md`](../deploys/energia-v2.md) | Se puede archivar **después** del despliegue, no antes |

---

> Creado: 2026-09-13 · Última revisión: 2026-09-14
