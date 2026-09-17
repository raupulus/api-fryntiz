# Simplificar tabla `meteorology_aemet_ozone_total`

> **Estado:** completado el 2026-09-17. Tabla simplificada a `(id, ozone_value, measured_on, timestamps)`.

La lógica de ingesta y presentación ya filtra, guarda y muestra **únicamente** la medición de Moguer (El Arenosillo, `5860E`). Se simplificó la tabla eliminando las columnas fijas redundantes:

1. **Eliminar columnas `station_name` y `station_code`**: Eran fijas para esta ubicación (`config('aemet.ozone_station_code')`).
2. **Índice único en `measured_on`**: Se reemplazó el índice único compuesto `(station_code, measured_on)` por `unique('measured_on')` para garantizar un único registro diario.
3. **Clave primaria `id`**: Mantenida intacta como `$table->id()` auto-incremental según la regla general de migraciones.

## Tareas completadas

- [x] Migración: eliminar columnas `station_name` y `station_code`, y cambiar el índice único a `measured_on`.
- [x] Modelo `AEMETOzoneTotal`: ajustar `$fillable` y `saveFromApi()`.
- [x] Actualizar `AemetOzoneTotalFactory` y tests asociados.
- [x] Vista `ozone-card.blade.php`: retirar la dependencia de la columna `$ozone->station_name`.

---

> Creado: 2026-09-16 · Última revisión: 2026-09-17
