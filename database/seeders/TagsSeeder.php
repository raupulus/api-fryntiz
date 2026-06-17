<?php

namespace Database\Seeders;

use App\Models\Tag;
use Illuminate\Database\Seeder;

/**
 * Seeder de tags por defecto.
 */
class TagsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tags = [
            ['name' => 'iot', 'slug' => 'iot', 'description' => 'Internet de las cosas y dispositivos conectados.'],
            ['name' => 'sensors', 'slug' => 'sensors', 'description' => 'Sensores electrónicos de temperatura, humedad, presión, luz, etc.'],
            ['name' => 'maker', 'slug' => 'maker', 'description' => 'Movimiento maker: proyectos DIY, fabricación digital y electrónica creativa.'],
            ['name' => 'raspberry', 'slug' => 'raspberry', 'description' => 'Raspberry Pi: mini computadoras para proyectos electrónicos y servidores.'],
            ['name' => 'weather station', 'slug' => 'weather-station', 'description' => 'Estaciones meteorológicas y monitorización del clima.'],
            ['name' => 'anemometer', 'slug' => 'anemometer', 'description' => 'Anemómetros y medición de velocidad del viento.'],
            ['name' => 'wind', 'slug' => 'wind', 'description' => 'Viento: dirección, velocidad, rachas y energía eólica.'],
            ['name' => 'uva', 'slug' => 'uva', 'description' => 'Radiación ultravioleta tipo A.'],
            ['name' => 'light', 'slug' => 'light', 'description' => 'Sensores de luz, luminosidad y radiación solar.'],
            ['name' => 'pressure', 'slug' => 'pressure', 'description' => 'Presión atmosférica y barométrica.'],
            ['name' => 'temperature', 'slug' => 'temperature', 'description' => 'Medición y monitorización de temperatura.'],
            ['name' => 'dpkg', 'slug' => 'dpkg', 'description' => 'Gestor de paquetes dpkg de Debian.'],
            ['name' => 'debian', 'slug' => 'debian', 'description' => 'Distribución GNU/Linux Debian.'],
            ['name' => 'apt', 'slug' => 'apt', 'description' => 'Gestor de paquetes APT para distribuciones basadas en Debian.'],
            ['name' => 'gestor de paquetes', 'slug' => 'gestor-de-paquetes', 'description' => 'Gestores de paquetes de software: apt, dpkg, brew, npm, pip, etc.'],
            ['name' => 'api', 'slug' => 'api', 'description' => 'APIs REST, GraphQL y servicios web.'],
            ['name' => 'raspberry pi pico', 'slug' => 'raspberry-pi-pico', 'description' => 'Microcontrolador Raspberry Pi Pico con RP2040.'],
            ['name' => 'btc', 'slug' => 'btc', 'description' => 'Bitcoin y criptomonedas relacionadas.'],
            ['name' => 'miner', 'slug' => 'miner', 'description' => 'Minería de criptomonedas: hardware y software.'],
            ['name' => 'esp32', 'slug' => 'esp32', 'description' => 'Microcontrolador ESP32 con WiFi y Bluetooth.'],
            ['name' => 'software', 'slug' => 'software', 'description' => 'Desarrollo de software en general.'],
            ['name' => 'tool', 'slug' => 'tool', 'description' => 'Herramientas de desarrollo y utilidades.'],
            ['name' => 'linux', 'slug' => 'linux', 'description' => 'Sistema operativo GNU/Linux.'],
            ['name' => 'macos', 'slug' => 'macos', 'description' => 'Sistema operativo macOS de Apple.'],
            ['name' => 'fedora', 'slug' => 'fedora', 'description' => 'Distribución GNU/Linux Fedora.'],
            ['name' => 'raspberry os', 'slug' => 'raspberry-os', 'description' => 'Sistema operativo Raspberry Pi OS (anteriormente Raspbian).'],
            ['name' => 'rasbian', 'slug' => 'rasbian', 'description' => 'Raspbian, distribución Debian para Raspberry Pi (nombre anterior).'],
            ['name' => 'servidores', 'slug' => 'servidores', 'description' => 'Administración de servidores, hosting y despliegue.'],
            ['name' => 'gnu', 'slug' => 'gnu', 'description' => 'Proyecto GNU y software libre.'],
            ['name' => 'keycounter', 'slug' => 'keycounter', 'description' => 'Contador de pulsaciones de teclado y ratón.'],
            ['name' => 'project', 'slug' => 'project', 'description' => 'Proyectos de desarrollo y electrónica.'],
            ['name' => 'script', 'slug' => 'script', 'description' => 'Scripts de automatización y utilidades.'],
            ['name' => 'apple', 'slug' => 'apple', 'description' => 'Ecosistema Apple: hardware y software.'],
            ['name' => 'lightning', 'slug' => 'lightning', 'description' => 'Rayos, detección de descargas eléctricas atmosféricas.'],
            ['name' => 'max7219', 'slug' => 'max7219', 'description' => 'Chip MAX7219 para control de matrices LED y displays de 7 segmentos.'],
        ];

        foreach ($tags as $tag) {
            Tag::firstOrCreate(
                ['slug' => $tag['slug']],
                $tag
            );
        }
    }
}
