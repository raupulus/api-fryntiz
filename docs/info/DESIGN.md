# Sistema de Diseño Visual — Raupulus Slate & Obsidian Flux

Documento maestro con la especificación técnica de identidad visual, maquetación, paleta de colores y tokens de Tailwind CSS v4 del proyecto Api Raupulus.

---

## 1. Filosofía y "North Star"

**"The Data Architect's Atelier"**: Conectar el dato técnico y la telemetría rigurosa con la elegancia editorial de gama alta.
Se rechaza el aspecto corporativo plano o genérico. El sistema busca **profundidad tonal**, **espacio asimétrico que respira** y una jerarquía natural donde los datos no solo se muestran, sino que se presentan con valor analítico.

---

## 2. Tipografía

- **Familia Tipográfica Principal:** **`Inter`** (`sans-serif`), cargada desde Google Fonts con pesos de 100 a 900.
  - `--font-family-headline`: `'Inter', sans-serif`
  - `--font-family-body`: `'Inter', sans-serif`
  - `--font-family-label`: `'Inter', sans-serif`
- **Iconografía:** **Material Symbols Outlined** (Google Fonts) con grosor y relleno configurables (`font-variation-settings`).
- **Jerarquía Tipográfica:**
  - **Display / Hero:** Títulos con tracking ligeramente negativo (`tracking-tighter` / `-0.02em`) para presencia moderna.
  - **Headline LG (2rem):** Cabeceras de sección con espaciado superior generoso.
  - **Body MD (0.875rem):** Cuerpo de texto estándar, legible y contrastado.
  - **Label MD / SM:** Etiquetas técnicas y badges con `font-semibold` o `font-bold`.

---

## 3. Paleta Cromática y Tokens Tailwind CSS v4

La paleta se define en **`resources/css/app.css`** utilizando la directiva `@theme` de Tailwind CSS v4 para el modo claro (*Raupulus Slate*) y la clase `html.dark` para el modo oscuro (*Obsidian Flux*).

### A. Tema Claro — Raupulus Slate

| Token | Hex | Propósito |
|-------|-----|-----------|
| `--color-primary` | `#000000` | Máxima autoridad y contraste |
| `--color-primary-container` | `#050765` | Botones primarios, acentos profundos |
| `--color-on-primary` | `#ffffff` | Texto sobre contenedores primarios |
| `--color-secondary` | `#5b5b7d` | Elementos de apoyo, etiquetas secundarias |
| `--color-secondary-container` | `#d8d6fe` | Fondos de chips y badges secundarios |
| `--color-tertiary-fixed` | `#cce5ff` | Acentos interactivos |
| `--color-on-tertiary-container` | `#1d8acd` | Enlaces y llamadas a la acción |
| `--color-background` / `--color-surface` | `#f8f9ff` | Fondo base de la página |
| `--color-surface-container-lowest` | `#ffffff` | Fondo de tarjetas y elementos elevados |
| `--color-surface-container-low` | `#eff4ff` | Fondos de sección alternativa |
| `--color-surface-container` | `#e6eeff` | Estados hover y agrupaciones intermedias |
| `--color-surface-container-high` | `#dee9fc` | Paneles con mayor elevación |
| `--color-on-surface` | `#121c2a` | Texto principal sobre fondo claro |
| `--color-on-surface-variant` | `#464651` | Texto secundario y descriptivo |
| `--color-outline-variant` | `#c7c5d3` | Bordes sutiles y divisores discretos |
| `--color-error` | `#ba1a1a` | Estados de error y alertas destructivas |
| `--color-inverse-surface` | `#27313f` | Fondo oscuro del footer y contrastes invertidos |

### B. Tema Oscuro — Obsidian Flux (`html.dark`)

| Token | Hex | Propósito |
|-------|-----|-----------|
| `--color-primary` | `#adc6ff` | Acentos principales y enlaces destacados |
| `--color-primary-container` | `#4d8eff` | Botones primarios en modo oscuro |
| `--color-on-primary` | `#002e6a` | Texto de alto contraste sobre botón primario |
| `--color-secondary` | `#4fdbc8` | Acento cian para telemetría y sensores |
| `--color-surface` / `--color-background`| `#0f131d` | Fondo base oscuro |
| `--color-surface-container-lowest` | `#090e17` | Fondo profundo de tarjetas |
| `--color-surface-container-low` | `#171c25` | Fondos de contenedores y bloques |
| `--color-surface-container` | `#1b2029` | Capas intermedias y cards interactivas |
| `--color-on-surface` | `#e2e8f0` | Texto principal legible sobre fondo oscuro |
| `--color-on-surface-variant` | `#94a3b8` | Texto secundario atenuado |

---

## 4. Reglas de Maquetación y Layout

1. **Regla de "Cero Líneas":** Evitar bordes duros de 1px para delimitar secciones. La separación estructural se consigue mediante **cambios en el tono del fondo** (ej. de `bg-surface` a `bg-surface-container-low`) y **espaciado vertical amplio** (`py-12` a `py-20`).
2. **Profundidad por Capas (Tonal Stacking):**
   - Nivel 0: `bg-surface` (Lienzo principal).
   - Nivel 1: `bg-surface-container-low` (Bloques de contenido y secciones).
   - Nivel 2: `bg-surface-container-lowest` (Tarjetas de métricas y formularios con `rounded-2xl` o `rounded-3xl`).
3. **Elevación y Sombras Suaves:**
   - Para elementos flotantes y tarjetas se emplean sombras difusas con opacidad baja (`shadow-sm`, `shadow-lg` suaves) evitando halos negros artificiales.
4. **Efecto Vidrio (Glassmorphism):**
   - La barra de navegación utiliza la clase `.glass-nav` (`backdrop-blur-md` sobre fondo translúcido) para integrarse orgánicamente durante el desplazamiento.
5. **Formas "Pill" para Acciones:**
   - Botones principales y chips de estado utilizan radios completos (`rounded-full`) proporcionando un tacto moderno y amigable.

---

## 5. Prevención de FOUC (Flash of Unstyled Content)

El layout base `resources/views/layouts/app.blade.php` incluye una pequeña rutina inline en el `<head>` que comprueba el almacenamiento local (`localStorage.getItem('theme') === 'dark'`) e inyecta la clase `dark` en la etiqueta `<html>` **antes** de evaluar el árbol de renderizado del navegador.

---

> Creado: 2026-08-26 · Última revisión: 2026-08-26
