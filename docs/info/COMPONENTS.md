# Catálogo de Componentes UI Reutilizables

Este catálogo documenta los componentes Blade existentes en `resources/views/components/` para interfaces públicas y layouts del proyecto Api Raupulus.

---

> [!IMPORTANT]
> ### Directriz de Reutilización Obligatoria
> **Queda expresamente prohibido a cualquier agente de IA o desarrollador maquetar botones (`<button>`, `<a>` tipo botón) o inputs (`<input>`) con clases Tailwind manuales ad-hoc si ya existe un componente Blade equivalente en el proyecto (`<x-button>`, `<x-input>`).**
> Todos los nuevos desarrollos de vistas deben utilizar los componentes unificados de este catálogo para mantener la coherencia estética, de accesibilidad y del sistema de diseño (Raupulus Slate / Obsidian Flux).

---

## 1. Botón (`<x-button>`)

Componente versátil para acciones y enlaces con estilos de botón. Si se proporciona el atributo `href`, se renderiza automáticamente como una etiqueta `<a>`; en caso contrario, se renderiza como `<button>`.

### Propiedades y Atributos

| Propiedad | Tipo | Valores posibles | Por defecto | Descripción |
|-----------|------|------------------|-------------|-------------|
| `variant` | `string` | `primary`, `secondary`, `danger`, `success`, `outline`, `ghost` | `primary` | Variante cromática según tokens de diseño |
| `size` | `string` | `sm`, `md`, `lg` | `md` | Escala y espaciado del botón |
| `type` | `string` | `button`, `submit`, `reset` | `button` | Atributo nativo HTML para botones |
| `href` | `string|null` | URL / ruta | `null` | Si se define, renderiza enlace `<a>` |
| `icon` | `string|null` | Nombre de icono Material Symbol | `null` | Icono decorativo a la izquierda del texto |
| `disabled`| `bool` | `true`, `false` | `false` | Deshabilita el botón |

### Variantes Visuales

- **`primary`**: Fondo `bg-primary-container` con texto contrastado y elevación sutil.
- **`secondary`**: Fondo `bg-secondary-container` con texto neutro.
- **`danger`**: Fondo `bg-error` con texto blanco para acciones destructivas.
- **`success`**: Fondo esmeralda para confirmaciones y altas exitosas.
- **`outline`**: Borde sutil `border-outline-variant` y fondo transparente.
- **`ghost`**: Sin borde ni fondo por defecto; resalta en hover para acciones de baja jerarquía.

### Snippets de Ejemplo

```blade
{{-- Botón primario de envío en formulario --}}
<x-button type="submit" variant="primary" icon="send">
    Enviar Mensaje
</x-button>

{{-- Botón secundario con enlace --}}
<x-button :href="route('weather_station.index')" variant="secondary" icon="cloud">
    Ver Estación Meteorológica
</x-button>

{{-- Botón de peligro / acción destructiva --}}
<x-button variant="danger" size="sm" icon="delete">
    Eliminar Registro
</x-button>

{{-- Botón transparente sutil --}}
<x-button variant="ghost" size="sm" icon="arrow_back">
    Volver
</x-button>
```

---

## 2. Campo de Entrada (`<x-input>`)

Control de formulario con soporte para etiqueta (`label`), indicador de obligatoriedad, icono integrado a la izquierda, texto de ayuda (`helper`) y detección automática o explícita de errores de validación de Laravel.

### Propiedades y Atributos

| Propiedad | Tipo | Valores posibles | Por defecto | Descripción |
|-----------|------|------------------|-------------|-------------|
| `name` | `string` | Identificador del campo | *(Requerido)* | Nombre del input y clave de validación |
| `label` | `string|null` | Texto de cabecera | `null` | Etiqueta asociada |
| `type` | `string` | `text`, `email`, `password`, `number`, `url`, etc. | `text` | Tipo de input HTML |
| `value` | `mixed` | Valor actual | `null` | Fallback de `old($name, $value)` |
| `placeholder` | `string|null` | Texto indicativo | `null` | Marcador de posición |
| `required` | `bool` | `true`, `false` | `false` | Marca asterisco rojo y atributo required |
| `icon` | `string|null` | Nombre Material Symbol | `null` | Icono posicionado en el lateral izquierdo |
| `helper` | `string|null` | Texto explicativo | `null` | Guía de ayuda bajo el input |
| `error` | `string|null` | Mensaje de error | `null` | Sobrescribe el error de `$errors->first($name)` |

### Snippets de Ejemplo

```blade
{{-- Campo de email corporativo con icono y validación --}}
<x-input
    name="email"
    label="Correo electrónico"
    type="email"
    placeholder="ejemplo@raupulus.dev"
    icon="mail"
    required
    helper="Utiliza tu cuenta corporativa o habitual."
/>

{{-- Campo de contraseña --}}
<x-input
    name="password"
    label="Contraseña"
    type="password"
    placeholder="••••••••"
    icon="lock"
    required
/>
```

---

## 3. Alerta Contextual (`<x-alert>`)

Mensaje de notificación o aviso en línea con soporte para título, icono representativo por severidad y botón de cierre opcional interactivo con Alpine.js.

### Propiedades y Atributos

| Propiedad | Tipo | Valores posibles | Por defecto | Descripción |
|-----------|------|------------------|-------------|-------------|
| `type` | `string` | `info`, `success`, `warning`, `error` | `info` | Severidad y estilo contextual |
| `title` | `string|null` | Título en negrita | `null` | Cabecera destacada de la alerta |
| `dismissible` | `bool` | `true`, `false` | `false` | Muestra botón 'x' para ocultar con animación |
| `icon` | `string|null` | Material Symbol | `null` (auto) | Icono personalizado o el asignado por `type` |

### Snippets de Ejemplo

```blade
{{-- Alerta informativa simple --}}
<x-alert type="info">
    Los datos de la estación meteorológica se actualizan cada 10 minutos.
</x-alert>

{{-- Alerta de éxito descartable con título --}}
<x-alert type="success" title="¡Suscripción confirmada!" dismissible>
    Te hemos enviado un correo de bienvenida a tu bandeja de entrada.
</x-alert>

{{-- Alerta de error crítico --}}
<x-alert type="error" title="Fallo de conexión con sensor">
    El nodo ESP32 no responde a la petición de telemetría.
</x-alert>
```

---

## 4. Diálogo Modal (`<x-modal>`)

Ventana modal con fondo desenfocado (backdrop blur), soporte de cierre por tecla `Escape` o clic exterior, encabezado con título y botón de cierre, y ranura (`footer`) para botones de acción.

### Propiedades y Atributos

| Propiedad | Tipo | Valores posibles | Por defecto | Descripción |
|-----------|------|------------------|-------------|-------------|
| `name` | `string` | Identificador único | *(Requerido)* | Nombre del evento para abrir/cerrar |
| `title` | `string|null` | Título del modal | `null` | Texto del encabezado |
| `maxWidth` | `string` | `sm`, `md`, `lg`, `xl`, `2xl`, `3xl` | `lg` | Ancho máximo del contenedor |

### Snippets de Ejemplo

```blade
{{-- Botón para disparar apertura del modal --}}
<x-button @click="$dispatch('open-modal', 'contacto-modal')" variant="primary" icon="mail">
    Abrir Formulario
</x-button>

{{-- Estructura del modal --}}
<x-modal name="contacto-modal" title="Enviar Mensaje de Contacto" maxWidth="xl">
    <form method="POST" action="{{ route('api.v2.contact.send') }}">
        @csrf
        <div class="space-y-4">
            <x-input name="name" label="Nombre completo" required />
            <x-input name="email" label="Correo electrónico" type="email" required />
            <x-input name="subject" label="Asunto" required />
        </div>

        <x-slot:footer>
            <x-button type="button" @click="$dispatch('close-modal', 'contacto-modal')" variant="outline">
                Cancelar
            </x-button>
            <x-button type="submit" variant="primary" icon="send">
                Enviar
            </x-button>
        </x-slot:footer>
    </form>
</x-modal>
```

---

## 5. Menú Desplegable (`<x-dropdown>`)

Menú contextual desplegable accionado por clic, con cierre automático al pulsar fuera o seleccionar una opción.

### Propiedades y Atributos

| Propiedad | Tipo | Valores posibles | Por defecto | Descripción |
|-----------|------|------------------|-------------|-------------|
| `align` | `string` | `right`, `left`, `top` | `right` | Alineación del panel flotante |
| `width` | `string` | `48`, `56`, `64` o clase Tailwind | `48` | Anchura del desplegable |

### Snippets de Ejemplo

```blade
<x-dropdown align="right" width="56">
    <x-slot:trigger>
        <x-button variant="outline" size="sm" icon="more_vert">
            Opciones
        </x-button>
    </x-slot:trigger>

    <a href="{{ route('home') }}" class="flex items-center gap-2 px-4 py-2 text-sm text-on-surface hover:bg-surface-container transition-colors">
        <span class="material-symbols-outlined text-base">home</span>
        <span>Inicio</span>
    </a>
    <a href="/admin" class="flex items-center gap-2 px-4 py-2 text-sm text-on-surface hover:bg-surface-container transition-colors">
        <span class="material-symbols-outlined text-base">admin_panel_settings</span>
        <span>Panel de Administración</span>
    </a>
</x-dropdown>
```

---

## 6. Barra de Navegación (`<x-navbar>`)

Cabecera flotante fija superior con efecto vidrio (*glass-nav*), cambio dinámico de tema claro/oscuro con Alpine.js y menú adaptativo para dispositivos móviles.

```blade
<x-navbar />
```

---

## 7. Pie de Página (`<x-footer>`)

Pie de página corporativo con paleta Obsidian / Raupulus Slate (`bg-inverse-surface`), créditos a Raúl Caro Pastorino (@raupulus), enlaces a módulos y logos en escala de grises interactivos.

```blade
<x-footer />
```

---

## 8. Bloque de Estadísticas (`<x-block-4-stats>`)

Panel de 4 tarjetas métricas con soporte para iconos, valores numéricos formateados y etiquetas explicativas.

```blade
<x-block-4-stats :stats="$metricsArray" />
```

---

> Creado: 2026-08-26 · Última revisión: 2026-08-26
