<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

/**
 * Seeder de categorías por defecto.
 */
class CategoriesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            ['name' => 'iot', 'slug' => 'iot', 'description' => 'Internet de las cosas: dispositivos conectados, sensores, automatización y domótica.'],
            ['name' => 'Desarrollo Web', 'slug' => 'desarrollo-web', 'description' => 'Desarrollo de aplicaciones y sitios web, tanto frontend como backend.'],
            ['name' => 'frontend', 'slug' => 'frontend', 'description' => 'Desarrollo de interfaces de usuario, maquetación, CSS, JavaScript y frameworks de cliente.'],
            ['name' => 'backend', 'slug' => 'backend', 'description' => 'Desarrollo del lado del servidor, APIs, bases de datos y lógica de negocio.'],
            ['name' => 'hardware', 'slug' => 'hardware', 'description' => 'Componentes físicos, placas, periféricos y dispositivos electrónicos.'],
            ['name' => 'microcontroladores', 'slug' => 'microcontroladores', 'description' => 'Programación y proyectos con microcontroladores como ESP32, Arduino, Raspberry Pi Pico.'],
            ['name' => 'crypto', 'slug' => 'crypto', 'description' => 'Criptomonedas, blockchain, minería y tecnologías descentralizadas.'],
            ['name' => 'gadget', 'slug' => 'gadget', 'description' => 'Dispositivos electrónicos innovadores, accesorios tecnológicos y wearables.'],
            ['name' => 'energía', 'slug' => 'energia', 'description' => 'Energía solar, eólica, baterías, monitorización de consumo y generación eléctrica.'],
            ['name' => 'tool', 'slug' => 'tool', 'description' => 'Herramientas de desarrollo, productividad y utilidades de software.'],
            ['name' => 'debian', 'slug' => 'debian', 'description' => 'Distribución GNU/Linux Debian: configuración, paquetes, administración y derivadas.'],
            ['name' => 'macos', 'slug' => 'macos', 'description' => 'Sistema operativo macOS de Apple: configuración, trucos, aplicaciones y desarrollo.'],
            ['name' => 'developer', 'slug' => 'developer', 'description' => 'Contenido general para desarrolladores: buenas prácticas, metodologías y carrera profesional.'],
            ['name' => 'linux', 'slug' => 'linux', 'description' => 'Sistema operativo GNU/Linux: distribuciones, kernel, comandos y administración de sistemas.'],
            ['name' => 'bash', 'slug' => 'bash', 'description' => 'Scripting en Bash, automatización de tareas y terminal de comandos.'],
            ['name' => 'apple', 'slug' => 'apple', 'description' => 'Ecosistema Apple: iOS, macOS, Swift, hardware y desarrollo para plataformas Apple.'],
            ['name' => 'scripts', 'slug' => 'scripts', 'description' => 'Scripts de automatización, utilidades y herramientas de línea de comandos.'],
            ['name' => 'Inteligencia Artificial', 'slug' => 'inteligencia-artificial', 'description' => 'IA, machine learning, deep learning, modelos de lenguaje y aplicaciones de inteligencia artificial.'],
        ];

        foreach ($categories as $category) {
            Category::firstOrCreate(
                ['slug' => $category['slug']],
                $category
            );
        }
    }
}
