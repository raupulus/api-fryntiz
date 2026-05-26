# Comandos de Debug — Datos de Prueba

Comandos Artisan para insertar datos de prueba manualmente durante el desarrollo.
**No usar en producción.** No se ejecutan con seeders.

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

Inserta aviones con ICAO/callsign y registros de ruta con coordenadas cercanas a Chipiona:

```bash
# 10 aviones + 100 rutas (por defecto)
php artisan debug:seed-airflight

# Personalizar
php artisan debug:seed-airflight --planes=20 --routes=200
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

## Notas

- Todos los comandos usan `hardware_device_id = 1` por defecto.
- Los timestamps se generan en orden descendente desde el momento actual.
- Los valores son aleatorios pero dentro de rangos realistas para cada sensor.
- El directorio de comandos de debug es `app/Console/Commands/Debug/`.
