// Bloque de código del editor: `@calumk/editorjs-codecup`, el sucesor de
// `@calumk/editorjs-codeflask` (el que usaba `main`, hoy retirado de npm).
// Guarda `{code, language, showlinenumbers, showCopyButton}`: lo que ya leía
// `editor/fields/_code.blade.php` más dos opciones que la web ignora.
//
// El paquete trae su propio Prism con sólo HTML, CSS y JavaScript, y un
// autoloader que baja el resto de lenguajes de cdnjs cuando se usan. Para que
// el panel no cargue scripts de fuera, los lenguajes que se ofrecen vienen de
// npm (`prismjs`, misma familia de versión) y se registran aquí, en el Prism
// del paquete (`window.Prism`, que el paquete crea al cargarse: por eso sus
// imports van después). El autoloader apunta a una ruta local: un bloque viejo
// con un lenguaje fuera de la lista se ve sin colores, pero no sale a internet.

import CodeCup from '@calumk/editorjs-codecup';
import 'prismjs/components/prism-markup-templating.js';
import 'prismjs/components/prism-bash.js';
import 'prismjs/components/prism-c.js';
import 'prismjs/components/prism-cpp.js';
import 'prismjs/components/prism-arduino.js';
import 'prismjs/components/prism-diff.js';
import 'prismjs/components/prism-docker.js';
import 'prismjs/components/prism-go.js';
import 'prismjs/components/prism-ini.js';
import 'prismjs/components/prism-java.js';
import 'prismjs/components/prism-json.js';
import 'prismjs/components/prism-markdown.js';
import 'prismjs/components/prism-nginx.js';
import 'prismjs/components/prism-php.js';
import 'prismjs/components/prism-powershell.js';
import 'prismjs/components/prism-python.js';
import 'prismjs/components/prism-rust.js';
import 'prismjs/components/prism-sql.js';
import 'prismjs/components/prism-typescript.js';
import 'prismjs/components/prism-yaml.js';

if (window.Prism?.plugins?.autoloader) {
    window.Prism.plugins.autoloader.languages_path = '/vendor/prism-sin-descargas/';
}

// Clave de Prism → nombre en el desplegable. `none` es «sin colores» y el
// paquete lo guarda como `plain`.
export const codeLanguages = {
    none: 'Texto',
    arduino: 'Arduino',
    bash: 'Bash',
    c: 'C',
    cpp: 'C++',
    css: 'CSS',
    diff: 'Diff',
    docker: 'Dockerfile',
    go: 'Go',
    markup: 'HTML',
    ini: 'INI',
    java: 'Java',
    javascript: 'JavaScript',
    json: 'JSON',
    markdown: 'Markdown',
    nginx: 'Nginx',
    php: 'PHP',
    powershell: 'PowerShell',
    python: 'Python',
    rust: 'Rust',
    sql: 'SQL',
    typescript: 'TypeScript',
    yaml: 'YAML',
};

export default class CodeBlock extends CodeCup {
    // Un bloque nuevo empieza vacío, no con «// Hello World».
    constructor(options) {
        super({ ...options, data: { code: '', ...(options.data ?? {}) } });
    }

    static get toolbox() {
        return { ...CodeCup.toolbox, title: 'Code' };
    }
}
