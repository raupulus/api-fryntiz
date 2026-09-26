// Textos de Editor.js y sus herramientas en español.
//
// Las claves son los textos originales en inglés, agrupados como los busca
// Editor.js:
// - `ui`, `toolNames` y `blockTunes`: el núcleo (menús, nombres de bloque,
//   botones de mover y borrar) y los ajustes de bloque (`textVariant`);
// - `tools.<nombre>`: lo que cada herramienta pasa por `api.i18n.t()`, con el
//   nombre con el que se registra en `editorjs.js`.
//
// Las listas se sacaron del código de cada paquete (versiones fijadas en
// `package.json`). Si se actualiza uno, conviene volver a buscar sus
// `i18n.t(...)`: lo que falte aquí sale en inglés.

export const messages = {
    ui: {
        blockTunes: {
            toggler: {
                'Click to tune': 'Opciones del bloque',
                'or drag to move': 'o arrastra para moverlo',
            },
        },
        inlineToolbar: {
            converter: {
                'Convert to': 'Convertir en',
            },
        },
        toolbar: {
            toolbox: {
                Add: 'Añadir bloque',
            },
        },
        popover: {
            Filter: 'Buscar',
            'Nothing found': 'Sin resultados',
            'Convert to': 'Convertir en',
        },
    },

    toolNames: {
        Text: 'Párrafo',
        Heading: 'Título',
        List: 'Lista',
        'Unordered List': 'Lista con viñetas',
        'Ordered List': 'Lista numerada',
        Checklist: 'Lista de tareas',
        Quote: 'Cita',
        Delimiter: 'Separador',
        Table: 'Tabla',
        'Raw HTML': 'HTML',
        Warning: 'Aviso',
        Alert: 'Alerta',
        Image: 'Imagen',
        Attachment: 'Adjunto',
        Link: 'Enlace',
        Code: 'Código',
        Marker: 'Resaltar',
        InlineCode: 'Código en línea',
        Bold: 'Negrita',
        Italic: 'Cursiva',
    },

    tools: {
        // Herramientas del propio núcleo. `convertTo` es el botón «Convertir
        // en» de la barra que sale al seleccionar texto.
        convertTo: {
            'Convert to': 'Convertir en',
        },
        link: {
            'Add a link': 'Pega la dirección del enlace',
        },
        stub: {
            'The block can not be displayed correctly.': 'Este bloque no se puede mostrar: su herramienta no está cargada.',
        },

        header: {
            'Heading 3': 'Título 3',
            'Heading 4': 'Título 4',
            'Heading 5': 'Título 5',
            'Heading 6': 'Título 6',
        },
        list: {
            Unordered: 'Con viñetas',
            Ordered: 'Numerada',
            Checklist: 'Con casillas',
            'Start with': 'Empezar en',
            'Counter type': 'Tipo de numeración',
            Numeric: 'Números',
            'Lower Roman': 'Romanos en minúscula',
            'Upper Roman': 'Romanos en mayúscula',
            'Lower Alpha': 'Letras en minúscula',
            'Upper Alpha': 'Letras en mayúscula',
        },
        quote: {
            'Align Left': 'Alinear a la izquierda',
            'Align Center': 'Centrar',
        },
        table: {
            'Add column to left': 'Añadir columna a la izquierda',
            'Add column to right': 'Añadir columna a la derecha',
            'Delete column': 'Borrar columna',
            'Add row above': 'Añadir fila encima',
            'Add row below': 'Añadir fila debajo',
            'Delete row': 'Borrar fila',
            Heading: 'Cabecera',
            'With headings': 'Con cabecera',
            'Without headings': 'Sin cabecera',
            Stretch: 'A todo el ancho',
            Collapse: 'Ancho normal',
        },
        embed: {
            'Enter a caption': 'Pie del vídeo',
        },
        image: {
            Caption: 'Pie de foto',
            'Select an Image': 'Elegir imagen',
            'With border': 'Con borde',
            'Stretch image': 'A todo el ancho',
            'With background': 'Con fondo',
            'With caption': 'Con pie de foto',
            // El motivo lo enseña el cargador propio (ver `createUploader()`).
            'Couldn’t upload image. Please try another.': 'No se ha subido la imagen.',
        },
        attaches: {
            'File title': 'Título del fichero',
        },
        linkTool: {
            Link: 'Pega la dirección y pulsa Intro',
            "Couldn't fetch the link data": 'No se han podido leer los datos del enlace',
            "Couldn't get this link data, try the other one": 'No se han podido leer los datos de este enlace; prueba con otro',
            'Wrong response format from the server': 'El servidor ha respondido algo que no se entiende',
        },
        alert: {
            Primary: 'Principal',
            Secondary: 'Secundaria',
            Info: 'Información',
            Success: 'Correcto',
            Warning: 'Advertencia',
            Danger: 'Peligro',
            Light: 'Clara',
            Dark: 'Oscura',
            Left: 'Izquierda',
            Center: 'Centro',
            Right: 'Derecha',
        },
    },

    blockTunes: {
        delete: {
            Delete: 'Borrar',
            'Click to delete': 'Pulsa otra vez para borrar',
        },
        moveUp: {
            'Move up': 'Subir',
        },
        moveDown: {
            'Move down': 'Bajar',
        },
        textVariant: {
            'Call-out': 'Destacado',
            Citation: 'Cita',
            Details: 'Detalles',
        },
    },
};

// Textos que el bloque de código (`@calumk/editorjs-codecup`) escribe
// directamente en el HTML, sin pasar por `api.i18n`. Se cambian en el DOM
// (ver `translateHardcoded()` en `editorjs.js`).
export const hardcoded = {
    text: {
        'Hide Numbers': 'Ocultar números de línea',
        'Show Numbers': 'Mostrar números de línea',
        'Select Language': 'Lenguaje',
        'Copied!': 'Copiado',
        'Plain Text': 'Texto',
    },
    placeholder: {
        'Enter language..': 'Lenguaje…',
    },
};
