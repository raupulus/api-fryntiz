# Comandos de Debug — Datos de Prueba

Comandos Artisan para insertar datos de prueba manualmente durante el desarrollo.
**No usar en producción.** No se ejecutan con seeders.

## Comportamiento común a todos los comandos

- **Entorno**: solo se ejecutan si `APP_ENV !== 'production'`. Abortan con
  código `1` si se invocan en producción.
- **Hardware device**: si no existe `HardwareDevice` con `id = 1`, se usa
  automáticamente el primero disponible (ordenado por `id`) y se muestra un
  aviso. Si no existe ninguno, aborta.
- **User**: si no existe `User` con `id = 1`, se usa el primero disponible.
  Si no existe ninguno, aborta.
- **Trait compartido**: la lógica vive en
  `app/Console/Commands/Debug/Concerns/ResolvesDebugDefaults.php`.

## Weather Station

Inserta registros para todos los sensores (temperatura, humedad, presión, luz, viento, dirección viento, lluvia, eco2, tvoc, calidad aire, relámpagos):

```bash
# 20 registros por sensor (por defecto)
php artisan debug:seed-weatherstation

# Personalizar cantidad
php artisan debug:seed-weatherstation --count=50
```

**Archivo:** `app/Console/Commands/Debug/SeedWeatherStationDebugCommand.php`

## KeyCounter

Inserta registros de teclado y ratón con datos realistas (pulsaciones, special keys, score, duración, etc.):

```bash
# 50 registros por tipo (por defecto)
php artisan debug:seed-keycounter

# Personalizar cantidad
php artisan debug:seed-keycounter --count=100
```

**Archivo:** `app/Console/Commands/Debug/SeedKeyCounterDebugCommand.php`

## AirFlight

Inserta aviones con ICAO/callsign y una trayectoria de vuelo coherente cada
uno (rumbo, velocidad y altitud evolucionan punto a punto, cerca de Chipiona),
para que el mapa trace una línea real en vez de puntos sueltos:

```bash
# 10 aviones + 100 puntos de ruta repartidos entre ellos (por defecto)
php artisan debug:seed-airflight

# Personalizar
php artisan debug:seed-airflight --planes=20 --routes=200

# Ejecutar varias veces para ir añadiendo aviones nuevos (no borra los existentes)
php artisan debug:seed-airflight --planes=1 --routes=25
```

**Archivo:** `app/Console/Commands/Debug/SeedAirFlightDebugCommand.php`

## SmartPlant

Inserta plantas con nombres y lecturas de sensores (humedad suelo/aire, temperatura, luz, presión, UV):

```bash
# 5 plantas + 50 registros (por defecto)
php artisan debug:seed-smartplant

# Personalizar
php artisan debug:seed-smartplant --plants=10 --registers=100
```

**Archivo:** `app/Console/Commands/Debug/SeedSmartPlantDebugCommand.php`

## Hardware/Energy

Inserta dispositivos hardware y registros de energía solar (voltaje, corriente, potencia):

```bash
# 5 dispositivos + 100 registros (por defecto)
php artisan debug:seed-energy

# Personalizar
php artisan debug:seed-energy --devices=10 --records=200
```

**Archivo:** `app/Console/Commands/Debug/SeedEnergyDebugCommand.php`

> **Eliminado en fix_11:** el comando `debug:seed-users` se ha retirado. Los 3
> usuarios creados por `UsersTableSeeder` (superadmin, admin, user) son suficientes
> para probar la plataforma. `debug:seed-all` ya no lo invoca.

## Hardware (Dispositivos sueltos)

Crea dispositivos hardware de prueba. Cada dispositivo se genera con el
**último estado conocido** poblado con datos de ejemplo (`temp`, `voltage`,
`battery_level`, `cpu`, `disk`, `uptime` y `extra`), útil para depurar el widget
"Estado de dispositivos" del panel y la sección "Stats de hardware":

```bash
php artisan debug:seed-hardware --count=5
```

**Archivo:** `app/Console/Commands/Debug/SeedHardwareDebugCommand.php`

## Platform

Crea plataformas de prueba con categorías y tags asociadas:

```bash
php artisan debug:seed-platform --count=3
```

**Archivo:** `app/Console/Commands/Debug/SeedPlatformDebugCommand.php`

## Content (CMS)

Crea contenidos (artículos, tutoriales, proyectos) con categorías, tags, metadata y SEO:

```bash
php artisan debug:seed-content --count=10
```

**Archivo:** `app/Console/Commands/Debug/SeedContentDebugCommand.php`

## Curriculum Vitae (CV)

Crea la estructura completa de un currículum vitae con sus 18 secciones asociadas:

```bash
php artisan debug:seed-cv
```

**Archivo:** `app/Console/Commands/Debug/SeedCvDebugCommand.php`

## Newsletter

Crea suscriptores de prueba para el newsletter (verificados y no verificados):

```bash
php artisan debug:seed-newsletter --count=10
```

**Archivo:** `app/Console/Commands/Debug/SeedNewsletterDebugCommand.php`

## Contact

Crea registros de mensajes de contacto de prueba en base de datos:

```bash
php artisan debug:seed-contact --count=10
```

**Archivo:** `app/Console/Commands/Debug/SeedContactDebugCommand.php`

## Seed-all (Comando Maestro)

Ejecuta TODOS los comandos `debug:seed-*` en orden seguro y secuencial:

```bash
# Cantidades estándar
php artisan debug:seed-all

# Cantidades reducidas a 1/5 para rapidez
php artisan debug:seed-all --small
```

**Archivo:** `app/Console/Commands/Debug/SeedAllDebugCommand.php`

## Notas

- Todos los comandos resuelven dinámicamente el `hardware_device_id` y `user_id` de prueba a través de `ResolvesDebugDefaults`.
- Los timestamps se generan en orden descendente desde el momento actual.
- Los valores son aleatorios pero dentro de rangos realistas para cada sensor/lectura.
- El directorio de comandos de debug es `app/Console/Commands/Debug/`.

### Cambios fix_11

- `debug:seed-users` **eliminado**; `debug:seed-all` ya no lo referencia.
- `debug:seed-content`: ya no crea categorías (deben existir vía `CategoriesSeeder`);
  ahora inserta registros en `content_daily_views` (7 días + hoy).
- `debug:seed-energy`: crea asociaciones en `hardware_energy` (auto-monitorización,
  monitorización cruzada y bidireccional).
- `debug:seed-smartplant`: 20 nombres de plantas variados con nombres científicos.
- `debug:seed-weatherstation`: añade `user_id` a todas las inserciones y rellena
  `meteorology_resume_today`, `meteorology_resume_historical`, `meteorology_uv_index`,
  `meteorology_uva` y `meteorology_uvb`.

