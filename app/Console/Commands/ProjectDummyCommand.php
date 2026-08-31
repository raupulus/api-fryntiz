<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Content\Content;
use App\Models\Content\ContentPage;
use App\Models\Email;
use App\Models\Hardware\HardwareDevice;
use App\Models\Hardware\HardwareType;
use App\Models\Newsletter;
use App\Models\Platform;
use App\Models\SmartPlant\SmartPlantPlant;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Comando para generar datos de ejemplo corporativos realistas en todos los módulos.
 * Ideal para demostraciones a clientes y desarrollo local sin requerir volcado de producción.
 */
class ProjectDummyCommand extends Command
{
    protected $signature = 'project:dummy
        {--fresh : Ejecuta migrate:fresh y seed antes de poblar datos}
        {--force : Forzar ejecución en entornos no locales}';

    /** @var array<string> */
    protected $aliases = ['xerintel:dummy'];

    protected $description = 'Genera datos corporativos realistas para todos los módulos del proyecto';

    public function handle(): int
    {
        if (app()->isProduction() && ! $this->option('force')) {
            $this->error('Este comando está destinado a entornos de desarrollo y demostración.');
            $this->warn('Para forzar en este entorno use --force.');

            return self::FAILURE;
        }

        $this->info('====================================================');
        $this->info('🚀 Generando contenido corporativo de ejemplo');
        $this->info('====================================================');

        if ($this->option('fresh')) {
            $this->line('▶ Ejecutando migrate:fresh --seed...');
            $this->call('migrate:fresh', ['--seed' => true, '--force' => true]);
        } else {
            // Asegurar que los seeders base se hayan ejecutado
            if (Category::query()->doesntExist() || HardwareType::query()->doesntExist()) {
                $this->line('▶ Ejecutando seeders base indispensables...');
                $this->call('db:seed', ['--force' => true]);
            }
        }

        // 1. Obtener o crear usuario de demostración
        $user = User::query()->first();
        if (! $user) {
            $user = User::create([
                'name' => 'Raúl Caro Pastorino',
                'nick' => 'raupulus',
                'email' => 'demo@raupulus.dev',
                'password' => bcrypt('password'),
                'role_id' => 1,
                'is_active' => true,
            ]);
            $this->line('✓ Usuario de demostración preparado.');
        }

        // 2. Ejecutar generadores de datos debug base
        $this->line('▶ Poblando datos base de módulos vía debug:seed-all...');
        $this->call('debug:seed-all', ['--small' => true]);

        // 3. Crear Plataformas Corporativas Realistas
        $this->line('▶ Asegurando plataformas corporativas...');
        $platformsData = [
            [
                'title' => 'Raupulus Tech & Consulting',
                'description' => 'Soluciones integrales de hardware IoT, telemetría y software distribuido para empresas.',
                'domain' => 'tech.raupulus.dev',
                'is_active' => true,
            ],
            [
                'title' => 'Portal de Telemetría IoT',
                'description' => 'Plataforma cloud para supervisión en tiempo real de estaciones meteorológicas y energía solar.',
                'domain' => 'iot.raupulus.dev',
                'is_active' => true,
            ],
            [
                'title' => 'GreenEnergy Smart Monitor',
                'description' => 'Monitorización avanzada de generación fotovoltaica, baterías y consumos industriales.',
                'domain' => 'energy.raupulus.dev',
                'is_active' => true,
            ],
        ];

        $platforms = [];
        foreach ($platformsData as $data) {
            $platform = Platform::firstOrCreate(
                ['domain' => $data['domain']],
                [
                    'user_id' => $user->id,
                    'title' => $data['title'],
                    'slug' => Str::slug($data['title']),
                    'description' => $data['description'],
                    'is_active' => true,
                ]
            );

            // Asociar categorías y etiquetas si no las tiene
            $cat = Category::query()->inRandomOrder()->first();
            if ($cat) {
                $platform->categories()->syncWithoutDetaching([$cat->id]);
            }
            $tag = Tag::query()->inRandomOrder()->first();
            if ($tag) {
                $platform->tags()->syncWithoutDetaching([$tag->id]);
            }

            $platforms[] = $platform;
        }

        // 4. Crear Contenidos Corporativos Destacados
        $this->line('▶ Generando artículos y guías corporativas...');
        $contentsData = [
            [
                'title' => 'Arquitectura de Red IoT con Sensores de Alta Precisión',
                'description' => 'Guía de diseño e implementación de sistemas de telemetría resilientes utilizando microcontroladores ESP32 y transmisión en tiempo real.',
                'content' => 'La monitorización continua en entornos industriales y agrícolas requiere infraestructuras robustas capaces de gestionar fluctuaciones de red y condiciones climáticas adversas. Esta arquitectura combina nodos autónomos con alimentación solar y buffers locales de persistencia.',
            ],
            [
                'title' => 'Optimización de Generación Fotovoltaica y Gestión de Baterías',
                'description' => 'Análisis de datos de curvas de carga y descarga en instalaciones solares aisladas con algoritmos de previsión meteorológica.',
                'content' => 'A través de la integración directa con estaciones meteorológicas locales y modelos predictivos AEMET, el sistema anticipa picos de irradiación solar para priorizar cargas críticas y maximizar el ciclo de vida de los bancos de baterías.',
            ],
            [
                'title' => 'Despliegue de Estaciones Meteorológicas de Código Abierto',
                'description' => 'Especificaciones de hardware, calibración de sensores barométricos y tratamiento de variables ambientales.',
                'content' => 'Cada sensor (anemómetro, pluviómetro, higrómetro y detección de rayos) se calibra con estándares meteorológicos para asegurar precisión analítica en la toma de decisiones.',
            ],
        ];

        foreach ($contentsData as $idx => $cData) {
            $mainPlatform = $platforms[$idx % count($platforms)];
            $slug = Str::slug($cData['title']);

            $content = Content::firstOrCreate(
                ['slug' => $slug],
                [
                    'user_id' => $user->id,
                    'platform_id' => $mainPlatform->id,
                    'type_id' => 1, // Artículo/Página
                    'status_id' => 1, // Publicado
                    'title' => $cData['title'],
                    'slug' => $slug,
                    'description' => $cData['description'],
                    'is_featured' => true,
                    'published_at' => now()->subDays($idx * 3),
                ]
            );

            // Crear página asociada con el texto corporativo
            ContentPage::firstOrCreate(
                [
                    'content_id' => $content->id,
                    'order' => 1,
                ],
                [
                    'title' => $cData['title'],
                    'content' => $cData['content'],
                ]
            );
        }

        // 5. Crear Dispositivos Hardware Corporativos
        $this->line('▶ Configurando dispositivos hardware de muestra...');
        $hwDevices = [
            [
                'name' => 'Estación Meteorológica Central Alpha',
                'description' => 'Nodo principal de monitorización ambiental exterior en sede central.',
                'hardware_type_id' => 1,
            ],
            [
                'name' => 'Inversor y Monitor Fotovoltaico 5kW',
                'description' => 'Unidad de monitorización de potencia solar y estado de almacenamiento.',
                'hardware_type_id' => 1,
            ],
            [
                'name' => 'Sensor Climatización Laboratorio I+D',
                'description' => 'Telemetría de precisión para temperatura, humedad relativa y calidad del aire interior.',
                'hardware_type_id' => 1,
            ],
        ];

        foreach ($hwDevices as $hwData) {
            $typeId = HardwareType::query()->value('id') ?? 1;
            HardwareDevice::firstOrCreate(
                ['name' => $hwData['name']],
                [
                    'user_id' => $user->id,
                    'hardware_type_id' => $typeId,
                    'description' => $hwData['description'],
                    'mac' => strtoupper(fake()->unique()->macAddress()),
                    'ip_local' => '192.168.1.'.fake()->numberBetween(20, 200),
                    'ip_wan' => '80.28.'.fake()->numberBetween(10, 250).'.'.fake()->numberBetween(10, 250),
                    'software_version' => '2.4.0',
                    'hardware_version' => '1.2-revB',
                ]
            );
        }

        // 6. Mensajes de Contacto y Suscriptores Realistas
        $this->line('▶ Registrando mensajes de contacto corporativos y suscriptores...');
        $contacts = [
            [
                'name' => 'Alejandro Martínez Gómez',
                'email' => 'amartinez@agroinnovacion.es',
                'subject' => 'Consulta sobre telemetría para monitorización agrícola',
                'message' => 'Estimado equipo de Raupulus, hemos revisado su arquitectura de sensores y nos gustaría evaluar una prueba de concepto para nuestra cooperativa agrícola. Quedamos a su disposición para coordinar una reunión.',
            ],
            [
                'name' => 'Elena Romero Soler',
                'email' => 'eromero@solartech-solutions.com',
                'subject' => 'Integración de API de monitorización solar',
                'message' => 'Hola Raúl, requerimos integrar la telemetría de vuestros inversores en nuestro panel corporativo. ¿Sería posible agendar una videollamada para revisar los contratos de la API V2?',
            ],
        ];

        foreach ($contacts as $c) {
            Email::firstOrCreate(
                ['email' => $c['email']],
                [
                    'name' => $c['name'],
                    'subject' => $c['subject'],
                    'message' => $c['message'],
                    'is_read' => false,
                ]
            );
        }

        $newsletters = [
            ['email' => 'director.tecnico@smartcitigroup.com', 'name' => 'Carlos Varela'],
            ['email' => 'investigacion@climatologia-andalucia.org', 'name' => 'Dra. Carmen Santos'],
        ];

        foreach ($newsletters as $nl) {
            Newsletter::firstOrCreate(
                ['email' => $nl['email']],
                [
                    'name' => $nl['name'],
                    'platform_id' => $platforms[0]->id,
                    'token_verified' => Str::random(32),
                    'is_verified' => true,
                ]
            );
        }

        $this->newLine();
        $this->info('====================================================');
        $this->info('✅ Datos corporativos de ejemplo generados con éxito');
        $this->info('====================================================');
        $this->table(
            ['Módulo', 'Datos Generados'],
            [
                ['Plataformas', Platform::count().' plataformas configuradas'],
                ['Contenidos (CMS)', Content::count().' artículos y guías corporativas'],
                ['Dispositivos Hardware', HardwareDevice::count().' dispositivos con telemetría'],
                ['Plantas Inteligentes', SmartPlantPlant::count().' unidades monitorizadas'],
                ['Mensajes de Contacto', Email::count().' mensajes de clientes'],
                ['Suscriptores Newsletter', Newsletter::count().' suscriptores verificados'],
            ]
        );

        return self::SUCCESS;
    }
}
