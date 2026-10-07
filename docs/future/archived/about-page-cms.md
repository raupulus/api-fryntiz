# Dinamizar la página "Sobre el proyecto" desde una plataforma de contenidos propia

> **Estado:** idea, sin diseñar en detalle. No bloquea nada.

## Qué se quiere

`resources/views/about.blade.php` (ruta `/about`, nombre `about`) se creó el 2026-09-14 con todo
el contenido escrito a mano en el propio Blade: descripción del proyecto, listado de módulos,
stack tecnológico y autoría. Sirve para tener ya la página pública, pero es contenido estático que
sólo se cambia tocando código y desplegando.

La idea a futuro es crear una plataforma en el backend, **pensada para la propia API** (no para
los proyectos de terceros que ya consumen el CMS existente en `app/Models/Content/`), desde la que
poder editar el contenido de páginas del propio frontend público —empezando por "Sobre el
proyecto"— sin tocar código ni desplegar.

## Por qué no reutilizar sin más el CMS de `Content`

El CMS actual (`docs/info/content.md`) es **multi-plataforma**: sirve contenido a los distintos
proyectos externos de Raúl (blogs, otras webs), con su propio modelo de plataforma/slug. Mezclar
ahí las páginas propias del frontend de esta API metería una plataforma más entre las de terceros,
cuando conceptualmente es una cosa distinta: contenido **de esta aplicación, para esta
aplicación**. Hay que decidir si:

1. Se modela como una plataforma más dentro del CMS existente (reutiliza todo lo que ya hay: SEO,
   versionado, editor), o
2. Se crea un modelo aparte, más simple, pensado sólo para páginas estáticas del propio frontend
   (menos flexible, pero sin arrastrar conceptos multi-plataforma que no aplican).

## Qué hay que decidir antes de implementarlo

1. **Modelo de datos**: si reutiliza `Content`/`ContentPage` (con una `Platform` propia para "Api
   Raupulus") o si es un modelo nuevo y más simple sólo con título, bloques de texto e imágenes.
2. **Editor**: si usa el mismo `EditorJsField` que ya existe en el panel Filament, o un editor Blade
   más simple dado que estas páginas son pocas y cambian poco.
3. **Alcance inicial**: sólo `/about`, o también otras secciones estáticas del home (los bloques
   "Sobre esta api", "Estación Meteorológica", etc. de `resources/views/home.blade.php`, que hoy
   también están escritos a mano).
4. **SEO**: si al dinamizarse pasa a usar `ContentSeo` igual que el resto de contenidos del CMS
   (ver skill `seo`), para no perder los metadatos que ya tiene la vista estática.

## Dónde tocar cuando se aborde

| Sitio | Qué |
|---|---|
| `database/migrations/` | Según la decisión del punto 1: nueva `Platform`/tabla, o modelo nuevo |
| `App\Http\Controllers\...` | Controlador que lea el contenido dinámico en vez de la vista estática |
| `resources/views/about.blade.php` | Pasa de contenido fijo a renderizar lo que venga del backend |
| `App\Filament\Admin\Resources\...` | Recurso Filament para editar la página desde el panel |
| `docs/info/frontend.md` | Documentar de dónde sale el contenido de `/about` una vez dinamizado |

> Creado: 2026-09-14 · Última revisión: 2026-09-14
