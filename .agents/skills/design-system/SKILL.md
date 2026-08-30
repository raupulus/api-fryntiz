---
name: design-system
description: >-
  Criterio de diseño visual de Api Raupulus (identidad "Raupulus Slate" / tema
  oscuro "Obsidian Flux"): tipografía, color, jerarquía, espaciado, profundidad y
  elemento de firma. Cárgala SIEMPRE que el trabajo implique decisiones de cómo
  se ve y se siente la interfaz: elegir o ajustar paleta, parejas tipográficas o
  escala de tamaños; diseñar una pantalla, hero, tarjeta, navbar o footer nuevos;
  revisar una UI que "se ve genérica" o "muy IA"; o definir tokens de tema. Úsala
  ante frases como "haz que se vea mejor", "elige colores", "qué fuente",
  "diséñame esta página", "dale identidad" o "mejora el aspecto", aunque no se
  diga "diseño". Es el criterio (qué y por qué); para implementarlo en
  Tailwind/Vue usa vue-tailwind-frontend.
---

# Sistema de diseño — Raupulus Slate / Obsidian Flux

Fuentes de verdad en el repo (léelas antes de proponer diseño nuevo):

- `template/normal/raupulus_slate/DESIGN.md` — tema claro.
- `template/dark/obsidian_flux/DESIGN.md` — tema oscuro.
- `template/normal/*/code.html` y `template/dark/*/code.html` — mockups HTML por
  módulo, con su `screen.png`. Son la referencia visual ya aprobada.
- Tokens implementados: `resources/css/app.css` (bloque `@theme` y `html.dark`).

## North Star

**"The Data Architect's Atelier"**: tender un puente entre dato técnico riguroso
y elegancia editorial de gama alta. Rechaza el look "corporativo genérico". Se
logra con **profundidad tonal** y **espacio asimétrico que respira**, no con
rejillas rígidas ni bordes pesados. El dato no se "muestra": se "presenta".

## Tipografía

- Motor único: **Inter** (`--font-family-headline/body/label`). Neutra y limpia
  para que hable el dato.
- Escala dramática: usa `display-*` para hero (con tracking ajustado, -0.02em,
  aire de periódico moderno); `headline-lg` (2rem) para títulos de sección con
  margen superior generoso; `body-md` (0.875rem) como caballo de batalla; en
  tablas técnicas, `label-md` en color `secondary` para separar dato de narrativa.

## Color (escala Material)

Trabaja **siempre con tokens**, nunca hex sueltos en el markup (los hex viven en
`@theme` / `html.dark`).

- Autoridad: `primary` (#000000) y `primary-container` (#050765).
- Base: `background`/`surface` (#f8f9ff) y `surface-container-lowest` (#ffffff).
- Acentos interactivos: `tertiary-fixed` (#cce5ff), `on-tertiary-container`
  (#1d8acd).
- Oscuro (Obsidian Flux): `primary` (#adc6ff), `surface` (#0f131d) y derivados.

## Reglas de marca (las que dan el carácter)

1. **Regla "sin líneas":** no uses bordes sólidos de 1px para separar secciones.
   La estructura se define con **cambios de fondo** (un `surface-container-low`
   sobre `surface`) y **espacio negativo** (escala de spacing 12–24).
2. **Profundidad por capas (tonal):** Nivel 0 `surface` → Nivel 1
   `surface-container-low` (secciones) → Nivel 2 `surface-container-lowest`
   (tarjetas). La jerarquía se "apila", no se enmarca.
3. **Sombras ambientales** para elementos flotantes: color `on-surface` al 6%,
   blur grande y difuso (24–40px), spread -4px (sombra recogida, no "barro").
4. **"Ghost border" de último recurso:** si algo necesita contenedor y la sombra
   pesa demasiado, `outline-variant` al **15%** de opacidad — un susurro, no una
   línea.
5. **Glass & gradient (el alma):** glassmorphism en elementos flotantes (navbar):
   backdrop-blur 12–20px sobre `surface-container-lowest` al ~70–80%. En heroes/
   CTAs, gradiente sutil de `primary-container` → `secondary` a 135°.

## Componentes de referencia

- **Navbar ligera:** sin fondo al inicio; al hacer scroll pasa a glass
  (`surface-container-lowest` ~80% + blur). Enlaces en `title-sm` color
  `on-surface-variant`; hover → `primary` con un punto inferior de 2px, no
  subrayado.
- **Tarjetas de datos:** fondo `surface-container-lowest`, radio `xl` (0.75rem),
  padding ≥ `spacing-6`. **Prohibidas las líneas divisorias**: separa con
  `spacing-4` de espacio vertical.
- **Botones:** primario `primary-container` / texto `on-primary`, forma **pill**
  (`radius-full`); secundario `secondary-fixed` sin borde; terciario "ghost" sin
  fondo, texto `primary`, para acciones de baja prioridad.
- **Footer "refinado":** bloque de tono profundo `inverse-surface` (#27313f),
  firma centrada "Hecho con ♥", y logos del stack (Laravel, Vue…) en una fila en
  escala de grises al 50%, a color solo en hover.

## Cómo entregar diseño aquí

1. Parte de los `DESIGN.md` y los mockups `template/**/code.html`; no inventes un
   lenguaje nuevo, **extiende el existente**.
2. Expresa toda decisión como **tokens** (color/tipografía/espaciado) coherentes
   con `@theme` de `app.css`. Si propones un token nuevo, justifícalo y nómbralo
   en la misma convención Material.
3. Verifica contraste (texto sobre surface/containers) en claro y oscuro.
4. La implementación técnica (clases, dark mode, montaje) está en la skill
   `vue-tailwind-frontend`.
