---
name: seo
description: >-
  SEO técnico y on-page de Api Raupulus (CMS multi-plataforma con Laravel). Cárgala
  SIEMPRE que el trabajo toque metadatos/SEO: el modelo ContentSeo y sus campos
  (description, keywords, robots, og_*, twitter_*), las meta del layout en
  resources/views/layouts/head.blade.php, el sitemap (spatie/laravel-sitemap,
  comando sitemap:generate), datos estructurados JSON-LD/schema.org, canonicals,
  hreflang/multi-idioma, o cuando se hable de posicionamiento, rich snippets,
  indexación o "por qué no aparezco en Google". Úsala ante "SEO", "meta tags",
  "Open Graph", "sitemap", "schema", "metadatos" o "rankings", aunque no se diga
  SEO explícitamente. Para escribir las vistas usa vue-tailwind-frontend; para el
  modelo de datos usa laravel-backend.
---

# SEO — Api Raupulus

El SEO de contenidos está modelado, no improvisado. Antes de añadir metadatos a
mano, comprueba si ya hay un campo o mecanismo para ello.

## Modelo de datos: `ContentSeo`

`app/Models/Content/ContentSeo.php` (tabla `content_seo`), relación 1–1 con
`Content`. Campos: `description`, `keywords`, `robots`, `distribution`,
`revisit_after`, `image_id` + `image_alt`, `og_title`, `og_type`,
`twitter_card`, `twitter_creator`. Expone `getGenericTags()` (author, copyright,
distribution, description, robots, keywords…).

Reglas:

1. Los metadatos SEO de un contenido se persisten en `ContentSeo`, **no** se
   hardcodean en la vista. Si falta un dato SEO nuevo, añádelo como campo de
   `content_seo` (migración con comentario + actualizar `docs/info/content.md`;
   ver skills `postgresql-migrations` y `laravel-backend`).
2. La imagen social usa la relación `image` (`File`) con su `image_alt`.

## Meta tags en la vista

El `<head>` se compone en `resources/views/layouts/head.blade.php`
(y `layouts/app.blade.php`). Cada página debe emitir:

- `title` único y `meta name="description"` (desde `ContentSeo` cuando exista).
- **Open Graph**: `og:title`, `og:type`, `og:description`, `og:image`,
  `og:url` — alimentados por los campos `og_*` de `ContentSeo`.
- **Twitter Card**: `twitter:card`, `twitter:creator` desde los campos `twitter_*`.
- `meta name="robots"` desde `ContentSeo.robots` (respeta noindex cuando aplique).
- **Canonical** correcto por plataforma/slug (el CMS es multi-plataforma: la URL
  canónica depende de `platformSlug` + `contentSlug`).

## Sitemap

Generado con **spatie/laravel-sitemap** vía
`app/Console/Commands/SitemapGeneratorCommand.php`
(`php artisan sitemap:generate`, programado **daily** en `routes/console.php`).

- El comando cachea con lock (`--force` para regenerar, `--chunk=N`).
- El archivo resultante (`public/sitemap.xml`) está **gitignored** (es artefacto).
- Si creas un nuevo tipo de contenido público o ruta indexable, **añádela al
  generador** para que entre en el sitemap, con `lastmod`/`changefreq`/`priority`
  coherentes.

## Datos estructurados (JSON-LD)

Para rich snippets, inyecta JSON-LD `application/ld+json` en el `<head>` según el
tipo de contenido (Article/TechArticle para artículos y tutoriales,
BreadcrumbList para navegación, Organization/Person para autoría). Deriva los
campos de `Content` + `ContentSeo` + autor; no dupliques datos que ya están en el
modelo.

## Multi-idioma

El CMS maneja idiomas (seeders de languages en los tests). Si una pieza existe en
varios idiomas, emite `hreflang` recíprocos y un `canonical` por idioma. Mantén
coherencia entre `hreflang`, canonical y las URLs reales por plataforma.

## Checklist SEO al publicar/editar contenido

1. `ContentSeo` relleno (description, og_*, twitter_*, robots, image+alt).
2. `head.blade.php` emite title/description/OG/Twitter/canonical correctos.
3. JSON-LD del tipo adecuado.
4. La URL entra en `sitemap:generate` si es pública.
5. `hreflang` si hay variantes de idioma.
6. Actualiza `docs/info/content.md` si cambian campos o lógica SEO.
