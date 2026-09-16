# Simplificar tabla `meteorology_aemet_ozone_total`

> **Estado:** idea decidida pero aplazada. No bloquea el funcionamiento actual.

## Contexto

El endpoint oficial de AEMET `GET /api/red/especial/ozono` devuelve el ozono total en columna (Unidades Dobson) de la red nacional de espectrofotómetros Brewer de España, compuesta únicamente por 7 estaciones (A Coruña, Madrid, Zaragoza, Murcia, Moguer/El Arenosillo, Izaña y Santa Cruz de Tenerife).

En este proyecto, la única estación de interés es **Moguer (El Arenosillo, Huelva - `5860E`)**, ubicada en Mazagón/INTA a unos ~50 km de Chipiona, siendo la única estación que mide la capa de ozono en toda Andalucía y el Golfo de Cádiz.

A partir de septiembre de 2026, el comando `aemet:ozone-total` (`AEMETOzoneTotalCommand`) y el helper `AEMETHelper::getOzoneTotal()` filtran y guardan **únicamente** la medición de Moguer (`5860E`).

## Qué se quiere en el futuro

Dado que la estación siempre es fija (Moguer - El Arenosillo), la tabla `meteorology_aemet_ozone_total` tiene columnas redundantes:

1. **Eliminar `station_name`**: Ya no aporta valor tener una columna `VARCHAR` repitiendo `"Moguer (El Arenosillo)"` en cada fila diaria.
2. **Eliminar `station_code`**: Al no almacenarse otras estaciones, el código `5860E` es una constante del sistema (`config('aemet.ozone_station_code')`).
3. **Eliminar `id` autoincremental y usar `measured_on` como Primary Key**: Al haber una única medición por día, la clave natural primaria puede ser directamente `measured_on` (tipo `date`), simplificando los índices y ahorrando la clave subrogada `id` y el índice único compuesto `(station_code, measured_on)`.

## Esquema propuesto a futuro

```sql
CREATE TABLE meteorology_aemet_ozone_total (
    measured_on DATE PRIMARY KEY,
    ozone_value INTEGER NOT NULL,
    created_at TIMESTAMP WITHOUT TIME ZONE,
    updated_at TIMESTAMP WITHOUT TIME ZONE
);
```

## Tareas pendientes cuando se aborde

- [ ] Crear migración para eliminar columnas `station_name`, `station_code` y cambiar la clave primaria a `measured_on`.
- [ ] Actualizar el modelo `AEMETOzoneTotal` (`$fillable`, `$primaryKey`, etc.).
- [ ] Actualizar `AEMETOzoneTotal::saveFromApi()` para hacer `updateOrCreate(['measured_on' => ...])` sin `station_code`.
- [ ] Actualizar `AemetOzoneTotalFactory` y tests asociados.
- [ ] Actualizar la vista `resources/views/weather_station/partials/ozone-card.blade.php` si hacía referencia a `$ozone->station_name`.

---

> Creado: 2026-09-16 · Última revisión: 2026-09-16
