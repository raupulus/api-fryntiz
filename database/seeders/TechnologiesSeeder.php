<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Technology;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

/**
 * Class ContentAvailableCategoriesSeeder
 */
class TechnologiesSeeder extends Seeder
{
    private $tableName = 'technologies';

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // 'name', 'slug', 'description', 'color'
        $datas = [
            ['name' => 'Bootstrap', 'slug' => 'bootstrap', 'description' => 'Bootstrap es un framework CSS de código abierto que te permite crear interfaces web receptivas y atractivas con rapidez y facilidad. Se basa en una colección de componentes prediseñados y estilos predefinidos que puedes en cada proyecto.', 'color' => '#6531b2'],
            ['name' => 'Redis', 'slug' => 'redis', 'description' => 'Almacén de datos en memoria ram de código abierto, rápido y versátil. Soporta estructuras de datos como claves-valor, listas, conjuntos, hashes y streams.', 'color' => '#a42022'],
            ['name' => 'Python', 'slug' => 'python', 'description' => 'Lenguaje de programación versátil. Sintaxis simple. Amplia biblioteca estándar. Código abierto. Ideal para principiantes, análisis de datos, IA, desarrollo web y más.', 'color' => '#f7d53e'],
            ['name' => 'PlSQL', 'slug' => 'plsql', 'description' => 'PL/SQL es un lenguaje de programación procedural integrado en las bases de datos Oracle. Permite ampliar las funcionalidades de SQL con sentencias condicionales, bucles, variables y estructuras de control.', 'color' => '#890c4c'],
            ['name' => 'MicroPython', 'slug' => 'micropython', 'description' => 'MicroPython es una versión de Python 3 optimizada para microcontroladores. Permite programar dispositivos de bajo recursos con la sintaxis familiar de Python, simplificando el desarrollo de proyectos electrónicos.', 'color' => '#f6b101'],
            ['name' => 'Bash', 'slug' => 'bash', 'description' => 'Intérprete de comandos para sistemas Unix y GNU/Linux. Permite ejecutar comandos, automatizar tareas y crear scripts.', 'color' => '#292f35'],
            ['name' => 'Javascript', 'slug' => 'javascript', 'description' => 'JavaScript es un lenguaje de programación dinámico y multiparadigma que se ejecuta en el lado del cliente en los navegadores web. Es uno de los lenguajes más populares del mundo.', 'color' => '#f0f000'],
            ['name' => 'C', 'slug' => 'c', 'description' => 'Lenguaje de bajo nivel, eficiente y versátil. Ideal para sistemas embebidos, aplicaciones de escritorio y desarrollo de software. Base de C++, Java y JavaScript. Control preciso sobre memoria y hardware. Alto rendimiento.', 'color' => '#03599b'],
            ['name' => 'PostgreSQL', 'slug' => 'postresql', 'description' => 'PostgreSQL es un sistema de gestión de bases de datos relacionales (SGBD) de código abierto, robusto y escalable. Es conocido por su confiabilidad, seguridad y flexibilidad.', 'color' => '#2e6691'],
            ['name' => 'Nuxt', 'slug' => 'nuxt', 'description' => 'Nuxt facilita la creación de aplicaciones web universales (SPA y SSR) con Vue.js. Ofrece SEO optimizado, una experiencia de desarrollo fluida y una gran escalabilidad. Ideal para proyectos complejos que requieren alto rendimiento y flexibilidad.', 'color' => '#00bb7f'],
            ['name' => 'NodeJs', 'slug' => 'nodejs', 'description' => 'Node.js te permite crear aplicaciones web escalables y en tiempo real con JavaScript. Su arquitectura asíncrona y basada en eventos la hace ideal para este tipo de aplicaciones. Cuenta con una amplia biblioteca de módulos y es de código abierto.', 'color' => '#5fb146'],
            ['name' => 'Angular', 'slug' => 'angular', 'description' => 'Angular es un framework de JavaScript de código abierto para el desarrollo de aplicaciones web front-end. Se basa en TypeScript, un superconjunto de JavaScript que añade tipado estático.', 'color' => '#dd002d'],
            ['name' => 'jQuery', 'slug' => 'jquery', 'description' => 'jQuery es una biblioteca JavaScript de código abierto que simplifica la selección de elementos del DOM, la manipulación del HTML y la gestión de eventos. Permite a los desarrolladores escribir código JavaScript más conciso y eficiente.', 'color' => '#1265a8'],
            ['name' => 'C++', 'slug' => 'c-plus-plus', 'description' => 'Lenguaje versátil y potente para crear aplicaciones de alto rendimiento. Combina eficiencia, flexibilidad y control sobre el hardware con características como la programación orientada a objetos, plantillas y genéricos.', 'color' => '#5c8dbc'],
            ['name' => 'Ionic Vue', 'slug' => 'ionic-vue', 'description' => 'Framework para crear aplicaciones móviles multiplataforma con Vue.js y Capacitor. Combina la simplicidad de Vue.js con la potencia de Ionic.', 'color' => '#1ca1f2'],
            ['name' => 'MySql', 'slug' => 'mysql', 'description' => 'MySQL es un sistema de gestión de bases de datos relacionales (SGBD) de código abierto, popular por su facilidad de uso y rendimiento. Es una opción ideal para pequeñas y medianas empresas, así como para proyectos personales.', 'color' => '#25547c'],
            ['name' => 'VueJs', 'slug' => 'vuejs', 'description' => 'Framework JavaScript para crear interfaces de usuario interactivas y aplicaciones web de una sola página (SPA). Combina la simplicidad del HTML con la potencia de JavaScript y ofrece un ecosistema de componentes reutilizables y herramientas de desarrollo.', 'color' => '#3eb884'],
            ['name' => 'PHP', 'slug' => 'php', 'description' => 'PHP es un lenguaje de scripting del lado del servidor utilizado para crear aplicaciones web dinámicas. Se integra con HTML y se ejecuta en el servidor web, generando contenido HTML que se envía al navegador del cliente.', 'color' => '#4f5b92'],
            ['name' => 'Laravel', 'slug' => 'laravel', 'description' => 'Framework PHP para crear aplicaciones web robustas y escalables con facilidad. Combina enrutamiento, plantillas, controladores, modelos, autenticación y autorización, y una consola de comandos para un desarrollo rápido y seguro.', 'color' => '#ff291a'],
            ['name' => 'Sqlite', 'slug' => 'sqlite', 'description' => 'SQLite es un motor de base de datos relacional ligero y de código abierto. Se caracteriza por su simplicidad, portabilidad y facilidad de uso. No requiere un servidor de base de datos, ideal para aplicaciones embebidas y dispositivos móviles.', 'color' => '#003756'],
            ['name' => 'Tailwind', 'slug' => 'tailwind', 'description' => 'Tailwind es un framework CSS de código abierto que te permite crear interfaces de usuario web con rapidez y facilidad. Se basa en una colección de clases predefinidas que puedes combinar para aplicar estilos a tus elementos HTML.', 'color' => '#00b5d6'],
            ['name' => 'Ionic Angular', 'slug' => 'ionic-angular', 'description' => 'Framework para crear aplicaciones móviles multiplataforma con Angular y Capacitor. Combina la potencia de Angular con la flexibilidad de Ionic', 'color' => '#c44639'],
            ['name' => 'HTML', 'slug' => 'html', 'description' => 'HTML (HyperText Markup Language) es un lenguaje de marcado que define la estructura y el contenido de las páginas web. Se basa en etiquetas que se utilizan para indicar el tipo de contenido que se presenta, como texto, imágenes, encabezados, enlaces, etc.', 'color' => '#dc2200'],
            ['name' => 'CSS', 'slug' => 'css', 'description' => 'CSS (Cascading Style Sheets) es un lenguaje de estilos que permite controlar la presentación de las páginas web. Se utiliza para definir el aspecto de los elementos HTML como colores, tipografía, tamaño, posición, etc.', 'color' => '#254de3'],
            ['name' => 'Macos', 'slug' => 'macos', 'description' => 'MacOS es un sistema operativo de tipo Unix desarrollado por Apple para sus ordenadores Macintosh. Se basa en Unix y Darwin, y ofrece una interfaz gráfica de usuario intuitiva, una alta seguridad y estabilidad.', 'color' => '#dde3e7'],
            ['name' => 'Java', 'slug' => 'java', 'description' => 'Java es un lenguaje de programación orientado a objetos, robusto, seguro y de propósito general. Se caracteriza por su portabilidad, lo que significa que el código Java se puede ejecutar en cualquier plataforma que tenga una máquina virtual Java (JVM).', 'color' => '#f8971c'],
            ['name' => 'GNU/Linux', 'slug' => 'gnu-linux', 'description' => 'GNU/Linux es un sistema operativo de tipo Unix compuesto por el kernel Linux, desarrollado por Linus Torvalds, y herramientas/aplicaciones GNU. Es un sistema libre y de código abierto, su código fuente está disponible para modificarlo y distribuirlo.', 'color' => '#f5bd0c'],
            ['name' => 'Swift', 'slug' => 'swift', 'description' => 'Swift es un lenguaje de programación multiparadigma creado por Apple enfocado en el desarrollo de aplicaciones para iOS y macOS.', 'color' => '#f05035'],
        ];

        // $now = Carbon::now();

        foreach ($datas as $data) {
            Technology::firstOrCreate(['slug' => $data['slug']], $data);
        }
    }
}
