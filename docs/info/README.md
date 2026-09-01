# Documentación Técnica de Módulos

Índice de todos los módulos de Api Raupulus con su documentación técnica detallada.

> **⚠️ OBLIGATORIO:** Mantener actualizada esta documentación cuando se modifique un módulo existente o se cree uno nuevo. Ver [AGENTS.md](../../AGENTS.md) para las reglas.

## Índice

| Archivo | Módulo | Descripción |
|---------|--------|-------------|
| [weather-station.md](weather-station.md) | Estación Meteorológica | Sensores meteorológicos + integración AEMET |
| [hardware.md](hardware.md) | Hardware y Energía | Elementos energéticos, instalaciones, lecturas, resúmenes y acumulados |
| [hardware/renogy-rover.md](hardware/renogy-rover.md) | Renogy Rover | Mapa Modbus del controlador solar y cómo entra cada bloque |
| [printers.md](printers.md) | Impresoras | Gestión de impresoras y cola de impresión |
| [keycounter.md](keycounter.md) | Contador de Pulsaciones | Teclado y ratón: pulsaciones, clicks, estadísticas |
| [smart-plant.md](smart-plant.md) | Plantas Inteligentes | Sensores de humedad, luz, temperatura en plantas |
| [airflight.md](airflight.md) | Registro de Vuelos | Detección y tracking de aviones |
| [content.md](content.md) | CMS / Contenidos | Artículos, tutoriales, proyectos, páginas, reseñas |
| [galleries.md](galleries.md) | Galerías | Agrupaciones de imágenes asociables a contenidos |
| [cv.md](cv.md) | Currículum Vitae | 16 secciones de CV + generación PDF |
| [platform.md](platform.md) | Plataformas | Gestión multi-sitio |
| [newsletter.md](newsletter.md) | Newsletter | Suscripción, verificación, gestión de baja |
| [auth.md](auth.md) | Autenticación y Usuarios | Sanctum, Fortify, roles, gestión de usuarios |
| [contact.md](contact.md) | Formulario de Contacto | Mensajes de contacto con reCAPTCHA y filtro de prioridad |
| [files.md](files.md) | Gestión de Archivos | Uploads, thumbnails, redimensión de imágenes |
| [common.md](common.md) | Entidades Comunes | Categorías, tags, tecnologías, idiomas |
| [default-images.md](default-images.md) | Imágenes por defecto | Catálogo de imágenes por defecto por módulo |
| [debug-commands.md](debug-commands.md) | Comandos de Debug | Inserción de datos de prueba para desarrollo |
| [filament-panels.md](filament-panels.md) | Paneles Filament | Inventario de Resources, widgets, clusters y permisos |
| [commands.md](commands.md) | Catálogo de comandos | Todos los comandos Artisan personalizados |
| [websockets.md](websockets.md) | WebSockets (Reverb) | Aviso en vivo de lecturas de estación. Instalado e implementado, **apagado por defecto** (`BROADCAST_CONNECTION=null`) |
| [frontend.md](frontend.md) | Frontend público | Vistas Blade, entrypoints de Vite, tokens de color, los dos componentes Vue, páginas de error y SEO |
| [i18n.md](i18n.md) | Idiomas (i18n) | Cómo se elige el idioma de la respuesta, dónde viven las traducciones y por qué los FormRequests ya no escriben mensajes |
| [mcp.md](mcp.md) | Model Context Protocol (MCP) | Integración con MCP (servidores y herramientas de IA) |
| [apis/aemet.md](apis/aemet.md) | API AEMET OpenData | Cómo usamos AEMET aquí: cuota, cadencias, avisos CAP, caducidad de la clave y atribución |
| [api/v2/README.md](api/v2/README.md) | Índice de rutas de la API V2 | Mapa completo de endpoints propios (`/api/v2/...`) por módulo, con auth, rate limit y enlace al contrato de cada uno. Es la referencia única del mapa de rutas: no se duplica en `AGENTS.md` |
| [api/v2/](api/v2/) | Contratos de la API V2 | Un archivo por módulo (auth, contact, newsletter, content, airflight, hardware, keycounter, smart-plant, weather-station, cv) con el contrato HTTP completo: rutas, auth, parámetros y forma exacta de la respuesta. Pensado para copiarse a otro proyecto. AEMET no tiene archivo aquí: no expone endpoints propios, es solo consumo interno (ver `apis/aemet.md`) |
| [COMPONENTS.md](COMPONENTS.md) | Componentes UI Reutilizables | Catálogo de componentes Blade (<x-button>, <x-input>...) |
| [DESIGN.md](DESIGN.md) | Sistema de Diseño Visual | Identidad Raupulus Slate / Obsidian Flux y tokens Tailwind CSS v4 |
| [content_builder.md](content_builder.md) | Constructor de Contenido | Constructor modular de bloques (Filament Builder + Blade) |
| [_MODULE_TEMPLATE.md](_MODULE_TEMPLATE.md) | Plantilla Oficial de Módulos | Plantilla guía para documentar nuevos módulos en 7 apartados |
| [../deploys/README.md](../deploys/README.md) | Despliegue | Guías de puesta en marcha y sitios virtuales del servidor web |
| [../deploys/deploy-vps.md](../deploys/deploy-vps.md) | Despliegue en VPS | Guía paso a paso Docker / bare-metal |
| [../deploys/websockets-reverb.md](../deploys/websockets-reverb.md) | Despliegue de WebSockets | Demonio Reverb, sitio virtual, certificado y `REVERB_ALLOWED_ORIGINS` |
| [../apis/README.md](../apis/README.md) | APIs de terceros | Documentación oficial destilada y verificada (AEMET) |

> 🌐 **Idioma por defecto del proyecto**: español (`es`), con `fallback_locale` en inglés (`en`) — ver [config/app.php](../../config/app.php) y [auth.md](auth.md).
> Las traducciones se encuentran activas en el directorio raíz `lang/` (`lang/es/`, `lang/en/`), estándar de Laravel.

## Documentación de APIs de terceros

| Directorio | Qué contiene | ¿Va en git? |
|---|---|---|
| [`apis/`](apis/) | **Cómo usamos nosotros** cada API de terceros en esta plataforma | ✅ sí |
| [`../apis/`](../apis/) | La documentación **oficial y verificada** de esa API: endpoints, erratas y límites reales | ✅ sí |
| `../planning/` | Material de trabajo local para preparar cómo se adapta el proyecto | ❌ **no**, y es a propósito |

Desde `apis/<api>.md` se **enlaza** a `../apis/<api>/` cuando haga falta citar la
especificación oficial, en vez de repetir el dato. Si los dos dicen cosas
distintas, manda `../apis/`.

> Los documentos de `docs/info` **no enlazan a `docs/planning`**: no está en el
> repositorio, así que sería un enlace roto para cualquiera que clone.

## Avisos vigentes

Comprobados contra el código el 2026-08-30.

| Aviso | Documento afectado |
|-------|--------------------|
| **Faltan 9 de las 10 imágenes de error.** `genericImagePath()` las sustituye por `not_found.webp` para no reventar, pero un «no autorizado» se ve igual que un «no encontrado» | [default-images.md](default-images.md), [files.md](files.md) |
| **`default-images.md` describe un esquema que no existe** (27 imágenes por módulo). Es una propuesta, no el estado del código | [default-images.md](default-images.md) |

### Resueltos desde la revisión anterior

| Aviso que había | Qué pasó |
|---|---|
| El scheduler invocaba 4 comandos inexistentes | ✅ Arreglado. `SchedulerTest` impide que vuelva a pasar |
| Weather Station y Newsletter sin gestión en Filament | ✅ Hecho |
| Panel Tenant vacío y accesible a cualquier usuario | ✅ Panel construido; el acceso al panel **admin** exige admin o editor |
| Hallazgos de seguridad abiertos desde 2026-07-07 | ✅ Cerrados. Estado en [auth.md](auth.md) y [files.md](files.md) |
| Las traducciones no se cargaban (`resources/lang/`) | ✅ Viven en `lang/` (raíz), que es donde Laravel 13 las lee. `__('clave')` funciona |
| WebSockets documentados pero Reverb no instalado | ✅ `laravel/reverb`, `laravel-echo` y `pusher-js` están instalados. Sigue **apagado por defecto** (`BROADCAST_CONNECTION=null`): encenderlo es una decisión de despliegue, no de código. Ver [websockets.md](websockets.md) |
| La documentación de Scribe estaba generada de antes del rediseño REST | ✅ Regenerada: 47 endpoints, coincide con las rutas reales |
| `docs/api-v2.md` documentaba rutas retiradas (`/auth/login`, `/auth/signup`, `/auth/delete-account`, `/user/{id}`) como si existieran | ✅ Eliminado. El contrato de la API vive ahora por módulo en [api/v2/](api/v2/), verificado contra rutas y código el 2026-08-30 |
| El rol `Editor` (`UserRoleEnum::Editor = 4`) no aparecía en `AGENTS.md` ni en varias skills, y el acceso al panel Admin se seguía describiendo como "solo SuperAdmin/Admin" | ✅ `AGENTS.md`, `auth.md` y las skills de `.agents/skills/` actualizadas: el panel Admin acepta SuperAdmin, Admin y Editor (`User::canAccessPanel()`) |

---

> Creado: 2026-05-25 · Última revisión: 2026-08-30
