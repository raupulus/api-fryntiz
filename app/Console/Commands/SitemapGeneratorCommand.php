<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Http\Controllers\WeatherStation\WeatherStationController;
use App\Models\CV\Curriculum;
use App\Models\Gdacs\GdacsEvent;
use App\Models\Hardware\HardwareDevice;
use App\Models\SmartPlant\SmartPlantPlant;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\Tags\Url;

class SitemapGeneratorCommand extends Command
{
    protected $signature = 'sitemap:generate
                            {--force : Forzar regeneración sin cache}
                            {--chunk=100 : Número de registros por chunk}';

    protected $description = 'Genera el sitemap del sitio completo con metadatos optimizados';

    private const CACHE_KEY = 'sitemap_generation_lock';

    private const CACHE_TTL = 3600; // 1 hora

    public function handle(): int
    {
        if (! $this->option('force') && Cache::has(self::CACHE_KEY)) {
            $this->warn('Generación de sitemap ya en progreso o reciente. Use --force para omitir.');

            return self::SUCCESS;
        }

        Cache::put(self::CACHE_KEY, true, self::CACHE_TTL);

        try {
            $this->info('🚀 Iniciando generación de sitemap...');

            $sitemap = $this->createBaseSitemap();

            $this->writeSitemapToFile($sitemap);

            $this->info('✅ Sitemap generado correctamente');

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->handleError($e);

            return self::FAILURE;
        } finally {
            Cache::forget(self::CACHE_KEY);
        }
    }

    private function createBaseSitemap(): Sitemap
    {
        $sitemap = Sitemap::create();

        // # URLs estáticas con prioridades y frecuencia de actualización
        //
        // Estaban sólo la portada y las plantas: faltaban «Sobre mí», vuelos,
        // el contador de pulsaciones y el panel de energía, que son páginas
        // públicas y no se indexaban. La documentación de la API (/docs) ya
        // no aparece aquí: ahora exige sesión iniciada y no tiene sentido
        // indexarla ni ofrecerla a rastreadores anónimos.
        //
        // `/about` dejó de ser una redirección a la portada: ahora es una
        // página real con la información del proyecto (2026-09-14).
        //
        // `cv.index` entra siempre, tenga o no currículums públicos hoy: es un
        // listado, igual que `smartplant.index` o `weather_station.index`, y
        // se comporta igual que ellos aunque esté vacío. Lo dinámico —que
        // depende de cuántos currículums estén marcados como públicos— son las
        // páginas de cada uno, que añade `addCurriculumUrls()`.
        $staticUrls = [
            ['url' => route('home'), 'priority' => 1.0, 'changefreq' => 'monthly', 'view' => 'home.blade.php'],
            ['url' => route('about'), 'priority' => 0.8, 'changefreq' => 'monthly', 'view' => 'about.blade.php'],
            ['url' => route('smartplant.index'), 'priority' => 0.7, 'changefreq' => 'weekly', 'view' => 'smartplant/index.blade.php'],
            ['url' => route('hardware.energy.index'), 'priority' => 0.7, 'changefreq' => 'daily', 'view' => 'hardware/energy/index.blade.php'],
            ['url' => route('hardware.index'), 'priority' => 0.6, 'changefreq' => 'daily', 'view' => 'hardware/index.blade.php'],
            ['url' => route('keycounter.index'), 'priority' => 0.6, 'changefreq' => 'daily', 'view' => 'keycounter/index.blade.php'],
            ['url' => route('airflight.index'), 'priority' => 0.6, 'changefreq' => 'daily', 'view' => 'airflight/index.blade.php'],
            ['url' => route('cv.index'), 'priority' => 0.6, 'changefreq' => 'monthly', 'view' => 'cv/index.blade.php'],
            ['url' => route('legal.notice'), 'priority' => 0.2, 'changefreq' => 'yearly', 'view' => 'legal/notice.blade.php'],
            ['url' => route('legal.privacy'), 'priority' => 0.2, 'changefreq' => 'yearly', 'view' => 'legal/privacy.blade.php'],
            ['url' => route('legal.cookies'), 'priority' => 0.2, 'changefreq' => 'yearly', 'view' => 'legal/cookies.blade.php'],
        ];

        foreach ($staticUrls as $urlData) {
            $sitemap->add(
                Url::create($urlData['url'])
                    ->setPriority($urlData['priority'])
                    ->setChangeFrequency($urlData['changefreq'])
                    ->setLastModificationDate($this->viewLastModified($urlData['view']))
            );
        }

        $this->addSmartPlantUrls($sitemap);
        $this->addWeatherStationUrls($sitemap);
        $this->addCurriculumUrls($sitemap);
        $this->addHardwareUrls($sitemap);

        return $sitemap;
    }

    /**
     * Añade las páginas individuales de cada dispositivo hardware marcado como público.
     * Los dispositivos privados nunca entran al sitemap.
     */
    private function addHardwareUrls(Sitemap $sitemap): void
    {
        HardwareDevice::public()->get()->each(function (HardwareDevice $device) use ($sitemap) {
            $sitemap->add(
                Url::create(route('hardware.show', $device->slug))
                    ->setPriority(0.5)
                    ->setChangeFrequency('weekly')
                    ->setLastModificationDate($device->updated_at ?? Carbon::now())
            );
        });
    }

    /**
     * Un currículum sólo entra en el sitemap si está marcado como público
     * (`Curriculum::scopePublicOnly()`): los compartidos por token y los
     * privados no deben ser indexables, y esa es exactamente la comprobación
     * que ya hace `Curriculum::isVisibleTo()` sin token —la misma que usa
     * `CurriculumController::show()` para decidir el 404.
     *
     * Interesa que cuantos más currículums públicos haya, más visibilidad:
     * todos entran, no sólo uno.
     */
    private function addCurriculumUrls(Sitemap $sitemap): void
    {
        Curriculum::publicOnly()
            ->select(['id', 'slug', 'updated_at'])
            ->lazyById(200)
            ->each(function (Curriculum $cv) use ($sitemap) {
                $sitemap->add(
                    Url::create(route('cv.show', ['slug' => $cv->slug]))
                        ->setPriority(0.5)
                        ->setChangeFrequency('monthly')
                        ->setLastModificationDate($cv->updated_at ?? Carbon::now())
                );
            });
    }

    private function addSmartPlantUrls(Sitemap $sitemap): void
    {
        SmartPlantPlant::query()
            ->select(['id', 'slug', 'updated_at'])
            ->lazyById(200)
            ->each(function (SmartPlantPlant $plant) use ($sitemap) {
                $sitemap->add(
                    Url::create(route('smartplant.show', ['smartplant' => $plant->slug]))
                        ->setPriority(0.6)
                        ->setChangeFrequency('weekly')
                        ->setLastModificationDate($plant->updated_at ?? $this->viewLastModified('smartplant/show.blade.php'))
                );
            });
    }

    /**
     * Añade las vistas interiores de la estación meteorológica: el índice y,
     * por cada estación (o de forma global si aún no hay ninguna clasificada),
     * la página de detalle de cada sensor que tenga al menos un registro.
     *
     * Los sensores sin datos para una estación se omiten: podría ser una
     * estación que nunca rellenará ese sensor (EJ: solo mide viento).
     */
    private function addWeatherStationUrls(Sitemap $sitemap): void
    {
        $latestStationUpdatedAt = HardwareDevice::weatherStations()->max('updated_at');
        $weatherIndexLastMod = $latestStationUpdatedAt
            ? Carbon::parse($latestStationUpdatedAt)
            : $this->viewLastModified('weather_station/index.blade.php');

        $sitemap->add(
            Url::create(route('weather_station.index'))
                ->setPriority(0.6)
                ->setChangeFrequency('hourly')
                ->setLastModificationDate($weatherIndexLastMod)
        );

        $latestGdacsUpdatedAt = GdacsEvent::query()->max('updated_at');
        $gdacsLastMod = $latestGdacsUpdatedAt
            ? Carbon::parse($latestGdacsUpdatedAt)
            : $this->viewLastModified('gdacs/index.blade.php');

        // Menor prioridad que el índice: es un histórico de alertas, no la
        // página principal del módulo.
        $sitemap->add(
            Url::create(route('weather_station.gdacs.index'))
                ->setPriority(0.3)
                ->setChangeFrequency('daily')
                ->setLastModificationDate($gdacsLastMod)
        );

        $stationIds = HardwareDevice::weatherStations()->pluck('id');

        // Reserva: si aún no hay ninguna estación clasificada, generamos las
        // urls globales (sin `station`) para no dejar el módulo sin páginas.
        $stationIds = $stationIds->isNotEmpty() ? $stationIds : collect([null]);

        foreach ($stationIds as $stationId) {
            foreach (WeatherStationController::SENSOR_MAP as $type => $config) {
                $model = $config['model'];
                $primaryField = $config['primary']['field'];

                $lastCreatedAt = $model::whereNotNull($primaryField)
                    ->when($stationId, fn ($q) => $q->where('hardware_device_id', $stationId))
                    ->max('created_at');

                if (! $lastCreatedAt) {
                    continue;
                }

                $sitemap->add(
                    Url::create(route('weather_station.sensor', array_filter([
                        'type' => $type,
                        'station' => $stationId,
                    ])))
                        ->setPriority(0.5)
                        ->setChangeFrequency('daily')
                        ->setLastModificationDate(Carbon::parse($lastCreatedAt))
                );
            }
        }
    }

    private function writeSitemapToFile(Sitemap $sitemap): void
    {
        $this->info('💾 Escribiendo sitemap a archivo...');

        $sitemapPath = public_path('sitemap.xml');

        // La copia de seguridad NO va en `public/`: ahí la sirve el servidor
        // web, y con ella se puede leer el sitemap anterior entero desde fuera
        // —incluidas las URL que se hayan quitado a propósito—. Además queda
        // suelta en el repositorio si la generación se corta a medias.
        $backupPath = storage_path('app/sitemap_backup.xml');

        // # Creo backup del sitemap anterior si existe
        if (file_exists($sitemapPath)) {
            copy($sitemapPath, $backupPath);
        }

        try {
            $sitemap->writeToFile($sitemapPath);

            // # Verifico que el archivo se escribió correctamente
            if (! file_exists($sitemapPath) || filesize($sitemapPath) === 0) {
                throw new \Exception('El archivo sitemap.xml está vacío o no se pudo crear');
            }

            // Borrar la copia es limpieza, no parte del trabajo. Si el sistema
            // de ficheros no deja —permisos, un montaje de sólo lectura— el
            // sitemap ya está escrito y es correcto: no se tira el resultado
            // por no poder borrar un fichero temporal, se avisa y punto.
            $this->deleteBackup($backupPath);

            $this->info('   ✓ Sitemap escrito correctamente');

        } catch (\Exception $e) {
            // # Restaura backup si algo sale mal
            if (file_exists($backupPath)) {
                copy($backupPath, $sitemapPath);
                $this->deleteBackup($backupPath);
            }
            throw $e;
        }
    }

    /**
     * Borra la copia de seguridad sin que un fallo al borrar tumbe el comando.
     */
    private function deleteBackup(string $path): void
    {
        if (! file_exists($path)) {
            return;
        }

        if (! @unlink($path)) {
            $this->warn('   No se ha podido borrar la copia de seguridad: '.$path);
        }
    }

    /**
     * Fecha real de modificación de una plantilla Blade como respaldo para `<lastmod>`.
     */
    private function viewLastModified(string $relativeViewPath): Carbon
    {
        $fullPath = resource_path('views/'.$relativeViewPath);

        if (is_file($fullPath)) {
            $mtime = filemtime($fullPath);

            if ($mtime !== false) {
                return Carbon::createFromTimestamp($mtime);
            }
        }

        return Carbon::now();
    }

    private function handleError(\Exception $e): void
    {
        $this->error('❌ Error al generar el sitemap: '.$e->getMessage());

        Log::error('SitemapGeneratorCommand: Error al generar el sitemap', [
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'line' => $e->getLine(),
            'file' => $e->getFile(),
            'memory_usage' => memory_get_usage(true),
            'peak_memory' => memory_get_peak_usage(true),
        ]);
    }
}
