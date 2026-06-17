# Skills del proyecto — Api Raupulus

Skills específicas de este proyecto, versionadas en git. Cada una vive en
`<nombre>/SKILL.md` con frontmatter YAML (`name`, `description`) y un cuerpo con
las convenciones reales del dominio.

## Cómo se cargan

- **Claude Code**: se auto-activan por su campo `description` (el modelo decide
  consultarlas según el contexto de la tarea).
- **Otros agentes**: la tabla de enrutado de `../../AGENTS.md` indica cuándo
  cargar cada una.

`.claude/skills/` está incluido en git mediante una excepción en `.gitignore`
(`.claude/*` + `!.claude/skills/`); el resto de `.claude/` (p. ej.
`settings.local.json`) sigue ignorado.

## Skills disponibles

| Skill | Dominio |
|-------|---------|
| `laravel-backend` | Backend base (Models, Services, Actions, Jobs, Events, Enums, Policies, Traits) |
| `api-rest-v2` | API REST V2 (controllers, Resources, FormRequests, rutas, Sanctum/IoT) |
| `postgresql-migrations` | Migraciones y modelado PostgreSQL |
| `filament-admin` | Paneles Filament 5 (Admin/Tenant) |
| `vue-tailwind-frontend` | Frontend Vue 3 + Tailwind 4 + Alpine (Vite/pnpm) |
| `design-system` | Criterio visual (Raupulus Slate / Obsidian Flux) |
| `seo` | SEO técnico y on-page |
| `mcp-server` | Integración MCP (`laravel/mcp`) |
| `printers` | Módulo Impresoras (mapa) |

## Reglas de enrutado (desambiguación entre hermanas)

- Lógica bajo `app/` que no sea API/Filament/MCP → `laravel-backend`.
- "Resource" **de Filament** → `filament-admin`; **JsonResource/API** → `api-rest-v2`.
- Esquema/tabla/índice/FK → `postgresql-migrations`; método/relación/scope de modelo → `laravel-backend`.
- **Criterio** visual (qué color/fuente, "se ve genérico") → `design-system`; **implementación** (clase Tailwind, `.vue`, dark mode) → `vue-tailwind-frontend`.
- Meta/OG/sitemap/schema en vistas → `seo` (aunque toque `head.blade.php`).
- Tema/branding del **panel admin** (`theme.css`) → `filament-admin`.

## Optimizar el auto-trigger (opcional)

El optimizador oficial de descripciones (skill-creator) ajusta el campo
`description` midiendo la precisión de activación con consultas reales. Requiere
el CLI `claude` **autenticado** (`claude /login`).

El set de evals de enrutado está en `triggering-eval.json` (consultas que deben y
que no deben activar cada skill, incluidos casos límite entre hermanas). Úsalo
como base para el optimizador o para validar a mano tras editar descripciones.
