# Frontend público

La parte que se ve con un navegador: la web pública (raupulus.dev) y las
páginas de los módulos IoT. Blade + Tailwind 4 + Alpine, con una isla de Vue
donde hace falta interactividad de verdad.

El panel de administración es Filament y va aparte; ver
[filament-panels.md](filament-panels.md).

## Qué hay

| | |
|---|---|
| Vistas Blade | 79 |
| Entrypoints de Vite | `css/app.css`, `js/app.js`, `js/vue.js`, `css/filament/admin/theme.css` |
| CSS | Tailwind 4.3 vía `@tailwindcss/vite`, con los colores como tokens en `@theme` |
| JS general | Alpine 3 |
| Componentes Vue | 1: `ChipionaWeatherComponent` |
| jQuery | Ninguno |

```
resources/
├── css/
│   ├── app.css                       ← tokens del tema + Tailwind
│   └── filament/admin/theme.css      ← tema del panel
└── js/
    ├── app.js                        ← Alpine + Echo. Va en todas las páginas
    ├── echo.js                       ← cliente de WebSocket (ver websockets.md)
    ├── vue.js                        ← monta el componente Vue. Sólo donde se pide
    └── vue/Components/
        └── ChipionaWeatherComponent.vue
```

`vue.js` **no** se carga en todas las páginas: se pide con
`@vite('resources/js/vue.js')` desde la vista que lo necesita, hoy sólo
`weather_station/index`.

## Los colores

Están en `@theme` dentro de `resources/css/app.css`, con los nombres del
sistema de diseño (Material-ish): `--color-surface`, `--color-on-surface`,
`--color-primary-container`, `--color-outline`, `--color-error`…

Tailwind 4 genera las clases solo: `bg-surface-container`,
`text-on-surface-variant`, `border-outline`. **En las vistas no se escribe un
color literal**: si hace falta uno nuevo, se añade el token.

## El componente Vue

### `ChipionaWeatherComponent`

El widget del clima. Se monta sobre `#app-weather-chipiona`, que le pasa por
`data-*` la URL base, la ruta de la colección y —opcionalmente— el id de la
estación.

- **Sin id** pide `GET /api/v2/weather-stations`, que es una colección: se
  queda con la primera, que es la estación principal.
- **Con id** pide `GET /api/v2/weather-stations/{id}`, que devuelve el objeto.
- Se refresca por **WebSocket** si hay Reverb montado (`window.Echo`), y por
  sondeo si no. Sin socket, cada 65 s; con socket, cada 5 minutos y sólo como
  red de seguridad.
- Tiene estado de **carga** y de **error** visibles. Si ya hay datos en
  pantalla, un fallo puntual de red no los borra ni marca error: lo que no
  puede pasar es que un 500 se vea como «0 ºC y 0 % de humedad».

## Páginas de error

`resources/views/errors/` — 401, 403, 404, 419, 429, 500 y 503. Las siete
extienden `layouts.app` e incluyen `errors/_pagina.blade.php`, que es el cuerpo
común. Para cambiar el diseño de todas se toca un fichero.

Cada página sólo declara su código, su titular y su frase.

## Cómo se compila

```bash
pnpm install
pnpm dev      # desarrollo, con recarga
pnpm build    # producción, a public/build/
```

---
## SEO

| Dónde | Qué hace |
|---|---|
| `layouts/app.blade.php` | `<head>` con title, description, keywords, Open Graph y Twitter Card, todos con `@yield` para que cada vista los pise |
| `app/Models/Content/ContentSeo.php` | Las etiquetas de un contenido: genéricas, sociales y de X. Incluye `og:image:width`, `og:image:height` y `og:image:type`, que salen de la miniatura real vía `File::thumbnailModel()` |
| `SitemapGeneratorCommand` | `php artisan sitemap:generate` → `public/sitemap.xml`. Portada, «sobre mí», documentación, plantas (una por planta), energía, contador de pulsaciones, vuelos y estación meteorológica (índice + una página por sensor con datos) |
| `public/robots.txt` | Bloquea `/admin`, `/panel`, `/livewire`, sesión y cuenta, y las URL con token (`/newsletter/`, `/cv/s/`). `/docs` sí se indexa |

`File::thumbnailModel()` devuelve el **modelo** de la miniatura, no sólo su URL, que
es lo que permite rellenar el ancho, el alto y el mime. Además resuelve el
tamaño en **una** consulta: antes bajaba tamaño por tamaño con un `first()` en
cada vuelta, hasta cinco consultas para pintar una imagen, y eso se llama una
vez por fila en cualquier listado.

## Lo que sigue pendiente

Cosas que necesitan un navegador de verdad y no se pueden cerrar desde aquí:

- [ ] `pnpm build` y comprobar el tamaño del *bundle*.
- [ ] Lighthouse en las cinco páginas públicas principales (objetivo: >90 en
      las cuatro categorías) y guardar el informe como línea base.
- [ ] Repaso visual de las siete páginas de error y de las vistas IoT, en móvil
      y en escritorio, con datos y sin datos.
- [ ] Modo oscuro: el panel lo tiene, la web pública no. Decidir si lo lleva.
- [ ] JSON-LD (`Article`, `Person`, `BreadcrumbList`, `Dataset` para los datos
      meteorológicos, que encaja bien y es poco habitual).
- [ ] Imágenes: `loading="lazy"`, `width`/`height` explícitos y WebP.
- [ ] Antigüedad del dato visible en las vistas IoT («hace 3 minutos» dice más
      que una marca de tiempo).

---

> Creado: 2026-08-30 · Última revisión: 2026-08-30
