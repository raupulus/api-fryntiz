# Originales de imagen en WebP en toda la plataforma

> **Estado:** pendiente. Decidido el 2026-09-24 (DUDA-6 del plan de contenidos
> y editor) tratarlo aparte, módulo a módulo.

## Qué se quiere

Que toda imagen que se suba a la plataforma quede guardada con el **original
en WebP** (calidad 85), sin metadatos, girada según su EXIF y con 2560 px de
ancho como máximo, igual que sus copias pequeñas.

## Qué hay hoy

- **Contenidos** (editor de páginas, portada del contenido y de sus páginas,
  imagen SEO): lo hace el plan de contenidos y editor del 2026-09-24 (fase F4).
- **El resto de módulos** (dispositivos, avatares, CV, tecnologías, galerías,
  plataformas…) pasan por `File::addFile()`, que guarda el original en su
  formato (JPEG o PNG). Sólo las copias pequeñas son WebP, y el JPEG se regraba
  con la calidad por defecto de Intervention, 75.

Se pidió en la migración a la v2 y no se hizo.

## Por qué no se hace de golpe

Cada módulo tiene sus pantallas y su parte de API, que no se han revisado en
la auditoría de contenidos. Cambiar el formato del original puede afectar a
cómo se sirven, se recortan o se descargan en cada uno. Se revisa un módulo
cada vez, con sus tests y capturas.

## Cómo se abordaría

1. Llevar a `File` la conversión que haga la fase F4 para contenidos, como
   opción de `addFile()`.
2. Activarla módulo a módulo, comprobando en cada uno la subida, el recorte,
   las miniaturas y lo que sirve su API.
3. Decidir si se convierten los originales ya guardados o sólo los nuevos.

---

> Creado: 2026-09-24 · Última revisión: 2026-09-24
