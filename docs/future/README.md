# Futuro

Ideas y trabajos **decididos pero aplazados**. No son deuda técnica ni bugs: son cosas que se
harán cuando la v2 esté estable y desplegada en producción.

Se separan de `docs/planning/` (planificación temporal de lo que se está implementando ahora,
no versionada) y de `docs/info/` (documentación técnica de lo que existe).

| Documento | Qué es | Cuándo |
|-----------|--------|--------|
| [`printers-api.md`](printers-api.md) | API del módulo de impresoras térmicas y cola de impresión | Cuando la v2 esté estable en producción |
| [`content-conteo-palabras-y-tiempo-lectura.md`](content-conteo-palabras-y-tiempo-lectura.md) | Guardar conteo de caracteres y/o palabras por página y exponer tiempo estimado de lectura en la API de Content | Sin fecha; no bloquea el despliegue |
| [`about-page-cms.md`](about-page-cms.md) | Plataforma de contenidos propia del backend para dinamizar `/about` (y otras páginas estáticas del frontend) sin desplegar código | Sin fecha; no bloquea el despliegue |
| [`hardware-vista-dispositivos.md`](hardware-vista-dispositivos.md) | Falta la vista pública de dispositivos hardware: controlador vacío, vistas en blanco y sin ruta | Sin fecha; no bloquea el despliegue |
| [`aemet-simplificar-ozono-total.md`](aemet-simplificar-ozono-total.md) | Simplificar tabla `meteorology_aemet_ozone_total` eliminando columnas fijas de estación (`station_name`, `station_code`) | Sin fecha; cuando se limpie el esquema |

> Creado: 2026-08-30 · Última revisión: 2026-09-16
