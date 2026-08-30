---
name: vue-tailwind-frontend
description: >-
  Frontend de Api Raupulus: Blade + Tailwind CSS 4 + Alpine.js + Vue 3 montado
  con Vite (pnpm). Cárgala SIEMPRE que toques resources/js/ o resources/css/,
  crees o edites componentes .vue, escribas o estilices vistas Blade, configures
  Tailwind o Vite, decidas entre Alpine y Vue para una interacción, o gestiones
  entrypoints/assets del frontend. Úsala en cuanto el trabajo mencione "Vue",
  "componente", "Tailwind", "clase CSS", "vista", "Blade", "Vite", "pnpm" o
  "interactividad del cliente", aunque no se nombre el framework. Para criterio
  de paleta/tipografía/look&feel usa design-system; para el tema del panel admin
  usa filament-admin.
---

# Frontend — Vue 3 + Tailwind 4 + Alpine (Vite/pnpm)

**Package manager: `pnpm`** (no npm/yarn). Build con **Vite** +
`laravel-vite-plugin`, `@tailwindcss/vite` y `@vitejs/plugin-vue`.

```bash
pnpm dev      # servidor de desarrollo
pnpm build    # build de producción
```

Entrypoints declarados en `vite.config.js`:
`resources/css/app.css`, `resources/js/app.js`, `resources/js/vue.js`,
`resources/css/filament/admin/theme.css`.

## Cuándo Vue y cuándo Alpine

- **Alpine.js** para interactividad ligera embebida en Blade (toggles, menús,
  pequeños estados). Es la opción por defecto para "un poco de JS en la vista".
  **Está instalado y activo de verdad en el frontend público** (dependencia
  npm `alpinejs`, no algo que "venga con Filament"): se importa en
  `resources/js/app.js` (`import Alpine from 'alpinejs'; window.Alpine =
  Alpine; Alpine.start();`) y ya se usa en `layouts/app.blade.php` y en los
  componentes `navbar`, `dropdown`, `modal` y `alert` de
  `resources/views/components/`. Filament/Livewire cargan su **propia**
  instancia interna para el panel admin — es independiente de esta; no hay que
  instalar ni configurar nada más para usar Alpine en una vista pública nueva,
  solo escribir `x-data`/`x-show`/etc. en el Blade.
- **Vue 3** para componentes con estado real, datos remotos o lógica de UI
  compleja (tablas, widgets de datos, cropper de imágenes).

No montes una SPA: aquí Vue se usa como **islas** sobre páginas Blade.

## Patrón de montaje de Vue (islas)

Los componentes viven en `resources/js/vue/Components/` (`.vue` SFC). Se montan
en `resources/js/vue.js` **solo si existe el contenedor** en la página, pasando
props desde `data-*`:

```js
import { createApp } from 'vue';
import TableComponent from './vue/Components/TableComponent.vue';

const el = document.getElementById('app-sensor-table');
if (el) {
    createApp(TableComponent, {
        url: el.dataset.apiUrl,
        title: el.dataset.title,
        csrf: el.dataset.csrf,
    }).mount(el);
}
```

En Blade, el contenedor + `@vite('resources/js/vue.js')` solo en las vistas que
lo necesiten:

```blade
<div id="app-sensor-table"
     data-api-url="{{ route('...') }}"
     data-title="Sensores"
     data-csrf="{{ csrf_token() }}"></div>
@vite('resources/js/vue.js')
```

Nota: `vite.config.js` usa el alias `vue → vue/dist/vue.esm-bundler.js`, y los
componentes consumen la API V2 (envelope `{success, message, data}`; ver skill
`api-rest-v2`). Al pintar HTML de la API valida/escapa: no uses `v-html` con
contenido no confiable.

## Tailwind 4 — tokens de tema, no colores sueltos

Tailwind 4 se configura **en CSS** con `@theme` en `resources/css/app.css`. El
proyecto define un sistema de tokens estilo Material (Raupulus Slate) y un tema
oscuro que se activa con `class="dark"` en `<html>`:

```css
@import "tailwindcss";

@theme {
    --color-primary: #000000;
    --color-primary-container: #050765;
    --color-surface: #f8f9ff;
    --color-on-surface: #121c2a;
    --font-family-headline: 'Inter', sans-serif;
    --font-family-body: 'Inter', sans-serif;
}

html.dark {
    --color-primary: #adc6ff;
    --color-surface: #0f131d;
    /* … */
}
```

Reglas:

1. **Usa los tokens existentes** (`bg-surface`, `text-on-surface`,
   `bg-primary-container`, etc.). No introduzcas hex sueltos en las clases ni
   redefinas colores ad hoc.
2. **Modo oscuro** vía los mismos tokens redefinidos en `html.dark`; no dupliques
   componentes para dark — deja que los tokens hagan el trabajo.
3. **Tipografía**: familia **Inter** mediante las variables `--font-family-*`.
4. Para *decidir* nuevos tokens, paleta o jerarquía tipográfica, usa la skill
   `design-system` (criterio y reglas de marca). Esta skill es el "cómo" técnico;
   `design-system` es el "qué/por qué" visual.

## Organización de assets JS

`resources/js/` separa responsabilidades: `app.js` (global; aquí vive el
arranque de Alpine, ver arriba), `vue.js` (islas Vue), `echo.js` (cliente de
Reverb/Broadcasting), `vue/` (componentes `.vue`), `dashboard/*` (back-office
legacy), `components/*`. Alpine no tiene un entrypoint propio: su arranque va
dentro de `app.js`. Mantén ese reparto; no metas lógica de un dominio en
`app.js`.
