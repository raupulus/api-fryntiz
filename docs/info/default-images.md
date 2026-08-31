# Imágenes por defecto — Catálogo

> ⛔ **AVISO (2026-08-30, N269): lo que sigue es una PROPUESTA, no el estado del
> código.** Este documento planifica 27 imágenes por módulo en directorios «a
> crear», y eso es un **tercer esquema** que no casa con ninguno de los dos que
> existen de verdad:
>
> | Esquema | Dónde vive | Estado |
> |---|---|---|
> | 5 imágenes por tamaño (`micro`, `small`, `medium`, `normal`, `large`) | `public/images/default/` | ✅ existe y se usa |
> | 10 imágenes de error (`not_found`, `not_authorized`…) | `public/images/default/errors/` | 🟠 declaradas en `App\Traits\HasGenericImages`; **sólo existe `not_found.webp`** |
> | 27 por módulo, en directorios por módulo | — | 🔴 **no existe nada**, y nada lo leería |
>
> Si alguien crea los directorios de este documento, **no los va a leer ningún
> código**. Antes de tocar nada aquí hay que decidir cuál de los tres esquemas
> se queda, y hoy el que manda es `HasGenericImages`.
>
> Lo que sí hace falta de verdad: **crear las 9 imágenes de error que faltan** en
> `public/images/default/errors/`. Mientras no estén, `genericImagePath()` las
> sustituye por `not_found.webp` para no reventar, pero un «no autorizado» y un
> «no es una imagen» se ven igual.


Ruta base: `public/images/default/`

Todas las imágenes deben ser optimizadas para web (WebP o PNG comprimido).
Para añadir una imagen, crear el archivo en la ruta indicada y escribir el nombre
del archivo en la columna "Archivo" de cada tabla.

---

## Usuarios

| Uso | Tamaño recomendado | Proporciones | Ruta | Archivo |
|-----|-------------------|-------------|------|---------|
| Avatar por defecto | 512×512 px | 1:1 | `public/images/default/users/` | _(pendiente)_ |
| Portada de perfil | 1600×400 px | 4:1 | `public/images/default/users/` | _(pendiente)_ |

---

## Contenidos (CMS)

| Uso | Tamaño recomendado | Proporciones | Ruta | Archivo |
|-----|-------------------|-------------|------|---------|
| Imagen de contenido (artículo) | 1200×675 px | 16:9 | `public/images/default/content/` | _(pendiente)_ |
| Imagen de contenido (tutorial) | 1200×675 px | 16:9 | `public/images/default/content/` | _(pendiente)_ |
| Imagen de contenido (proyecto) | 1200×675 px | 16:9 | `public/images/default/content/` | _(pendiente)_ |
| Imagen de contenido (página) | 1200×675 px | 16:9 | `public/images/default/content/` | _(pendiente)_ |
| Imagen de contenido (reseña) | 1200×675 px | 16:9 | `public/images/default/content/` | _(pendiente)_ |
| Imagen de página de contenido | 1200×675 px | 16:9 | `public/images/default/content/` | _(pendiente)_ |
| Thumbnail de contenido | 400×225 px | 16:9 | `public/images/default/content/` | _(pendiente)_ |

---

## Plataformas

| Uso | Tamaño recomendado | Proporciones | Ruta | Archivo |
|-----|-------------------|-------------|------|---------|
| Logo de plataforma | 512×512 px | 1:1 | `public/images/default/platforms/` | _(pendiente)_ |
| Favicon de plataforma | 64×64 px | 1:1 | `public/images/default/platforms/` | _(pendiente)_ |

---

## Categorías

| Uso | Tamaño recomendado | Proporciones | Ruta | Archivo |
|-----|-------------------|-------------|------|---------|
| Imagen de categoría | 512×512 px | 1:1 | `public/images/default/categories/` | _(pendiente)_ |

---

## Tags

| Uso | Tamaño recomendado | Proporciones | Ruta | Archivo |
|-----|-------------------|-------------|------|---------|
| Imagen de tag | 256×256 px | 1:1 | `public/images/default/tags/` | _(pendiente)_ |

---

## Tecnologías

| Uso | Tamaño recomendado | Proporciones | Ruta | Archivo |
|-----|-------------------|-------------|------|---------|
| Icono de tecnología | 128×128 px | 1:1 | `public/images/default/technologies/` | _(pendiente)_ |

---

## Hardware / Dispositivos

| Uso | Tamaño recomendado | Proporciones | Ruta | Archivo |
|-----|-------------------|-------------|------|---------|
| Imagen de dispositivo | 800×450 px | 16:9 | `public/images/default/hardware/` | _(pendiente)_ |
| Icono de tipo de hardware | 128×128 px | 1:1 | `public/images/default/hardware/` | _(pendiente)_ |

---

## Estación Meteorológica

| Uso | Tamaño recomendado | Proporciones | Ruta | Archivo |
|-----|-------------------|-------------|------|---------|
| Imagen de estación meteorológica | 800×450 px | 16:9 | `public/images/default/weather-station/` | _(pendiente)_ |
| Icono de sensor | 64×64 px | 1:1 | `public/images/default/weather-station/` | _(pendiente)_ |

---

## Smart Plant

| Uso | Tamaño recomendado | Proporciones | Ruta | Archivo |
|-----|-------------------|-------------|------|---------|
| Imagen de planta por defecto | 600×600 px | 1:1 | `public/images/default/smart-plant/` | _(pendiente)_ |

---

## Energía Solar

| Uso | Tamaño recomendado | Proporciones | Ruta | Archivo |
|-----|-------------------|-------------|------|---------|
| Imagen de panel solar | 800×450 px | 16:9 | `public/images/default/energy/` | _(pendiente)_ |
| Imagen de batería | 400×400 px | 1:1 | `public/images/default/energy/` | _(pendiente)_ |

---

## AirFlight

| Uso | Tamaño recomendado | Proporciones | Ruta | Archivo |
|-----|-------------------|-------------|------|---------|
| Imagen de avión por defecto | 800×450 px | 16:9 | `public/images/default/airflight/` | _(pendiente)_ |

---

## Impresoras

| Uso | Tamaño recomendado | Proporciones | Ruta | Archivo |
|-----|-------------------|-------------|------|---------|
| Imagen de impresora por defecto | 512×512 px | 1:1 | `public/images/default/printers/` | _(pendiente)_ |

---

## Currículum Vitae

| Uso | Tamaño recomendado | Proporciones | Ruta | Archivo |
|-----|-------------------|-------------|------|---------|
| Foto de CV | 400×500 px | 4:5 | `public/images/default/cv/` | _(pendiente)_ |
| Logo de empresa/formación | 200×200 px | 1:1 | `public/images/default/cv/` | _(pendiente)_ |

---

## Redes Sociales

| Uso | Tamaño recomendado | Proporciones | Ruta | Archivo |
|-----|-------------------|-------------|------|---------|
| Icono de red social genérico | 120×120 px | 1:1 | `public/images/default/social/` | _(pendiente)_ |

---

## General / Compartido

| Uso | Tamaño recomendado | Proporciones | Ruta | Archivo |
|-----|-------------------|-------------|------|---------|
| Placeholder general (imagen no disponible) | 800×450 px | 16:9 | `public/images/default/` | _(pendiente)_ |
| Logo del sitio | 512×512 px | 1:1 | `public/images/default/` | _(pendiente)_ |
| OG Image por defecto (Open Graph) | 1200×630 px | ~1.9:1 | `public/images/default/` | _(pendiente)_ |

---

## Estructura de directorios a crear

```
public/images/default/
├── users/
├── content/
├── platforms/
├── categories/
├── tags/
├── technologies/
├── hardware/
├── weather-station/
├── smart-plant/
├── energy/
├── airflight/
├── printers/
├── cv/
├── social/
└── (archivos generales: placeholder, logo, og-image)
```

---

> Creado: 2026-06-17 · Última revisión: 2026-08-30
