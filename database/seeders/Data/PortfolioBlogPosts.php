<?php

declare(strict_types=1);

namespace Database\Seeders\Data;

use Illuminate\Support\Str;

/**
 * Los 20 posts de ejemplo del blog del portfolio (`PortfolioBlogSeeder`).
 *
 * Cada post lleva su metadatos (categorías, etiquetas, tecnologías, estado…) y
 * sus páginas en bloques de Editor.js. Las imágenes se piden por posición
 * (`img(n)`) y el seeder las resuelve contra los ficheros que haya en la base:
 * sin ficheros, el bloque se omite.
 *
 * Los textos sólo usan el HTML que el editor deja guardar (negrita, cursiva,
 * enlace y código en línea).
 */
final class PortfolioBlogPosts
{
    /**
     * @return list<array<string, mixed>>
     */
    public static function all(): array
    {
        return [
            [
                'title' => 'Cómo monté mi estación meteorológica con una Raspberry Pi',
                'excerpt' => 'Del primer sensor BME280 sobre la mesa a una estación completa que envía temperatura, humedad, presión, viento y rayos a mi propia API.',
                'categories' => ['iot', 'hardware'], 'main' => 'iot',
                'tags' => ['iot', 'sensors', 'raspberry', 'weather station', 'wind'],
                'techs' => ['Python', 'PostgreSQL', 'Laravel'],
                'state' => 'published', 'featured' => true, 'days_ago' => 301,
                'collaborators' => [5], 'related' => [2, 7, 14],
                'metadata' => ['github' => 'https://github.com/raupulus/weather-station', 'gitlab' => 'https://gitlab.com/raupulus/weather-station'],
                'gallery' => 'Montaje de la estación meteorológica',
                'pages' => [
                    ['title' => 'El hardware', 'blocks' => [
                        self::h('Por qué una estación propia'),
                        self::p('Hace tiempo que quería tener datos del clima de <b>mi calle</b> y no los de una estación a varios kilómetros. Los servicios públicos son muy buenos, pero no te dicen qué pasa en tu terraza: la ráfaga que tumba las macetas, el sol de las cinco de la tarde o la humedad que se queda atrapada junto a la pared.'),
                        self::p('La idea era sencilla: una Raspberry Pi leyendo sensores, un pequeño programa en Python que los agrupa cada minuto y una API que los guarda para poder consultarlos desde cualquier sitio. Lo que no era sencillo, como casi siempre, eran los detalles.'),
                        self::img(0, 'Primer montaje sobre la mesa, todavía sin carcasa'),
                        self::h('Sensores que usé', 4),
                        self::ul([
                            'BME280 para temperatura, humedad y presión atmosférica.',
                            'BH1750 para la luz ambiental, en lux.',
                            'VEML6075 para radiación ultravioleta, con UVA y UVB por separado.',
                            ['Anemómetro y veleta', ['El anemómetro cierra un contacto con un imán en cada vuelta.', 'La veleta usa un encoder para saber la dirección.']],
                            'CJMCU-3935 como detector de rayos.',
                        ]),
                        self::alert('info', 'Si vas a poner sensores a la intemperie, protege los de temperatura y humedad con una pantalla de radiación: al sol dan lecturas varios grados por encima de la real.'),
                    ]],
                    ['title' => 'El software', 'blocks' => [
                        self::h('Leer, agrupar y enviar'),
                        self::p('Cada sensor tiene su pequeño módulo y un bucle principal los consulta con una frecuencia distinta: la temperatura cambia despacio y basta con leerla cada minuto, mientras que el viento necesita muestras mucho más seguidas para no perder las rachas.'),
                        self::code("def leer_sensores():\n    datos = {\n        'temperatura': bme.temperature,\n        'humedad': bme.humidity,\n        'presion': bme.pressure,\n        'viento_kmh': anemometro.velocidad_media(),\n    }\n    return datos", 'python'),
                        self::p('Los datos se envían a la API con un token propio del dispositivo. Si no hay conexión, el programa los guarda en un fichero local y los reenvía cuando vuelve la red, para no tener huecos en las gráficas.'),
                        self::check([['Lectura de todos los sensores', true], ['Envío con reintentos', true], ['Carcasa impresa en 3D', true], ['Alimentación con panel solar', false]]),
                        self::quote('Un sensor sin calibrar da números, no datos.', 'Lección aprendida tras tres semanas de gráficas sospechosas'),
                    ]],
                ],
            ],
            [
                'title' => 'Laravel 13 en producción: lo que cambió y cómo migré mi API',
                'excerpt' => 'Notas de una actualización real: dependencias que se quejan, pruebas que se rompen por motivos tontos y las mejoras que sí notas desde el primer día.',
                'categories' => ['backend', 'Desarrollo Web'], 'main' => 'backend',
                'tags' => ['api', 'software'],
                'techs' => ['Laravel', 'PHP', 'PostgreSQL'],
                'state' => 'published', 'featured' => true, 'days_ago' => 280,
                'collaborators' => [], 'related' => [10, 9, 17],
                'metadata' => ['web' => 'https://laravel.com'],
                'pages' => [
                    ['title' => 'Antes de actualizar', 'blocks' => [
                        self::h('Un inventario antes que nada'),
                        self::p('Actualizar un framework no es cambiar un número en <code>composer.json</code>. Lo primero que hice fue anotar qué paquetes dependen de la versión mayor y cuáles ya publican una versión compatible. Esa lista decide si la migración dura una tarde o una semana.'),
                        self::p('Mi regla es no tocar nada sin una batería de pruebas en verde. Si los tests no pasan antes de actualizar, no sabré si lo que falla después es culpa del framework o mía. Por eso empecé arreglando dos pruebas intermitentes que llevaban meses ignoradas.'),
                        self::ol(['Rama nueva y pruebas en verde.', 'Subir la versión de PHP si hace falta.', 'Actualizar el framework y luego cada paquete, uno a uno.', 'Revisar la guía oficial de cambios incompatibles.', 'Desplegar primero en un entorno de pruebas.']),
                        self::alert('warning', 'Haz copia de la base de datos antes de ejecutar migraciones nuevas, aunque creas que son inofensivas.'),
                    ]],
                    ['title' => 'Lo que me encontré', 'blocks' => [
                        self::h('Cambios que sí notas'),
                        self::p('Lo más visible fue el endurecimiento de los tipos: cosas que antes pasaban silenciosamente, como comparar un entero con una cadena en una colección, ahora fallan con un mensaje claro. Es molesto el primer día y fantástico el segundo, porque cada error apunta a un fallo real.'),
                        self::table([['Área', 'Antes', 'Ahora'], ['Configuración', 'Dispersa en varios ficheros', 'Más coherente y con menos valores por defecto ocultos'], ['Pruebas', 'Mucho código repetido', 'Ayudas nuevas para aserciones frecuentes'], ['Colas', 'Reintentos manuales', 'Políticas más explícitas']]),
                        self::p('Mi consejo es leer los cambios incompatibles con calma y buscar en el proyecto cada término afectado con un simple <code>grep</code>. Casi todo se resuelve en minutos cuando sabes exactamente qué buscar.'),
                        self::link('https://laravel.com/docs/upgrade', 'Guía de actualización de Laravel', 'La lista oficial de cambios incompatibles entre versiones mayores.'),
                    ]],
                ],
            ],
            [
                'title' => 'Raspberry Pi Pico y MicroPython: primeros pasos sin miedo',
                'excerpt' => 'Todo lo que necesitas para encender un LED, leer un sensor y subir tu primer programa a una Pico, sin instalar medio sistema operativo.',
                'categories' => ['microcontroladores', 'hardware'], 'main' => 'microcontroladores',
                'tags' => ['raspberry pi pico', 'maker', 'sensors'],
                'techs' => ['MicroPython', 'Python'],
                'state' => 'published', 'featured' => false, 'days_ago' => 262,
                'collaborators' => [], 'related' => [16, 8, 14],
                'metadata' => ['github' => 'https://github.com/raupulus/rpi-pico-project-ink-paper-gadget'],
                'pages' => [
                    ['title' => 'Primeros pasos', 'blocks' => [
                        self::h('Qué es una Pico y por qué me gusta'),
                        self::p('La Raspberry Pi Pico es un microcontrolador pequeño, barato y muy bien documentado. A diferencia de una Raspberry Pi normal no ejecuta un sistema operativo: arranca, lanza tu programa y se dedica a eso. Esa simplicidad es justo lo que la hace perfecta para aprender.'),
                        self::p('Con <b>MicroPython</b> puedes escribir en Python y ver el resultado al instante, sin compilar ni flashear nada complicado. Se conecta por USB, aparece como una unidad y un editor sencillo como Thonny se encarga del resto.'),
                        self::h('Instalar MicroPython', 4),
                        self::ol(['Descarga el fichero <code>.uf2</code> desde la web oficial.', 'Mantén pulsado el botón BOOTSEL y conecta la placa al USB.', 'Copia el fichero a la unidad que aparece.', 'La placa se reinicia sola y ya está lista.']),
                        self::img(1, 'La Pico sobre una protoboard con un sensor conectado'),
                    ]],
                    ['title' => 'Tu primer programa', 'blocks' => [
                        self::h('Parpadear no es poca cosa'),
                        self::p('El «hola mundo» de los microcontroladores es hacer parpadear un LED, y no es casualidad: comprueba que la placa funciona, que el entorno está bien configurado y que sabes subir código. Si llegas aquí, todo lo demás es ir añadiendo piezas.'),
                        self::code("from machine import Pin\nimport time\n\nled = Pin(25, Pin.OUT)\n\nwhile True:\n    led.toggle()\n    time.sleep(0.5)", 'python'),
                        self::p('A partir de ahí puedes leer un sensor por I2C, mostrar datos en una pantalla o conectarte a una red Wi-Fi con la variante W de la placa. Cada paso añade solo unas pocas líneas.'),
                        self::alert('success', 'Guarda tu programa como main.py y se ejecutará solo cada vez que alimentes la placa.'),
                    ]],
                ],
            ],
            [
                'title' => 'Editor.js en Laravel: bloques propios y almacenamiento en JSON',
                'excerpt' => 'Cómo guardar el contenido de un editor por bloques, generar el HTML que se sirve y conservar el JSON original para poder volver a editar.',
                'categories' => ['Desarrollo Web', 'backend', 'frontend'], 'main' => 'Desarrollo Web',
                'tags' => ['software', 'api'],
                'techs' => ['Laravel', 'Javascript', 'PHP'],
                'state' => 'published', 'featured' => true, 'days_ago' => 244,
                'collaborators' => [3], 'related' => [12, 15, 10],
                'metadata' => ['github' => 'https://github.com/codex-team/editor.js', 'web' => 'https://editorjs.io'],
                'pages' => [
                    ['title' => 'Por qué bloques', 'blocks' => [
                        self::h('El problema del HTML libre'),
                        self::p('Los editores clásicos guardan HTML, y el HTML es un formato fantástico para mostrar y bastante malo para editar. Cada vez que alguien pega algo desde un documento llegan estilos, etiquetas vacías y estructuras que no esperabas, y a los pocos meses el contenido es imposible de mantener.'),
                        self::p('Un editor por <b>bloques</b> como Editor.js guarda datos estructurados: un título es un título, una lista es una lista, una imagen es un fichero con su pie. El diseño queda separado del contenido y puedes pintarlo de la forma que quieras, hoy en una web y mañana en una aplicación.'),
                        self::quote('Guarda la estructura, genera el aspecto.', 'Principio que repito cada vez que toco un editor'),
                        self::h('Qué se guarda', 4),
                        self::ul(['El JSON de los bloques, como fuente de verdad.', 'El HTML ya generado, para servirlo rápido.', 'Una copia en Markdown, útil para exportar.', 'El historial de versiones de cada página.']),
                    ]],
                    ['title' => 'Un ejemplo de bloque', 'blocks' => [
                        self::h('Cómo es un bloque por dentro'),
                        self::p('Cada bloque tiene un identificador, un tipo y unos datos. Lo único que cambia entre un párrafo y una imagen es lo que hay dentro de <code>data</code>, y esa uniformidad es lo que hace sencillo recorrerlos y convertirlos.'),
                        self::code("{\n  \"type\": \"header\",\n  \"data\": {\n    \"text\": \"Hola mundo\",\n    \"level\": 3\n  }\n}", 'json'),
                        self::p('En el servidor recorro la lista, valido que cada bloque traiga lo imprescindible y paso su contenido por un limpiador de HTML. Así el editor solo puede guardar etiquetas que yo he permitido, aunque alguien manipule la petición.'),
                        self::alert('danger', 'Nunca confíes en el JSON que llega del navegador: valida y limpia siempre en el servidor.'),
                        self::delimiter(),
                        self::link('https://editorjs.io', 'Editor.js', 'Editor por bloques de código abierto con salida en JSON limpio.'),
                    ]],
                ],
            ],
            [
                'title' => 'Scripts de Bash que me ahorran horas cada semana',
                'excerpt' => 'Una colección de pequeños scripts de terminal para copias de seguridad, limpieza y tareas repetitivas, con los trucos para que no se rompan a la primera.',
                'categories' => ['bash', 'scripts', 'linux'], 'main' => 'bash',
                'tags' => ['script', 'software', 'linux'],
                'techs' => ['Bash', 'GNU/Linux'],
                'state' => 'published', 'featured' => false, 'days_ago' => 226,
                'collaborators' => [], 'related' => [6, 13, 15],
                'metadata' => ['gitlab' => 'https://gitlab.com/raupulus/scripts'],
                'pages' => [
                    ['title' => 'Scripts útiles', 'blocks' => [
                        self::h('Automatizar lo aburrido'),
                        self::p('Si haces algo en la terminal más de tres veces, escríbelo en un script. No hace falta que sea bonito ni que lo entienda nadie más: basta con que te ahorre repetir la secuencia de comandos y los errores tontos que vienen con ella.'),
                        self::p('El primer consejo es empezar siempre con una cabecera segura. Con tres opciones evitas la mayoría de desastres, porque el script se detiene en el primer fallo en vez de seguir como si nada con variables vacías.'),
                        self::code("#!/usr/bin/env bash\nset -euo pipefail\n\norigen=\"\$HOME/proyectos\"\ndestino=\"/mnt/copias/\$(date +%F)\"\n\nmkdir -p \"\$destino\"\nrsync -a --delete \"\$origen/\" \"\$destino/\"\necho \"Copia terminada en \$destino\"", 'bash'),
                        self::h('Los que más uso', 4),
                        self::ul(['Copia incremental de proyectos con <code>rsync</code>.', 'Limpieza de ramas de git ya fusionadas.', 'Renombrado masivo de fotos por fecha.', 'Informe del espacio libre de cada disco por correo.']),
                        self::alert('warning', 'Prueba cualquier script que borre ficheros con --dry-run o con un echo delante antes de ejecutarlo de verdad.'),
                        self::p('Con el tiempo, mi carpeta de scripts se ha convertido en una pequeña caja de herramientas versionada con git. Cuando cambio de equipo, la clono y estoy como en casa en cinco minutos.'),
                    ]],
                ],
            ],
            [
                'title' => 'Debian en 2026: por qué sigo fiel a la rama estable',
                'excerpt' => 'Servidores, portátiles y placas que llevan años sin sobresaltos. Un repaso a lo que me gusta de Debian y a lo que no tanto.',
                'categories' => ['debian', 'linux'], 'main' => 'debian',
                'tags' => ['debian', 'linux', 'apt', 'gnu'],
                'techs' => ['GNU/Linux', 'Bash'],
                'state' => 'published', 'featured' => false, 'days_ago' => 209,
                'collaborators' => [], 'related' => [5, 13, 18],
                'metadata' => ['web' => 'https://www.debian.org'],
                'pages' => [
                    ['title' => 'Estabilidad ante todo', 'blocks' => [
                        self::h('Lo aburrido es una virtud'),
                        self::p('Llevo años usando Debian en casi todo lo que no es mi ordenador de trabajo: servidores, la Raspberry Pi de la estación meteorológica, un pequeño NAS. La razón es poco glamurosa: <b>no da sorpresas</b>. Actualizo, reinicio y las cosas siguen funcionando.'),
                        self::p('La rama estable congela las versiones y solo recibe correcciones de seguridad. Eso significa que el software no será el más nuevo, pero sí el más probado. Para un servidor que debe estar encendido durante meses, es exactamente lo que busco.'),
                        self::h('Lo mejor y lo peor', 4),
                        self::table([['Me gusta', 'Me gusta menos'], ['Actualizaciones previsibles', 'Versiones algo antiguas'], ['Documentación excelente', 'Instalador algo austero'], ['Enorme repositorio de paquetes', 'Menos pulido en el escritorio']], false),
                        self::quote('Si funciona, no lo toques; si lo tocas, hazlo en un entorno de pruebas.', 'Máxima de administrador'),
                        self::p('Cuando necesito algo más reciente, recurro a repositorios de retroportes o compilo en un contenedor, pero casi nunca hace falta. La estabilidad compensa de sobra.'),
                    ]],
                ],
            ],
            [
                'title' => 'Monitorizar placas solares con Python y una Raspberry Pi',
                'excerpt' => 'Leer un controlador de carga por puerto serie, guardar la producción de cada día y ver de un vistazo cuánto te está dando el sol.',
                'categories' => ['energía', 'iot'], 'main' => 'energía',
                'tags' => ['iot', 'api', 'software', 'sensors'],
                'techs' => ['Python', 'PostgreSQL'],
                'state' => 'published', 'featured' => false, 'days_ago' => 192,
                'collaborators' => [], 'related' => [1, 17, 20],
                'metadata' => ['github' => 'https://github.com/raupulus/solar-monitor'],
                'gallery' => 'Instalación solar y cuadro eléctrico',
                'pages' => [
                    ['title' => 'Leer el controlador', 'blocks' => [
                        self::h('Datos que valen la pena'),
                        self::p('Una instalación solar pequeña produce muchos datos que nadie mira: voltaje del panel, corriente de carga, estado de la batería, temperatura del regulador. Mirarlos de forma continua permite detectar suciedad en los paneles, baterías que envejecen o un cable mal apretado antes de que sea un problema.'),
                        self::p('Muchos controladores de carga exponen un puerto serie con un protocolo bien documentado. Con un adaptador USB y la librería adecuada, leer los registros es cuestión de pocas líneas. Lo difícil es decidir con qué frecuencia guardar y cuánto histórico conservar.'),
                        self::img(2, 'Controlador de carga junto al cuadro de la instalación'),
                        self::code("lectura = controlador.leer()\nregistro = {\n    'panel_v': lectura.panel_voltage,\n    'bateria_v': lectura.battery_voltage,\n    'carga_a': lectura.charge_current,\n}\nenviar_a_api(registro)", 'python'),
                        self::check([['Lectura cada minuto', true], ['Resumen diario en la base de datos', true], ['Alerta si la batería baja del 20 %', false]]),
                        self::alert('warning', 'Trabaja siempre con la instalación desconectada al tocar cableado. Los datos se miran con calma; la electricidad, con respeto.'),
                    ]],
                ],
            ],
            [
                'title' => 'Imprimir en 3D una caja para mi pantalla e-paper',
                'excerpt' => 'Del boceto en papel al modelo imprimible: medidas, tolerancias y los errores que cometí hasta que la tapa encajó a la primera.',
                'categories' => ['gadget', 'hardware'], 'main' => 'gadget',
                'tags' => ['maker', 'raspberry pi pico', 'software'],
                'techs' => ['MicroPython'],
                'state' => 'published', 'featured' => false, 'days_ago' => 176,
                'collaborators' => [], 'related' => [3, 14, 16],
                'metadata' => ['web' => 'https://www.thingiverse.com'],
                'gallery' => 'Caja impresa en 3D',
                'pages' => [
                    ['title' => 'Diseño e impresión', 'blocks' => [
                        self::h('Medir dos veces, imprimir una'),
                        self::p('Una pantalla e-paper es delicada y casi todas traen sus medidas con la tolerancia del fabricante, que no siempre coincide con la realidad. Lo primero que hice fue medir la pantalla con un calibre en varios puntos y quedarme con el valor más grande, porque siempre es más fácil rellenar que limar.'),
                        self::p('Después modelé la caja por piezas: un marco frontal, un cuerpo con hueco para la placa y una tapa trasera con un par de pestañas de encaje. Imprimí primero solo el marco, una prueba rápida de veinte minutos que me ahorró un par de horas de impresión inútil.'),
                        self::img(3, 'Prueba de ajuste del marco frontal'),
                        self::h('Ajustes que funcionaron', 4),
                        self::ul(['Altura de capa de 0,2 mm, suficiente para una pieza de uso.', 'Holgura de 0,25 mm en los encajes.', ['Relleno', ['15 % en el cuerpo, que no soporta carga.', '40 % en las pestañas de la tapa.']], 'Una orientación de impresión que evite soportes en el marco.']),
                        self::quote('Una tolerancia mal puesta convierte una pieza en un pisapapeles.', 'Mi tercera tapa, ya descartada'),
                        self::link('https://www.thingiverse.com', 'Thingiverse', 'Repositorio de modelos 3D donde se pueden compartir y descargar diseños.'),
                    ]],
                ],
            ],
            [
                'title' => 'PostgreSQL: índices parciales y JSONB en casos reales',
                'excerpt' => 'Dos herramientas de PostgreSQL que resolvieron problemas de rendimiento y de modelado en mi API sin añadir una sola dependencia nueva.',
                'categories' => ['backend', 'Desarrollo Web'], 'main' => 'backend',
                'tags' => ['api', 'software'],
                'techs' => ['PostgreSQL', 'Laravel', 'PlSQL'],
                'state' => 'published', 'featured' => false, 'days_ago' => 160,
                'collaborators' => [], 'related' => [2, 10, 17],
                'metadata' => ['web' => 'https://www.postgresql.org/docs/'],
                'pages' => [
                    ['title' => 'Índices parciales', 'blocks' => [
                        self::h('Indexar solo lo que consultas'),
                        self::p('Un índice ocupa espacio y ralentiza las escrituras. Si el noventa por ciento de tus consultas miran solo las filas que <b>no</b> están borradas, indexar también las borradas es malgastar disco. Un índice parcial resuelve justo eso: se construye únicamente sobre las filas que cumplen una condición.'),
                        self::code("CREATE UNIQUE INDEX contents_slug_activo_unique\n    ON contents (platform_id, slug)\n    WHERE deleted_at IS NULL;", 'sql'),
                        self::p('Además de más pequeño, este índice expresa una regla de negocio: el slug debe ser único mientras el contenido exista, pero se puede reutilizar cuando está en la papelera. La base de datos lo garantiza, no el código.'),
                        self::h('JSONB sin pasarse', 4),
                        self::p('Los campos JSONB son estupendos para datos con forma variable, como la configuración de un dispositivo o los metadatos de una tarjeta de enlace. Mi regla es sencilla: si vas a filtrar, ordenar o unir por un dato, merece su propia columna; si solo lo vas a leer entero, JSONB está perfecto.'),
                        self::table([['Necesidad', 'Mejor opción'], ['Filtrar por un valor frecuente', 'Columna normal con índice'], ['Guardar ajustes variables', 'JSONB'], ['Buscar dentro de un JSON', 'Índice GIN sobre la columna JSONB']]),
                        self::alert('info', 'Mide con EXPLAIN ANALYZE antes y después. Un índice que el planificador no usa solo estorba.'),
                    ]],
                ],
            ],
            [
                'title' => 'Diseñar una API REST versionada con Laravel y Sanctum',
                'excerpt' => 'Rutas por versión, respuestas con un formato único, tokens por dispositivo con permisos acotados y limitación de peticiones sin sorpresas.',
                'categories' => ['backend', 'Desarrollo Web', 'iot'], 'main' => 'backend',
                'tags' => ['api', 'software', 'iot'],
                'techs' => ['Laravel', 'PHP', 'PostgreSQL'],
                'state' => 'published', 'featured' => true, 'days_ago' => 144,
                'collaborators' => [5, 3], 'related' => [2, 9, 17],
                'metadata' => ['github' => 'https://github.com/raupulus/api-raupulus', 'gitlab' => 'https://gitlab.com/raupulus/api-raupulus'],
                'pages' => [
                    ['title' => 'Estructura y versiones', 'blocks' => [
                        self::h('Versionar desde el primer día'),
                        self::p('Cuando tu API la consumen dispositivos que no se actualizan solos, cambiar un campo de nombre puede dejar sin datos a una placa que está en un tejado. Por eso el prefijo de versión va en la ruta desde el principio, aunque de momento solo exista la primera: poder añadir una segunda sin romper la primera es una tranquilidad enorme.'),
                        self::p('Mantengo una estructura previsible: un controlador por recurso, un recurso de salida que decide qué campos se ven y un formulario de validación para cada entrada. Así, quien llegue al proyecto sabe dónde mirar sin leer todo el código.'),
                        self::code("GET  /api/v2/platforms/portfolio/contents\nGET  /api/v2/platforms/portfolio/contents/{slug}\nPOST /api/v2/weather-station/record", 'text'),
                        self::h('Respuestas con un formato único', 4),
                        self::p('Todas las respuestas comparten la misma envoltura con tres claves: si fue bien, un mensaje y los datos. Los clientes escriben un solo manejador de errores y los dispositivos más sencillos solo tienen que mirar un campo.'),
                    ]],
                    ['title' => 'Tokens y límites', 'blocks' => [
                        self::h('Un token por dispositivo'),
                        self::p('En lugar de compartir una clave global, cada dispositivo tiene su propio token con <b>habilidades</b> limitadas: la estación meteorológica solo puede enviar lecturas, nunca borrar datos ni leer los de otro módulo. Si un token se filtra, se revoca uno y el resto sigue funcionando.'),
                        self::ul(['Token único por dispositivo y por módulo.', 'Habilidades mínimas para lo que hace.', 'Fecha de último uso, para detectar dispositivos olvidados.', 'Revocación inmediata desde el panel.']),
                        self::p('La limitación de peticiones completa el cuadro. Las rutas públicas de lectura admiten muchas consultas por minuto, mientras que las de escritura y los formularios tienen un límite más estricto por dirección IP.'),
                        self::alert('success', 'Documenta el contrato con una herramienta que se genere desde el propio código: la documentación que se escribe a mano acaba desactualizada.'),
                        self::quote('Una API es una promesa; versionarla es la forma de poder cambiar de opinión.'),
                    ]],
                ],
            ],
            [
                'title' => 'Minería doméstica con una Raspberry Pi: ¿merece la pena?',
                'excerpt' => 'Cuentas claras sobre consumo, rendimiento y rentabilidad de minar con hardware pequeño, y por qué lo hago de todas formas.',
                'categories' => ['crypto', 'hardware'], 'main' => 'crypto',
                'tags' => ['btc', 'miner', 'raspberry'],
                'techs' => ['Python', 'Bash'],
                'state' => 'published', 'featured' => false, 'days_ago' => 128,
                'collaborators' => [], 'related' => [16, 14, 19],
                'metadata' => [],
                'pages' => [
                    ['title' => 'Las cuentas', 'blocks' => [
                        self::h('Hablemos de números'),
                        self::p('Mi respuesta corta a si merece la pena es: <b>no como negocio</b>. Un minero pequeño produce cantidades casi simbólicas y la electricidad cuesta lo que cuesta. Pero tiene un valor que no sale en la hoja de cálculo: es un proyecto estupendo para aprender sobre redes, rendimiento y monitorización con hardware barato.'),
                        self::table([['Concepto', 'Valor de ejemplo'], ['Consumo', '5 W'], ['Velocidad de cálculo', 'Muy baja'], ['Coste de electricidad al mes', 'Menos de un euro'], ['Ingresos esperados', 'Prácticamente nulos']]),
                        self::p('Lo que sí hago es medir. Una pequeña pantalla muestra la velocidad de cada minero, el estado de la conexión con el servidor y las acciones aceptadas. Ver los números en directo es lo que realmente mantiene vivo el proyecto.'),
                        self::alert('warning', 'Las cifras de la tabla son orientativas. Calcula con el precio real de tu electricidad antes de comprar nada.'),
                        self::h('Qué me llevo', 4),
                        self::ul(['Una excusa perfecta para monitorizar hardware.', 'Experiencia ajustando consumo y temperatura.', 'La certeza de que no voy a dejar mi trabajo.']),
                    ]],
                ],
            ],
            [
                'title' => 'Vue 3 y Tailwind 4: arquitectura de un frontend para clientes de una API',
                'excerpt' => 'Cómo organizo componentes, llamadas a la API y estilos para que un cliente web consuma datos reales sin convertirse en un laberinto.',
                'categories' => ['frontend', 'Desarrollo Web'], 'main' => 'frontend',
                'tags' => ['software', 'api'],
                'techs' => ['VueJs', 'Tailwind', 'Javascript', 'HTML', 'CSS'],
                'state' => 'published', 'featured' => false, 'days_ago' => 112,
                'collaborators' => [3], 'related' => [4, 18, 10],
                'metadata' => ['github' => 'https://github.com/raupulus/portfolio-client'],
                'pages' => [
                    ['title' => 'Organizar el cliente', 'blocks' => [
                        self::h('Capas pequeñas y claras'),
                        self::p('Un cliente que consume una API suele crecer sin orden: una llamada aquí, otra allá, y a los dos meses nadie sabe de dónde sale cada dato. Mi solución es separar tres capas muy simples y no mezclarlas nunca.'),
                        self::ol(['Un módulo de acceso a la API, que sabe construir peticiones y poco más.', 'Funciones de dominio, que traducen la respuesta a lo que necesita la interfaz.', 'Componentes, que solo se ocupan de mostrar y de recoger acciones.']),
                        self::p('Con esto, cambiar la forma de una respuesta toca un único fichero, y probar un componente no exige una API funcionando: basta con pasarle los datos de ejemplo.'),
                        self::code("export async function listarEntradas({ pagina = 1, etiqueta } = {}) {\n  const respuesta = await api.get('/platforms/portfolio/contents', {\n    params: { page: pagina, tag: etiqueta },\n  })\n  return respuesta.data\n}", 'javascript'),
                        self::h('Estilos con Tailwind', 4),
                        self::p('Tailwind encaja bien cuando los componentes son pequeños y reutilizables: las clases viven junto al marcado y no hay hojas de estilo olvidadas. El truco es extraer un componente en cuanto repites tres veces la misma combinación de clases.'),
                        self::alert('info', 'Los datos de prueba de la API sirven para maquetar estados raros: listas largas, títulos enormes, entradas sin imagen y contenidos sin publicar.'),
                    ]],
                ],
            ],
            [
                'title' => 'macOS para desarrolladores: mi configuración de partida',
                'excerpt' => 'Las aplicaciones, ajustes del sistema y atajos que instalo en cualquier Mac nuevo antes de escribir la primera línea de código.',
                'categories' => ['macos', 'apple', 'developer'], 'main' => 'macos',
                'tags' => ['macos', 'apple', 'software'],
                'techs' => ['Macos', 'Swift', 'Bash'],
                'state' => 'published', 'featured' => false, 'days_ago' => 97,
                'collaborators' => [], 'related' => [5, 15, 19],
                'metadata' => ['web' => 'https://brew.sh'],
                'pages' => [
                    ['title' => 'Un Mac listo para programar', 'blocks' => [
                        self::h('Lo primero que instalo'),
                        self::p('Un Mac recién sacado de la caja es un excelente ordenador, pero no un buen entorno de desarrollo. Mi primer paso siempre es el mismo: las herramientas de línea de comandos de Apple y un gestor de paquetes, porque casi todo lo demás se instala con un solo comando.'),
                        self::code("xcode-select --install\n/bin/bash -c \"\$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/HEAD/install.sh)\"\nbrew install git php composer node pnpm", 'bash'),
                        self::p('Después ajusto lo que me estorba: repetición de teclas más rápida, extensiones de fichero siempre visibles, el Finder mostrando la ruta completa y las esquinas activas desactivadas. Son detalles pequeños que, sumados, evitan mil fricciones al día.'),
                        self::check([['Herramientas de línea de comandos', true], ['Gestor de paquetes', true], ['Terminal con un buen tema', true], ['Copia de seguridad automática', true], ['Cifrado del disco', true]]),
                        self::h('Atajos que no perdono', 4),
                        self::ul(['Cmd + Espacio para lanzar cualquier cosa.', 'Cmd + ` para cambiar entre ventanas de la misma aplicación.', 'Ctrl + flechas para moverme entre escritorios.']),
                        self::quote('El mejor entorno de desarrollo es el que no notas.'),
                    ]],
                ],
            ],
            [
                'title' => 'Contador de pulsaciones: construir un KeyCounter propio',
                'excerpt' => 'Medir cuántas teclas pulsas al día no es solo una curiosidad: es un buen ejercicio de captura de eventos, agregación y visualización de datos.',
                'categories' => ['iot', 'gadget', 'developer'], 'main' => 'gadget',
                'tags' => ['keycounter', 'api', 'iot', 'software'],
                'techs' => ['Python', 'Laravel', 'PostgreSQL'],
                'state' => 'published', 'featured' => false, 'days_ago' => 83,
                'collaborators' => [], 'related' => [3, 8, 17],
                'metadata' => ['github' => 'https://github.com/raupulus/keycounter', 'youtube_video_id' => 'Tvh2LaYt-_s'],
                'pages' => [
                    ['title' => 'Capturar y guardar', 'blocks' => [
                        self::h('Qué y cómo contar'),
                        self::p('La idea es muy simple: cada vez que se pulsa una tecla se suma uno a un contador. La parte interesante viene después, al decidir <b>qué</b> guardas. Yo solo almaceno totales por franjas de tiempo y nunca qué teclas se pulsan, así que el sistema no puede reconstruir lo que escribo y no hay nada sensible que filtrar.'),
                        self::p('Un pequeño programa en cada equipo cuenta pulsaciones y las envía a la API cada pocos minutos. El servidor las agrupa por día, por equipo y por hora, lo que permite comparar una jornada de programación con una de reuniones.'),
                        self::embed('Tvh2LaYt-_s', 'Presentación del proyecto KeyCounter Display'),
                        self::h('Qué he aprendido', 4),
                        self::ul(['Los picos de pulsaciones coinciden con las horas de más concentración.', 'Los fines de semana bajan, pero no tanto como pensaba.', 'Medir cambia el comportamiento: ahora hago más pausas.']),
                        self::alert('info', 'Si cuentas pulsaciones en un equipo compartido, avisa a quien lo use y no guardes nunca el contenido de lo escrito.'),
                    ]],
                ],
            ],
            [
                'title' => 'Cómo documento mis proyectos para no olvidarlos',
                'excerpt' => 'Un README honesto, un fichero por módulo y fechas al pie: el sistema mínimo que me permite retomar un proyecto después de seis meses.',
                'categories' => ['developer', 'tool'], 'main' => 'developer',
                'tags' => ['software', 'project'],
                'techs' => ['Laravel', 'Bash'],
                'state' => 'published', 'featured' => false, 'days_ago' => 70,
                'collaborators' => [3], 'related' => [4, 13, 19],
                'metadata' => [],
                'pages' => [
                    ['title' => 'Documentar sin sufrir', 'blocks' => [
                        self::h('Escribir para tu yo del futuro'),
                        self::p('El lector más habitual de mi documentación soy yo mismo dentro de medio año, con el contexto olvidado y poca paciencia. Esa imagen me ayuda a decidir qué escribir: lo que me haría falta para volver a arrancar sin tener que releer todo el código.'),
                        self::p('Mantengo la documentación pegada al código y la actualizo en el mismo cambio. Una documentación separada se desactualiza en cuanto cambias de tarea, y una desactualizada es peor que ninguna porque da confianza donde no debería.'),
                        self::h('Mi sistema mínimo', 4),
                        self::ol(['Un README con lo que hace el proyecto y cómo arrancarlo.', 'Un fichero por módulo con sus tablas, rutas y decisiones.', 'Un registro de decisiones técnicas con su porqué.', 'La fecha de última revisión al pie de cada documento.']),
                        self::quote('Lo que no se escribe se olvida, y lo que se olvida se vuelve a discutir.', 'Cualquier reunión de equipo, tarde o temprano'),
                        self::alert('success', 'Si dudas entre documentar o no una decisión, documenta el motivo: el qué ya lo cuenta el código.'),
                    ]],
                ],
            ],
            [
                'title' => 'ESP32 frente a Raspberry Pi Pico: cuál elegir para tu proyecto',
                'excerpt' => 'Wi-Fi, Bluetooth, consumo, ecosistema y precio. Una comparación práctica basada en proyectos reales y no en hojas de características.',
                'categories' => ['microcontroladores', 'hardware', 'iot'], 'main' => 'microcontroladores',
                'tags' => ['esp32', 'raspberry pi pico', 'iot', 'maker'],
                'techs' => ['MicroPython', 'C', 'C++'],
                'state' => 'published', 'featured' => false, 'days_ago' => 58,
                'collaborators' => [], 'related' => [3, 11, 14],
                'metadata' => ['web' => 'https://micropython.org'],
                'pages' => [
                    ['title' => 'La comparación', 'blocks' => [
                        self::h('Dos placas, dos filosofías'),
                        self::p('Las dos son baratas, las dos se programan en MicroPython o en C y las dos caben en una protoboard. Aun así, no sirven para lo mismo. La ESP32 nació conectada: trae Wi-Fi y Bluetooth integrados y es la opción natural para cualquier cosa que deba hablar con una red.'),
                        self::p('La Pico, en cambio, destaca por su sencillez, su documentación y sus <b>PIO</b>, unos pequeños procesadores programables que permiten generar señales con una precisión difícil de lograr en otras placas. Es muy agradable para aprender y para controlar periféricos con tiempos exigentes.'),
                        self::table([['Característica', 'ESP32', 'Raspberry Pi Pico'], ['Conectividad', 'Wi-Fi y Bluetooth', 'Solo en la variante W'], ['Consumo en reposo', 'Moderado', 'Bajo'], ['Documentación', 'Amplia y dispersa', 'Muy cuidada'], ['Precio', 'Muy bajo', 'Muy bajo']]),
                        self::h('Mi criterio', 4),
                        self::ul(['Necesito red desde el primer día: ESP32.', 'Quiero aprender o controlar pantallas y LED: Pico.', 'Funciona con batería durante meses: depende del modo de bajo consumo.']),
                        self::alert('info', 'Antes de decidir, comprueba qué librerías existen para el sensor que quieres usar. A veces eso inclina la balanza más que cualquier especificación.'),
                    ]],
                ],
            ],
            [
                'title' => 'WebSockets con Laravel Reverb para dispositivos IoT',
                'excerpt' => 'Cuando preguntar cada pocos segundos a la API no es suficiente: canales en tiempo real para recibir el estado de una impresora o un sensor al instante.',
                'categories' => ['iot', 'backend'], 'main' => 'iot',
                'tags' => ['iot', 'api', 'software'],
                'techs' => ['Laravel', 'PHP', 'Javascript'],
                'state' => 'published', 'featured' => false, 'days_ago' => 44,
                'collaborators' => [5], 'related' => [10, 14, 20],
                'metadata' => ['github' => 'https://github.com/raupulus/api-raupulus'],
                'gallery' => 'Panel con datos en tiempo real',
                'pages' => [
                    ['title' => 'Por qué tiempo real', 'blocks' => [
                        self::h('Del sondeo a los eventos'),
                        self::p('La forma más sencilla de mostrar el estado de un dispositivo es preguntar cada pocos segundos. Funciona, pero desperdicia peticiones cuando no cambia nada y llega tarde cuando cambia algo importante. Con <b>WebSockets</b> el servidor avisa en el momento en que hay novedades.'),
                        self::p('En mi caso, el uso más claro es la cola de impresión: quiero ver cómo avanza una impresión en 3D sin recargar nada, y quiero que un cliente pueda ordenar una pausa y ver la respuesta de la impresora en el mismo instante.'),
                        self::img(0, 'Panel mostrando el estado de una impresora en directo'),
                        self::h('Canales y autorización', 4),
                        self::ul(['Canales públicos para datos que ya son públicos, como el clima.', 'Canales privados para dispositivos, autorizados con su token.', 'Un evento por cambio de estado, no por cada lectura.']),
                    ]],
                    ['title' => 'Un evento de ejemplo', 'blocks' => [
                        self::h('Emitir un evento'),
                        self::p('El servidor define un evento con los datos que interesa difundir y lo lanza donde ocurre el cambio. A partir de ahí, la infraestructura se encarga de entregarlo a todos los clientes suscritos al canal, sin que el código de negocio sepa quién escucha.'),
                        self::code("class EstadoImpresoraCambiado implements ShouldBroadcast\n{\n    public function __construct(public int \$impresoraId, public string \$estado) {}\n\n    public function broadcastOn(): array\n    {\n        return [new PrivateChannel('impresora.'.\$this->impresoraId)];\n    }\n}", 'php'),
                        self::p('En el cliente basta con suscribirse al canal y reaccionar. Eso sí: conviene tratar siempre el WebSocket como un aviso y no como la fuente de verdad, y volver a consultar la API si la conexión se corta.'),
                        self::alert('warning', 'Un canal privado sin autorización bien comprobada es una fuga de datos. Prueba qué pasa cuando un usuario intenta suscribirse al canal de otro.'),
                    ]],
                ],
            ],
            [
                'title' => 'Nuxt, Angular e Ionic: lo que aprendí con tres frontends distintos',
                'excerpt' => 'Tres proyectos, tres frameworks y una conclusión poco épica: importa menos la herramienta que la disciplina con la que organizas el código.',
                'categories' => ['frontend', 'Desarrollo Web'], 'main' => 'frontend',
                'tags' => ['software', 'project'],
                'techs' => ['Nuxt', 'Angular', 'Ionic Vue', 'Ionic Angular', 'Javascript'],
                'state' => 'hidden', 'featured' => false, 'days_ago' => 31,
                'collaborators' => [], 'related' => [12, 4, 10],
                'metadata' => [],
                'pages' => [
                    ['title' => 'Tres frameworks', 'blocks' => [
                        self::h('Sin guerras de frameworks'),
                        self::p('He trabajado con Nuxt, Angular e Ionic en proyectos distintos y cada uno me enseñó algo diferente. Nuxt me dio una experiencia muy fluida para sitios con renderizado en servidor; Angular, una estructura estricta que ayuda mucho en equipos grandes; e Ionic, la posibilidad de reutilizar la web como aplicación móvil.'),
                        self::p('Lo que no cambió fue lo importante: separar los datos de la presentación, tipar las respuestas de la API, gestionar los estados de carga y error con el mismo cuidado que el caso feliz y escribir componentes pequeños que se puedan probar de forma aislada.'),
                        self::ul(['Nuxt: rapidez de desarrollo y buen posicionamiento.', 'Angular: estructura y herramientas integradas.', 'Ionic: una base para web y móvil.']),
                        self::quote('El framework te da herramientas; la arquitectura te la pones tú.'),
                        self::alert('info', 'Esta entrada está publicada pero oculta en la web: sirve para probar qué hace tu cliente con contenidos que la API no devuelve.'),
                    ]],
                ],
            ],
            [
                'title' => 'Inteligencia artificial como compañera de desarrollo: lo que funciona',
                'excerpt' => 'Borrador: cómo uso un asistente de IA para revisar código, escribir pruebas y explorar proyectos, y dónde prefiero seguir pensando yo.',
                'categories' => ['Inteligencia Artificial', 'developer'], 'main' => 'Inteligencia Artificial',
                'tags' => ['software', 'tool'],
                'techs' => ['Python', 'Laravel'],
                'state' => 'draft', 'featured' => false, 'days_ago' => 12,
                'collaborators' => [], 'related' => [15, 13],
                'metadata' => [],
                'pages' => [
                    ['title' => 'Primer borrador', 'blocks' => [
                        self::h('Una ayudante, no una sustituta'),
                        self::p('Llevo meses usando un asistente de inteligencia artificial en mi día a día y mi conclusión provisional es que funciona mejor cuanto más claro tengo yo lo que quiero. Le encargo tareas acotadas, como escribir pruebas para una función concreta o explicarme un módulo que no conozco, y reviso siempre lo que devuelve.'),
                        self::p('Donde menos me convence es en las decisiones de diseño: puede proponer varias opciones razonables, pero no conoce las restricciones de mi proyecto ni lo que me costó aprender cada lección. Esas decisiones las sigo tomando yo, con ayuda pero con criterio propio.'),
                        self::check([['Revisión de código', true], ['Escritura de pruebas', true], ['Diseño de la arquitectura', false], ['Decisiones de seguridad', false]]),
                        self::alert('warning', 'Borrador sin terminar: faltan ejemplos reales y la sección de límites. Esta entrada no debe salir en la web.'),
                    ]],
                ],
            ],
            [
                'title' => 'Cacharreo del mes: una planta inteligente con riego automático',
                'excerpt' => 'Un sensor de humedad del suelo, una pequeña bomba y una API que decide cuándo regar. Entrada programada para publicarse dentro de unos días.',
                'categories' => ['iot', 'gadget', 'microcontroladores'], 'main' => 'iot',
                'tags' => ['iot', 'sensors', 'esp32', 'maker'],
                'techs' => ['MicroPython', 'Python', 'Laravel'],
                'state' => 'scheduled', 'featured' => false, 'days_ago' => 0, 'scheduled_in_days' => 9,
                'collaborators' => [], 'related' => [1, 7, 16],
                'metadata' => ['github' => 'https://github.com/raupulus/smart-plant'],
                'gallery' => 'Planta inteligente',
                'pages' => [
                    ['title' => 'La planta que se riega sola', 'blocks' => [
                        self::h('Un proyecto de fin de semana'),
                        self::p('Mi planta favorita lleva años sobreviviendo por pura suerte, así que decidí echarle una mano. El montaje es sencillo: un sensor capacitivo de humedad en la tierra, una pequeña bomba de agua y una placa que decide cuándo activarla. Lo divertido es cómo se integra todo con la API.'),
                        self::p('La placa envía la humedad cada diez minutos y la API guarda el histórico. Una regla muy simple decide si regar: si la humedad baja de un umbral y no se ha regado en las últimas doce horas, se activa la bomba durante unos segundos. Esa segunda condición evita encharcar la maceta por un fallo del sensor.'),
                        self::img(1, 'Sensor de humedad y bomba junto a la maceta'),
                        self::ol(['Calibrar el sensor en seco y en agua.', 'Definir el umbral de humedad.', 'Limitar la frecuencia máxima de riego.', 'Registrar cada riego en la API.']),
                        self::alert('success', 'Esta entrada está programada: tu cliente debería verla solo cuando llegue su fecha de publicación.'),
                        self::quote('Automatizar sin límites es la forma más rápida de ahogar una planta.', 'Mi primer ficus'),
                    ]],
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function h(string $text, int $level = 3): array
    {
        return self::block('header', ['text' => $text, 'level' => $level]);
    }

    /**
     * @return array<string, mixed>
     */
    public static function p(string $text): array
    {
        return self::block('paragraph', ['text' => $text]);
    }

    /**
     * Lista con viñetas. Un elemento puede ser un texto o `[texto, [hijos…]]`.
     *
     * @param  list<string|array{0: string, 1: list<string>}>  $items
     * @return array<string, mixed>
     */
    public static function ul(array $items): array
    {
        return self::block('list', ['style' => 'unordered', 'meta' => new \stdClass, 'items' => self::listItems($items)]);
    }

    /**
     * Lista numerada.
     *
     * @param  list<string|array{0: string, 1: list<string>}>  $items
     * @return array<string, mixed>
     */
    public static function ol(array $items): array
    {
        return self::block('list', ['style' => 'ordered', 'meta' => ['counterType' => 'numeric'], 'items' => self::listItems($items)]);
    }

    /**
     * @param  list<array{0: string, 1: bool}>  $items
     * @return array<string, mixed>
     */
    public static function check(array $items): array
    {
        return self::block('checklist', ['items' => array_map(
            fn (array $item): array => ['text' => $item[0], 'checked' => $item[1]],
            $items,
        )]);
    }

    /**
     * @return array<string, mixed>
     */
    public static function quote(string $text, string $caption = ''): array
    {
        return self::block('quote', ['text' => $text, 'caption' => $caption, 'alignment' => 'left']);
    }

    /**
     * @return array<string, mixed>
     */
    public static function code(string $code, string $language = 'text'): array
    {
        return self::block('code', ['code' => $code, 'language' => $language, 'showlinenumbers' => true, 'showCopyButton' => true]);
    }

    /**
     * @param  list<list<string>>  $rows
     * @return array<string, mixed>
     */
    public static function table(array $rows, bool $withHeadings = true): array
    {
        return self::block('table', ['withHeadings' => $withHeadings, 'stretched' => false, 'content' => $rows]);
    }

    /**
     * @return array<string, mixed>
     */
    public static function alert(string $type, string $message): array
    {
        return self::block('alert', ['type' => $type, 'align' => 'left', 'message' => $message]);
    }

    /**
     * @return array<string, mixed>
     */
    public static function delimiter(): array
    {
        return self::block('delimiter', new \stdClass);
    }

    /**
     * Tarjeta de enlace con título y descripción ya resueltos (sin salir a la red).
     *
     * @return array<string, mixed>
     */
    public static function link(string $url, string $title, string $description): array
    {
        return self::block('linkTool', ['link' => $url, 'meta' => ['title' => $title, 'description' => $description]]);
    }

    /**
     * Vídeo de YouTube.
     *
     * @return array<string, mixed>
     */
    public static function embed(string $youtubeId, string $caption): array
    {
        return self::block('embed', [
            'service' => 'youtube',
            'source' => 'https://youtu.be/'.$youtubeId,
            'embed' => 'https://www.youtube.com/embed/'.$youtubeId,
            'width' => 580,
            'height' => 320,
            'caption' => $caption,
        ]);
    }

    /**
     * Imagen por posición en el conjunto de ficheros disponibles; el seeder la
     * convierte en un bloque de imagen real (o la omite si no hay ficheros).
     *
     * @return array<string, mixed>
     */
    public static function img(int $position, string $caption): array
    {
        return ['type' => 'image', 'data' => ['_pool' => $position, 'caption' => $caption]];
    }

    /**
     * @param  array<string, mixed>|\stdClass  $data
     * @return array<string, mixed>
     */
    private static function block(string $type, array|\stdClass $data): array
    {
        return ['id' => Str::random(10), 'type' => $type, 'data' => $data, 'tunes' => ['textVariant' => '']];
    }

    /**
     * @param  list<string|array{0: string, 1: list<string>}>  $items
     * @return list<array{content: string, meta: \stdClass, items: list<array<string, mixed>>}>
     */
    private static function listItems(array $items): array
    {
        return array_map(function (string|array $item): array {
            [$text, $children] = is_array($item) ? $item : [$item, []];

            return ['content' => $text, 'meta' => new \stdClass, 'items' => self::listItems($children)];
        }, $items);
    }
}
