---
name: filament-admin
description: >-
  Paneles de administración Filament 5 de Api Raupulus. Cárgala SIEMPRE que
  trabajes bajo app/Filament/: crear o editar Resources, Pages, Widgets o
  Clusters; los dos paneles (Admin para SuperAdmin, Tenant para usuarios);
  campos/componentes personalizados (EditorJsField, ImageCropperUpload,
  YoutubeVideoField); concerns/traits Filament; reglas reutilizables de
  app/Support/FilamentValidationRules; o los PanelProviders en
  app/Providers/Filament/. Incluye el tema/branding del panel
  (resources/css/filament/admin/panel.css). Úsala en cuanto el trabajo mencione
  "panel admin", "Filament", "recurso de administración", "tabla del admin",
  "formulario del back-office", "widget" o "tema del panel", aunque no se nombre
  Filament. Para la API pública usa api-rest-v2; para los modelos usa
  laravel-backend; para el criterio visual (paleta/tipografía) usa design-system.
---

# Filament 5 — Paneles Admin y Tenant

Dos paneles, registrados en `app/Providers/Filament/`:

- **AdminPanelProvider** → panel **Admin**, acceso vía `User::canAccessPanel()`:
  usuario **activo** y con rol **SuperAdmin, Admin o Editor** (`isAdmin() ||
  isEditor()`). No es "solo SuperAdmin": un Editor entra igual, aunque sin el
  bypass de `Gate::before` que sí tiene el SuperAdmin.
- **TenantPanelProvider** → panel **Tenant**, cualquier usuario autenticado y
  activo (es su panel personal).

Estructura bajo `app/Filament/`:

```
Admin/        # Resources (≈16 módulos), Pages, Widgets, Clusters
              # Clusters existentes: AirFlight, Energy, KeyCounter, SmartPlant
Tenant/       # Resources, Pages, Widgets (en construcción)
Components/   # Campos personalizados: EditorJsField, ImageCropperUpload, YoutubeVideoField
Concerns/     # Traits Filament: HasImageFileUpload
```

## Reglas del proyecto

1. **Coloca el recurso en el panel correcto.** Funciones de superadmin → `Admin/`.
   Funciones de usuario final → `Tenant/`. No mezcles.
2. **Agrupa por Cluster** los módulos que ya lo usan (AirFlight, Energy,
   KeyCounter, SmartPlant). Un recurso nuevo de esos dominios va dentro de su
   Cluster, no suelto.
3. **Reutiliza componentes y concerns existentes** antes de crear nuevos:
   - Subida de imágenes → trait `HasImageFileUpload` (`app/Filament/Concerns/`)
     y/o `ImageCropperUpload`.
   - Editor enriquecido de contenido → `EditorJsField`.
   - Vídeo de YouTube → `YoutubeVideoField`.
4. **Validación reutilizable**: usa `app/Support/FilamentValidationRules` en lugar
   de repetir reglas en cada formulario. Los mensajes de validación, en español.
5. **Autorización**: respeta las Policies de `app/Policies/` (el panel Admin ya
   está restringido a SuperAdmin; no reimplementes el control de rol a mano).

## Coherencia con el dominio

- Los modelos que edita Filament son los mismos de `app/Models/<Modulo>/`
  (extienden `BaseModel`). Reutiliza enums de `app/Enums/` en selects/badges en
  lugar de literales (`ContentStatusEnum`, `HardwareTypeEnum`, etc.).
- **Los estilos propios de los paneles van en
  `resources/css/filament/admin/panel.css`**, que cargan los dos PanelProviders
  con `App\Support\FilamentPanelCss` (ver «Vistas Blade propias del panel»).
  ⚠️ `resources/css/filament/admin/theme.css` se compila pero **ningún panel lo
  carga** (no hay `viteTheme()`): editarlo no cambia nada de lo que se ve. Para
  criterio de color/tipografía, ver la skill `design-system`.

## Vistas Blade propias del panel: CSS llano, nunca utilidades de Tailwind

**Regla dura. Ya ha costado dos despliegues rotos:** el campo de YouTube (commit
`3fdcf2f`) y la cabecera de la ficha de Aparatos de Energía (`50af197`). En el
segundo se dio por arreglado con un `npm run build` que no cambiaba nada.

**Por qué.** El panel enlaza **sólo** el `app.css` precompilado de Filament
(`public/css/filament/filament/app.css`), que trae las clases `fi-*` y las que
usa el propio Filament. Las utilidades de Tailwind que escribas en una vista de
`resources/views/filament/` —`flex`, `grid`, `sm:flex-row`, `lg:grid-cols-4`,
`gap-x-8`, `text-gray-500`…— **no existen en el navegador**, compiles lo que
compiles. Unas pocas coinciden por casualidad con clases que Filament ya usa, y
eso es peor: la vista sale medio bien en un sitio y rota en otro. El síntoma
típico es todo apilado a la izquierda.

**Qué sí se puede usar:**

- Los componentes Blade de Filament: `x-filament-panels::page`,
  `x-filament::section`, `x-filament::tabs`, `x-filament::tabs.item`,
  `x-filament::button`, `x-filament::icon`, `x-filament::badge`… Sus estilos
  están en `app.css`.
- Esquemas, tablas, widgets y relation managers de Filament.

**Para maquetar algo propio, en `resources/css/filament/admin/panel.css`.** Es el
único sitio de CSS propio del panel; **nada de `<style>` sueltos en las vistas**.
Lo compila Vite y lo inyecta `App\Support\FilamentPanelCss::etiqueta()` en un
render hook `HEAD_END` de los dos PanelProviders, detrás del `app.css` de
Filament. CSS normal: ni `@import "tailwindcss"` ni `@apply`.

```css
/* ── Energía · ficha de un aparato (ManageEnergyDevice) ── */
.ed-aparato { display: flex; flex-wrap: wrap; align-items: flex-start; gap: 1.5rem; }
.ed-aparato__datos { flex: 1 1 18rem; min-width: 0; }
.ed-aparato__nombre { color: #030712; }
.dark .ed-aparato__nombre { color: #ffffff; }   /* Filament pone `dark` en <html> */
```

- Una sección por pantalla, con un comentario que diga cuál.
- Prefijo propio en las clases (`ed-`…) para no chocar con `fi-*`.
- `flex-wrap` en vez de media queries siempre que se pueda; si hacen falta,
  `@media (min-width: 40rem)`.
- El modo oscuro con `.dark .clase`; nunca `dark:` de Tailwind.
- **Tras tocarlo, `npm run build` y commitear `public/build`**: va versionado y es
  lo que llega al servidor con el `git pull`. `FilamentPanelCssTest` falla si la
  entrada no está en el manifest.
- Con `npm run dev` en marcha, Vite enlaza el servidor de desarrollo en vez de la
  hoja compilada. Para verificar lo que verá producción, fuerza el manifest con
  `app(Vite::class)->useHotFile(<ruta inexistente>)` antes de renderizar.
- Por qué no `viteTheme()` (recompila el tema entero y cambia todo el panel) ni
  `FilamentAsset` (se publica con `composer install`, fuera de git): ver
  `docs/info/filament-panels.md`.

**Comprobar antes de tocar:** qué hojas enlaza de verdad la página renderizada
(buscar `<link … stylesheet>` en el HTML). Si sólo aparece `app.css` de Filament,
esta regla aplica.

## Verificar una vista: los tests no bastan

Que los tests pasen no dice que la vista **se vea** bien ni que aguante los
datos reales. Las dos cosas han fallado en producción con la suite en verde:

- **Maquetación:** ver arriba. Un test no mira el CSS.
- **Datos:** una pestaña reventaba sólo con telemetría dentro, porque en
  PostgreSQL `sum()` de una columna `numeric` llega como cadena y el cierre
  estaba tipado `?float`. El test creaba elementos sin lecturas y la columna ni se
  evaluaba. Agrega en la consulta (`withSum`, `withMax`) en vez de por celda, y
  en los tests de vistas **siembra datos** en todo lo que pinte la pantalla.

**Antes de decir que una vista del panel está terminada, se carga en un navegador
real con datos reales y se mira.** Sin credenciales, con Chrome en headless:

1. Renderizar la página por el kernel de HTTP, autenticado como un admin, contra
   la base local (copia de producción) y guardar el HTML.
2. Copiarlo temporalmente a `public/` y servir con `php -S localhost:8000 -t public`
   (las URLs de assets apuntan a `APP_URL`).
3. Capturar: `"/Applications/Google Chrome.app/Contents/MacOS/Google Chrome"
   --headless=new --window-size=1400,1100 --virtual-time-budget=4000
   --screenshot=captura.png http://localhost:8000/<fichero>.html`.
4. **Mirar la captura.** Luego borrar el HTML de `public/` y parar el servidor.

Y **no se afirma la causa de un fallo sin comprobarla.** «Falta compilar el CSS»
se dijo sin mirar qué hoja cargaba la página, y era falso.

## Al terminar

1. `./vendor/bin/pint`.
2. Si el cambio altera el comportamiento de un módulo, **actualiza
   `docs/info/<modulo>.md`** (sección "Configuración Filament").
3. Verifica que el recurso aparece en el panel correcto y respeta la policy.
4. Si has tocado una vista, **cárgala en un navegador real con datos y mírala**
   (receta en «Verificar una vista»). Sin captura, no está terminada.
