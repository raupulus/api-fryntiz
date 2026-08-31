# Constructor Modular de Contenido (Page / Content Builder)

> **Módulo:** Constructor Modular de Bloques (Builder)  
> **Estado:** Operativo  

---

## 1. Resumen y Arquitectura

El **Constructor Modular de Contenido** permite estructurar páginas y artículos mediante una secuencia flexible y ordenada de bloques predefinidos (texto enriquecido, imágenes recortadas, vídeos, botones CTA, citas y código) en lugar de un único campo de texto plano.

### Arquitectura Técnica
1. **Definición en Filament (`app/Filament/Admin/`):**  
   Se utiliza el componente `Filament\Forms\Components\Builder` que permite añadir, reordenar, clonar y eliminar bloques visualmente desde el panel de control.
2. **Persistencia en Base de Datos:**  
   La estructura se almacena en columnas de tipo `JSON` / `JSONB` (`content` o `blocks`) como un array de objetos serializados con soporte multiidioma opcional.
3. **Renderizado en Frontend Blade (`resources/views/`):**  
   El array se itera en Blade incluyendo sub-vistas atómicas o componentes para cada tipo de bloque (`@include('components.blocks.' . $block['type'], ['data' => $block['data']])` o bucle `@switch`), empleando siempre los tokens semánticos corporativos de Tailwind CSS v4 y URLs dinámicas de assets.

---

## 2. Estructura JSON en Base de Datos

Los bloques se serializan en un formato estandarizado que incluye el identificador de tipo (`type`) y los parámetros específicos (`data`):

```json
[
  {
    "type": "text",
    "data": {
      "content": "<h2>Arquitectura de Sistemas</h2><p>Explicación técnica detallada...</p>",
      "alignment": "left"
    }
  },
  {
    "type": "image",
    "data": {
      "image_path": "uploads/content/arquitectura-2026.webp",
      "alt": "Diagrama de bloques de la plataforma",
      "caption": "Figura 1: Flujo de ingestión y persistencia IoT.",
      "aspect_ratio": "16:9"
    }
  },
  {
    "type": "cta",
    "data": {
      "text": "Ver Documentación de la API",
      "url": "https://api.raupulus.dev/docs",
      "variant": "primary",
      "open_in_new_tab": true
    }
  }
]
```

### Soporte Multiidioma
Para modelos con soporte multiidioma (`es`, `en`), la columna almacena las claves idiomáticas en el primer nivel:

```json
{
  "es": [
    { "type": "text", "data": { "content": "<p>Bienvenido...</p>" } }
  ],
  "en": [
    { "type": "text", "data": { "content": "<p>Welcome...</p>" } }
  ]
}
```

---

## 3. Catálogo de Bloques Soportados

### 3.1. Bloque de Texto Enriquecido (`text`)
- **Propósito:** Párrafos, encabezados, listas y enlaces maquetados.
- **Definición en Filament Form:**
  ```php
  Builder\Block::make('text')
      ->label('Texto enriquecido')
      ->icon('heroicon-o-document-text')
      ->schema([
          RichEditor::make('content')
              ->label('Contenido')
              ->required()
              ->toolbarButtons([
                  'bold', 'italic', 'strike', 'link', 'heading',
                  'bulletList', 'orderedList', 'blockquote', 'codeBlock'
              ]),
          Select::make('alignment')
              ->label('Alineación')
              ->options([
                  'left' => 'Izquierda',
                  'center' => 'Centro',
                  'right' => 'Derecha',
              ])
              ->default('left'),
      ]);
  ```
- **Snippet Blade:**
  ```blade
  <div class="prose prose-invert max-w-none text-{{ $data['alignment'] ?? 'left' }}">
      {!! $data['content'] !!}
  </div>
  ```

---

### 3.2. Bloque de Imagen con Cropper (`image`)
- **Propósito:** Fotografía o diagrama con recorte adaptado a proporciones estándar (`16:9`, `4:3`, `1:1`).
- **Definición en Filament Form:**
  ```php
  Builder\Block::make('image')
      ->label('Imagen destacada')
      ->icon('heroicon-o-photo')
      ->schema([
          FileUpload::make('image_path')
              ->label('Fotografía')
              ->image()
              ->imageEditor()
              ->imageEditorAspectRatios([
                  '16:9',
                  '4:3',
                  '1:1',
              ])
              ->disk('public')
              ->directory('uploads/builder')
              ->visibility('public')
              ->required(),
          TextInput::make('alt')
              ->label('Texto alternativo (SEO/Accesibilidad)')
              ->required()
              ->maxLength(255),
          TextInput::make('caption')
              ->label('Pie de foto opcional')
              ->maxLength(255),
      ]);
  ```
- **Snippet Blade:**
  ```blade
  <figure class="my-8">
      <img src="{{ Storage::disk('public')->url($data['image_path']) }}"
           alt="{{ $data['alt'] }}"
           loading="lazy"
           class="w-full rounded-2xl object-cover border border-outline/10 shadow-lg"/>
      @if(!empty($data['caption']))
          <figcaption class="mt-2 text-center text-xs text-muted">
              {{ $data['caption'] }}
          </figcaption>
      @endif
  </figure>
  ```

---

### 3.3. Bloque de Vídeo Embed (`video`)
- **Propósito:** Vídeos externos interactivos (YouTube o Vimeo) embebidos con relación 16:9 responsive.
- **Definición en Filament Form:**
  ```php
  Builder\Block::make('video')
      ->label('Vídeo incrustado')
      ->icon('heroicon-o-video-camera')
      ->schema([
          TextInput::make('url')
              ->label('URL del vídeo (YouTube / Vimeo)')
              ->url()
              ->required(),
          TextInput::make('title')
              ->label('Título accesible')
              ->default('Vídeo explicativo')
              ->required(),
      ]);
  ```
- **Snippet Blade:**
  ```blade
  <div class="relative my-8 aspect-video w-full overflow-hidden rounded-2xl border border-outline/10 shadow-lg">
      <iframe src="{{ $data['url'] }}"
              title="{{ $data['title'] }}"
              class="absolute inset-0 h-full w-full border-0"
              allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
              allowfullscreen></iframe>
  </div>
  ```

---

### 3.4. Bloque de Botón CTA (`cta`)
- **Propósito:** Llamada a la acción hacia documentación, descargas o enlaces de registro.
- **Definición en Filament Form:**
  ```php
  Builder\Block::make('cta')
      ->label('Botón de Acción (CTA)')
      ->icon('heroicon-o-cursor-arrow-rays')
      ->schema([
          TextInput::make('text')
              ->label('Texto del botón')
              ->required()
              ->maxLength(100),
          TextInput::make('url')
              ->label('Enlace de destino')
              ->required(),
          Select::make('variant')
              ->label('Estilo visual')
              ->options([
                  'primary' => 'Primario Técnico',
                  'secondary' => 'Secundario',
                  'outline' => 'Borde resaltado',
              ])
              ->default('primary'),
          Toggle::make('open_in_new_tab')
              ->label('Abrir en nueva pestaña')
              ->default(false),
      ]);
  ```
- **Snippet Blade:**
  ```blade
  <div class="my-8 flex justify-center">
      <x-button :href="$data['url']"
                :variant="$data['variant'] ?? 'primary'"
                :target="!empty($data['open_in_new_tab']) ? '_blank' : '_self'">
          {{ $data['text'] }}
      </x-button>
  </div>
  ```

---

### 3.5. Bloque de Cita Destacada (`quote`)
- **Snippet Blade:**
  ```blade
  <blockquote class="my-8 border-l-4 border-primary pl-6 py-2 italic text-on-surface-variant bg-surface-container-low/30 rounded-r-xl">
      <p class="text-lg">“{{ $data['quote'] }}”</p>
      @if(!empty($data['author']))
          <footer class="mt-2 text-sm font-semibold not-italic text-primary">
              — {{ $data['author'] }}
          </footer>
      @endif
  </blockquote>
  ```

---

## 4. Guía para Añadir Nuevos Bloques

Para incorporar un nuevo tipo de bloque modular (ej. `gallery`, `code`, `metric`):

1. **Definir el Bloque en Filament:**  
   Añade una llamada `Builder\Block::make('[nombre_del_bloque]')` en la colección de bloques del recurso Filament con sus campos correspondientes (validaciones, etiquetas en español y widgets interactivos).
2. **Crear la Plantilla Blade Atómica:**  
   Crea el archivo `resources/views/components/blocks/[nombre_del_bloque].blade.php` recibiendo la variable asociativa `$data`.
3. **Mapear en el Renderizador Central:**  
   En la vista de renderizado de contenido (`resources/views/content/builder.blade.php`), incluye la directiva correspondiente:
   ```blade
   @foreach($blocks as $block)
       @includeIf('components.blocks.' . $block['type'], ['data' => $block['data']])
   @endforeach
   ```
4. **Verificar Accesibilidad y Responsividad:**  
   Comprueba que los nuevos bloques respeten el tema oscuro (`html.dark`) y los contrastes definidos en `docs/info/DESIGN.md`.

---

> Creado: 2026-08-26 · Última revisión: 2026-08-26
