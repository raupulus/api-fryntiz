# Design System Specification: High-End Technical Editorial

## 1. Overview & Creative North Star
**Creative North Star: "The Obsidian Architect"**
This design system moves beyond the "SaaS-standard" dashboard. It is designed for high-density technical data—API endpoints, latency metrics, and auth keys—while maintaining the quiet authority of a luxury timepiece. 

We break the "template" look by rejecting the traditional grid of boxed-in widgets. Instead, we use **Intentional Asymmetry** and **Tonal Depth**. The UI should feel like a single, cohesive slab of dark glass where information is revealed through light and soft shifts in value, rather than rigid containers. 

## 2. Color & Surface Architecture
The color palette is rooted in a deep, nocturnal spectrum designed to eliminate eye fatigue during long engineering sessions.

### Surface Hierarchy & The "No-Line" Rule
**Explicit Instruction:** Do not use 1px solid borders to define sections. Layout boundaries must be achieved through background shifts using the following tiers:
- **Base Layer:** `surface` (#0f131d) — The foundation of the application.
- **Sunken Elements:** `surface_container_lowest` (#090e17) — Used for the TopNavBar and code blocks to create a sense of recessed depth.
- **Raised Content:** `surface_container` (#1b2029) — The primary "card" or section background.
- **Elevated Interactive:** `surface_container_high` (#252a34) — For hover states or active workspace panels.

### The Glass & Gradient Rule
To prevent the UI from feeling "flat," use **Glassmorphism** for floating elements (modals, dropdowns, tooltips). 
- **Recipe:** Apply `surface_container_highest` at 70% opacity with a `20px` backdrop-blur.
- **Signature Gradients:** For primary CTAs and data visualizations, use a subtle linear gradient from `primary` (#adc6ff) to `primary_container` (#4d8eff) at a 135-degree angle. This adds "soul" to the technical precision.

## 3. Typography
We use **Inter** exclusively, leaning on its variable font weights to create hierarchy without needing excessive color changes.

- **The Display Scale:** Use `display-md` for high-level metrics (e.g., "99.9% Uptime"). The wide tracking and thin weight convey precision.
- **The Technical Scale:** `label-md` and `label-sm` are the workhorses. They should be used for API keys, status tags, and metadata. 
- **Editorial Intent:** Pair `headline-sm` titles with `body-md` descriptions. Ensure there is a generous `1.5` or `2` spacing unit between the header and the body to allow the typography to breathe—mimicking high-end print magazines.

## 4. Elevation & Depth
In this system, depth is a function of **Luminance**, not shadows.

### The Layering Principle
Stack surfaces to guide the eye. An API Key management section should sit on `surface_container_low` (#171c25), while the individual keys sit on `surface_container_lowest` (#090e17). This "nested" approach creates a natural focus trap for the user.

### Ambient Shadows
If a component must float (e.g., a Command Palette), use a "Tinted Shadow":
- **Shadow Color:** `on_surface` (#dee2f0) at 4% opacity.
- **Blur:** 40px to 60px.
- This mimics natural light reflecting off a dark surface rather than a "dirty" black shadow.

### The "Ghost Border" Fallback
If contrast testing requires a boundary, use the **Ghost Border**:
- Token: `outline_variant` (#424754).
- Opacity: **15%**.
- This provides just enough edge definition for accessibility without breaking the fluid aesthetic.

## 5. Components

### Buttons & Interaction
- **Primary:** Gradient fill (`primary` to `primary_container`). White text (`on_primary_fixed_variant`). `xl` (0.75rem) roundedness.
- **Secondary:** Transparent background with a `Ghost Border`. On hover, shift background to `surface_container_highest`.
- **Tertiary:** Text-only, using `secondary` (#4fdbc8) for the label to indicate actionability without visual weight.

### Input Fields & Code Blocks
- **Inputs:** Use `surface_container_lowest` for the field background. No border. On focus, transition the background to `surface_container` and add a subtle `primary` glow.
- **Code Blocks:** High-contrast background (#090e17) with `0.5rem` padding. Use the `secondary` (teal) palette for syntax highlighting to ensure a "technical" feel.

### Lists & Tables
- **Prohibited:** Horizontal divider lines (`<hr>` or `border-b`).
- **Required:** Use vertical white space (`spacing-4`) or alternating row tints using `surface_container_low`. This keeps the data "airy" and readable.

### Dashboard Specifics
- **The Minimalist TopNavBar:** Must be fixed-position, utilizing `surface_container_lowest` with a `backdrop-blur`. It should feel like a thin, dark ribbon at the top of the screen.
- **The Structured Footer:** Use `surface_container_lowest`. Layout must be strictly symmetrical with four columns of `label-sm` links, providing a grounded, architectural finish to the page.

## 6. Do's and Don'ts

### Do
- **Use "Space as Structure":** Rely on the Spacing Scale (10, 12, 16) to separate major sections.
- **Embrace Asymmetry:** Align primary content to a 65% width column, leaving the remaining 35% for metadata or "Quick Stats" to create an editorial feel.
- **Subtle Motion:** All hover states should have a `200ms ease-out` transition on the background color.

### Don't
- **Don't use pure white (#FFFFFF):** Use `on_surface` (#dee2f0) for text to prevent "halatting" (visual vibration) on dark backgrounds.
- **Don't use 100% opaque borders:** They shatter the "Obsidian Architect" glass aesthetic.
- **Don't crowd the edges:** High-end design requires "wasted" space. Maintain a minimum of `24` (5.5rem) horizontal padding on large viewports.