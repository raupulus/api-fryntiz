<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Http\Controllers\WeatherStation\WeatherStationController;
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

    public function handle()
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
        $staticUrls = [
            ['url' => route('home'), 'priority' => 1.0, 'changefreq' => 'monthly'],
            ['url' => route('smartplant.index'), 'priority' => 0.7, 'changefreq' => 'weekly'],
        ];

        foreach ($staticUrls as $urlData) {
            $sitemap->add(
                Url::create($urlData['url'])
                    ->setPriority($urlData['priority'])
                    ->setChangeFrequency($urlData['changefreq'])
                    ->setLastModificationDate(Carbon::now())
            );
        }

        $this->addSmartPlantUrls($sitemap);
        $this->addWeatherStationUrls($sitemap);

        return $sitemap;
    }

    private function addSmartPlantUrls(Sitemap $sitemap): void
    {
        SmartPlantPlant::all()->each(function (SmartPlantPlant $plant) use ($sitemap) {
            $sitemap->add(
                Url::create(route('smartplant.show', $plant))
                    ->setPriority(0.6)
                    ->setChangeFrequency('weekly')
                    ->setLastModificationDate($plant->updated_at ?? Carbon::now())
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
        $sitemap->add(
            Url::create(route('weather_station.index'))
                ->setPriority(0.6)
                ->setChangeFrequency('hourly')
                ->setLastModificationDate(Carbon::now())
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
        $backupPath = public_path('sitemap_backup.xml');

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

            // # Elimino backup si todo salió bien
            if (file_exists($backupPath)) {
                unlink($backupPath);
            }

            $this->info('   ✓ Sitemap escrito correctamente');

        } catch (\Exception $e) {
            // # Restaura backup si algo sale mal
            if (file_exists($backupPath)) {
                copy($backupPath, $sitemapPath);
                unlink($backupPath);
            }
            throw $e;
        }
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
