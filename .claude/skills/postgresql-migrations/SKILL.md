---
name: postgresql-migrations
description: >-
  Migraciones y modelado de datos en PostgreSQL para Api Raupulus. Cárgala
  SIEMPRE que crees o edites algo en database/migrations/, diseñes tablas,
  índices, claves foráneas, tipos de columna o relaciones, escribas seeders/
  factories con implicaciones de esquema, o cuando aparezcan dudas de rendimiento
  de consultas (índices, JSONB, full-text). Úsala en cuanto el trabajo toque "la
  base de datos", "una tabla", "una columna", "una migración", "un índice" o "una
  FK", aunque no se diga "PostgreSQL". IMPORTANTE: este proyecto usa PostgreSQL,
  no MySQL — evita copiar artefactos MySQL de migraciones legacy. Para la lógica
  de los modelos Eloquent usa laravel-backend.
---

# Migraciones PostgreSQL — Api Raupulus

Motor: **PostgreSQL** (BD de test: `api_raupulus_testing`). Las migraciones del
proyecto están **documentadas**: comentario en la tabla y en **todas** las
columnas, en **español**.

## Reglas obligatorias

1. **Comenta todo.** `$table->comment('...')` en la tabla y `->comment('...')`
   en cada columna. Es una convención fuerte del proyecto y la BD la conserva.
2. **Foreign keys explícitas** con `onUpdate` y `onDelete` siempre definidos.
   Elige la acción por semántica: `CASCADE` cuando el hijo no tiene sentido sin
   el padre; `SET NULL` (columna `nullable`) cuando puede sobrevivir.
3. **Índices** en columnas de búsqueda/filtro frecuente (slugs, FKs que se
   consultan, fechas de rango, estados).
4. **Naming**: tablas y columnas en `snake_case`; tablas en plural.

```php
Schema::create('hardware_devices', function (Blueprint $table) {
    $table->comment('Dispositivos de hardware');

    $table->bigIncrements('id')->comment('Identificador único');

    $table->unsignedBigInteger('user_id')->nullable()
        ->comment('Usuario asociado');
    $table->foreign('user_id')->references('id')->on('users')
        ->onUpdate('CASCADE')->onDelete('CASCADE');

    $table->unsignedBigInteger('image_id')->nullable()
        ->comment('Relación con la imagen asociada');
    $table->foreign('image_id')->references('id')->on('files')
        ->onUpdate('CASCADE')->onDelete('SET NULL');

    $table->string('name', 255)->nullable()
        ->comment('Nombre real del dispositivo, EJ: Raspberry Pi 4b+');

    $table->timestamps();

    $table->index('user_id');
});
```

## NO hagas esto (artefactos MySQL en migraciones antiguas)

Algunas migraciones legacy arrastran líneas pensadas para MySQL que en
PostgreSQL **no aplican** y solo añaden ruido. No las copies en migraciones
nuevas:

```php
// ❌ Específico de MySQL — no usar en este proyecto (PostgreSQL)
$table->engine = 'InnoDB';
$table->charset = 'utf8';
$table->collation = 'utf8_unicode_ci';
```

PostgreSQL gestiona el encoding a nivel de base de datos; no se configura por
tabla en la migración.

## Tipos y rasgos propios de PostgreSQL que sí debes aprovechar

- **JSONB** (`$table->jsonb('payload')`) para datos semiestructurados; es
  indexable y consultable, preferible a `json`/`text`.
- **Búsqueda de texto**: para búsquedas serias usa `tsvector` + índice GIN en
  lugar de `LIKE %...%`.
- **Índices parciales/compuestos** cuando el patrón de consulta lo justifique.
- **Booleanos** nativos (`->boolean()`), no `tinyint`.
- **Timestamps con zona**: respeta el patrón existente del proyecto; muchos
  modelos serializan a ISO 8601 en la capa Resource.

## Datos de telemetría (IoT) — alto volumen

Los módulos WeatherStation, Hardware/Energy, KeyCounter y SmartPlant insertan
series temporales. Para esas tablas:

- Indexa por `device_id` + columna temporal (`read_at`/`date`), que es el patrón
  de consulta dominante (resúmenes diarios e históricos).
- Considera índices compuestos `(device_id, read_at)` antes que índices sueltos.

## Modelos y migraciones van juntos

Tras crear/editar una migración:

1. El modelo correspondiente extiende `BaseModel`/`BaseAbstractModelWithTableCrud`
   (ver skill `laravel-backend`) y declara `$fillable` y casts (enums incluidos).
2. Si añades campos/relaciones, **actualiza `docs/info/<modulo>.md`** con los
   nuevos campos, tipos y relaciones (obligatorio en este proyecto).
3. Ejecuta `php artisan test` (usa `RefreshDatabase` contra `api_raupulus_testing`).
