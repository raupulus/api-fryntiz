# Migración de datos de V1 a V2

> **Estos scripts YA se ejecutaron sobre `raupulus_api` en local, el 2026-09-05.**
> Quedan aquí como registro de cómo se construyó esa base, no como un paso del
> despliegue: al servidor va el volcado ya hecho, no los scripts.

Sólo hay que volver a lanzarlos si se rehace la base desde cero a partir de un
volcado de V1. En ese caso, en este orden y sobre una base recién migrada:

```bash
./scripts/migrar_datos_v1_a_v2.sh
psql -d raupulus_api -f scripts/energia_estructura.sql
psql -d raupulus_api -f scripts/energia_enlaces.sql
```

Los tres son idempotentes.

## 1. `migrar_datos_v1_a_v2.sh`

Copia los datos de la base de V1 a la de V2, sólo por las columnas que existen
en las dos. Además:

- **Respeta las tablas que siembran las migraciones** (`energy_source_types`).
  Truncarlas antes de copiar se llevó por delante los cinco tipos de fuente y
  dejó el módulo de energía sin catálogo.
- **Siembra el rol `editor`**, que la migración sólo inserta si ya existían los
  roles base, cosa que no ocurre sobre una base recién migrada.
- **Recupera los acumulados de energía.** V1 los guardaba en `amperage` y
  `power`; V2 los llama `energy_ah` y `energy_wh`. Mismo dato, otro nombre: si
  no se mapean, `EnergyController` filtra por `energy_wh IS NOT NULL` y la web
  dice «No hay dispositivos de energía registrados» con 878.000 lecturas dentro.

## 2. `energia_estructura.sql`

V1 no tenía instalaciones: las lecturas colgaban del `hardware_device_id`. V2
mete una capa por medio —instalación → elemento → lectura— para poder sumar por
elemento y no por aparato.

Esa capa no se puede copiar porque en origen no existe, así que se **deriva**:

- Cada dispositivo con `hardware_type_id = 2` (Controlador Solar) es una
  instalación y un elemento **generador**. Hoy son dos: el Renogy Rover (id 6) y
  el Sunix (id 7).
- Todo dispositivo con histórico de consumo es un elemento **carga**.
- Los que generan sin ser controlador (un portátil con placa propia) también son
  generadores.

## 3. `energia_enlaces.sql`

Ata las lecturas ya cargadas a su elemento por `hardware_energy_id`. Sin esto
las lecturas siguen colgando sólo del aparato —como en V1— y las instalaciones
aparecen vacías en el panel.

---

> Creado: 2026-09-05 · Última revisión: 2026-09-05
