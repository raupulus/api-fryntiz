# Design System Document

## 1. Overview & Creative North Star
**Creative North Star: The Data Architect’s Atelier**

This design system is built to bridge the gap between rigorous technical data and high-end editorial elegance. It rejects the "corporate generic" look in favor of a signature aesthetic that feels like a bespoke digital journal for a sophisticated developer. 

We achieve this by moving away from rigid grids and heavy borders. Instead, we use **Tonal Depth** and **Asymmetric Breathing Room**. The layout should feel intentional and curated—where data isn't just displayed, but "presented." We utilize overlapping elements and a dramatic typography scale to create a sense of authority and personal craftsmanship.

---

## 2. Colors
Our palette is a sophisticated interplay of deep navy, slate, and "paper" whites. It is designed to be high-contrast for readability while remaining easy on the eyes for long-form data consumption.

### The Palette (Material Scale)
- **Primary / Core:** `primary` (#000000) and `primary_container` (#050765). Use these for high-authority moments.
- **Surface & Background:** `background` (#f8f9ff) and `surface_container_lowest` (#ffffff).
- **The Accents:** `tertiary_fixed` (#cce5ff) and `on_tertiary_container` (#1d8acd) for interactive data points.

### The "No-Line" Rule
**Explicit Instruction:** Do not use 1px solid borders to separate sections. Structure must be defined by:
1.  **Background Shifts:** A section using `surface_container_low` (#eff4ff) sitting directly on the `surface` (#f8f9ff).
2.  **Negative Space:** Using the Spacing Scale (specifically `12` to `24`) to create a clear "void" between content blocks.

### The "Glass & Gradient" Rule
To add soul to the interface, use **Glassmorphism** for floating elements (like the lightweight Navbar). Apply a backdrop-blur of 12px–20px with a 70% opacity version of `surface_container_lowest`. For Hero sections or primary CTAs, use a subtle linear gradient from `primary_container` (#050765) to `secondary` (#5b5b7d) at a 135-degree angle.

---

## 3. Typography
We use **Inter** as our primary engine. It provides a clean, neutral base that allows the data to speak. By varying weight and scale dramatically, we create a sense of "Premium Documentation."

- **Display Scales (`display-lg` to `display-sm`):** Reserved for high-impact hero statements. Set with tight letter-spacing (-0.02em) to look like a modern broadsheet.
- **Headlines & Titles:** Use `headline-lg` (2rem) for section titles. Ensure generous top margin (`spacing-16`) to let the headline "own" the space below it.
- **Body & Labels:** `body-md` (0.875rem) is the workhorse. For technical data tables, use `label-md` with `secondary` (#5b5b7d) color to create a clear distinction from narrative text.

---

## 4. Elevation & Depth
Depth in this system is organic, not artificial. We mimic the way light hits layered paper or frosted glass.

### The Layering Principle
Hierarchy is achieved by "stacking" surface tiers.
- **Level 0 (Base):** `surface` (#f8f9ff)
- **Level 1 (Sections):** `surface_container_low` (#eff4ff)
- **Level 2 (Cards/Content):** `surface_container_lowest` (#ffffff)

### Ambient Shadows
When an element must float (e.g., a primary card), use an **Ambient Shadow**:
- **Shadow Color:** Tinted with `on_surface` (#121c2a) at 6% opacity.
- **Blur:** Large and diffused (24px to 40px).
- **Spread:** -4px (to keep the shadow tucked under the element, avoiding a "muddy" look).

### The "Ghost Border" Fallback
If an element requires a container but a shadow is too heavy, use a **Ghost Border**:
- **Token:** `outline_variant` (#c7c5d3) at **15% opacity**. This provides a whisper of a boundary without breaking the "No-Line" rule.

---

## 5. Components

### Lightweight Navbar
- **Style:** Minimalist. No background on scroll-start; transition to a Glassmorphic `surface_container_lowest` with 80% opacity and `backdrop-blur` on scroll.
- **Links:** Use `title-sm` (Inter, 1rem) with `on_surface_variant` (#464651). Hover state should be a subtle shift to `primary` (#000000) with a 2px bottom dot rather than an underline.

### Modern Data Cards
- **Style:** Background `surface_container_lowest`. Radius `xl` (0.75rem).
- **Padding:** Always `spacing-6` (1.5rem) or higher. 
- **Content:** Forbid divider lines. Use `spacing-4` (1rem) of vertical whitespace to separate the card header from the body.

### Buttons
- **Primary:** Background `primary_container` (#050765), Text `on_primary` (#ffffff). Radius `full` (9999px) for a "pill" shape that feels approachable.
- **Secondary:** Background `secondary_fixed` (#e2dfff), Text `on_secondary_fixed` (#181836). No border.
- **Tertiary (Ghost):** No background. Text `primary`. Used for low-priority actions like "Learn More."

### The "Refined Footer"
- **Structure:** A deep-tone block using `inverse_surface` (#27313f).
- **The Signature:** Centralize the "Hecho con <heart>" text.
- **Logos:** Place technical stack logos (Laravel, Vue, etc.) in a single row below the "Hecho con" line. Use a grayscale filter with 50% opacity, returning to full color only on hover.

---

## 6. Do's and Don'ts

### Do
- **Use Asymmetry:** Place a large image slightly offset from the text column to create an editorial feel.
- **Embrace White Space:** If a section feels crowded, double the spacing token (e.g., move from `spacing-8` to `spacing-16`).
- **Data Detail:** Use `surface_dim` (#d0dbed) for table header backgrounds to create a "subtle ledge" for data.

### Don't
- **Don't use 100% Black:** Use `on_surface` (#121c2a) for text; it’s softer and more premium than pure #000000.
- **Don't use "Heavy" Borders:** Avoid the standard UI look of boxing everything in. Let the background colors do the work.
- **Don't use Default Shadows:** Never use the `0px 2px 4px rgba(0,0,0,0.1)` defaults. They feel "corporate" and dated. Stick to the Ambient Shadow values.
- **Don't use Dividers:** Avoid horizontal rules (`<hr>`). Use a 24px vertical gap or a subtle color-fill change to denote a new thought.

---
**Director's Note:** Remember, this system is about the *space between the elements* as much as the elements themselves. Keep it breathing, keep it deep, and keep it intentional.**