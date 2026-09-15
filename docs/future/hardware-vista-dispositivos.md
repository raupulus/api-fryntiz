# Vista pública de dispositivos hardware

> **Estado:** pendiente de implementar. No bloquea nada.
> **Detectado el:** 2026-09-15, revisando el impacto del frontend en las rutas
> que exponen JSON (weatherstation/airflight) — al descartar `hardware` de esa
> revisión (no expone nada, `/energy` es la única ruta real) salió que la vista
> de dispositivos ni siquiera existe.

## Qué hay hoy

| Pieza | Estado |
|-------|--------|
| `App\Http\Controllers\Hardware\HardwareDeviceController` | Vacío, sin métodos (`app/Http/Controllers/Hardware/HardwareDeviceController.php`) |
| `resources/views/hardware/index.blade.php` | Plantilla vacía (`@extends('layouts.app')` y secciones en blanco) |
| `resources/views/hardware/show.blade.php` | Igual, plantilla vacía |
| Ruta en `routes/hardware/web.php` | **No existe.** Sólo está registrado `/energy` (`EnergyController::index`) |

Nada de esto es alcanzable: sin ruta, controlador y vistas son código muerto.
La única página pública del módulo hardware hoy es `/hardware/energy`.

## Qué falta

Una vista pública de dispositivos (equivalente a lo que ya tienen
`weather_station`/`airflight`/`smartplant`): listado (`index`) y ficha por
dispositivo (`show`), sirviéndose de `App\Models\Hardware\HardwareDevice` y su
tipo (`HardwareType`), imagen, último estado conocido (`temp`, `voltage`,
`battery_level`, `last_seen_at`…) y, si aplica, sus componentes
(`HardwareComponent`).

Al implementarla, aplican los mismos criterios ya fijados en este hilo para
cualquier ruta JSON que consuma esa vista desde el navegador sin token:
`same-origin` (`App\Http\Middleware\EnsureRequestIsSameOrigin`) +
`throttle:public-widget` — ver `docs/info/weather-station.md` y
`docs/info/airflight.md` para el patrón exacto.

## Cuándo retomarlo

Sin fecha. Es la persona que decide qué se enseña de cada dispositivo (todos,
sólo los activos, con o sin número de serie/IPs — datos que hoy sólo ve el
panel de administración) antes de escribir la vista.
