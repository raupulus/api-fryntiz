<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\AirFlight\AirFlightAirPlane;
use App\Models\AirFlight\AirFlightRoute;
use App\Models\ApiToken;
use App\Models\Category;
use App\Models\Content\Content;
use App\Models\CV\Curriculum;
use App\Models\CV\CurriculumAvailableRepositoryType;
use App\Models\Email;
use App\Models\FileType;
use App\Models\Gallery;
use App\Models\GalleryImage;
use App\Models\Hardware\EnergySystem;
use App\Models\Hardware\HardwareAvailableComponent;
use App\Models\Hardware\HardwareDevice;
use App\Models\Hardware\HardwareEnergy;
use App\Models\Hardware\HardwareType;
use App\Models\KeyCounter\Keyboard;
use App\Models\KeyCounter\Mouse;
use App\Models\Newsletter;
use App\Models\Platform;
use App\Models\Printer;
use App\Models\PrinterAvailableType;
use App\Models\PrinterStack;
use App\Models\SmartPlant\SmartPlantPlant;
use App\Models\SmartPlant\SmartPlantRegister;
use App\Models\Tag;
use App\Models\Technology;
use App\Models\User;
use App\Models\WeatherStation\AirQuality;
use App\Models\WeatherStation\Eco2;
use App\Models\WeatherStation\Humidity;
use App\Models\WeatherStation\Light;
use App\Models\WeatherStation\Lightning;
use App\Models\WeatherStation\Pressure;
use App\Models\WeatherStation\Rain;
use App\Models\WeatherStation\Temperature;
use App\Models\WeatherStation\Tvoc;
use App\Models\WeatherStation\Wind;
use App\Models\WeatherStation\WindDirection;
use App\Policies\AdminCatalogPolicy;
use App\Policies\AirFlightPolicy;
use App\Policies\AirFlightRoutePolicy;
use App\Policies\ApiTokenPolicy;
use App\Policies\CategoryPolicy;
use App\Policies\ContentPolicy;
use App\Policies\CurriculumPolicy;
use App\Policies\EmailPolicy;
use App\Policies\EnergySystemPolicy;
use App\Policies\GalleryPolicy;
use App\Policies\HardwareEnergyPolicy;
use App\Policies\HardwarePolicy;
use App\Policies\KeyCounterKeyboardPolicy;
use App\Policies\KeyCounterMousePolicy;
use App\Policies\NewsletterPolicy;
use App\Policies\PlatformPolicy;
use App\Policies\PrinterPolicy;
use App\Policies\SmartPlantPolicy;
use App\Policies\SmartPlantRegisterPolicy;
use App\Policies\TagPolicy;
use App\Policies\TechnologyPolicy;
use App\Policies\UserPolicy;
use App\Policies\WeatherStationPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

/**
 * Registro explícito de policies.
 *
 * ¿Por qué explícito y no por convención? Porque la convención de Laravel para
 * `App\Models\Hardware\HardwareDevice` busca `App\Policies\Hardware\HardwareDevicePolicy`,
 * y aquí las policies viven planas en `App\Policies\` con nombres de módulo
 * (`HardwarePolicy`, `ContentPolicy`, `AirFlightPolicy`…). Resultado: **todas
 * las policies de modelos que viven en subcarpetas nunca se han descubierto**,
 * así que ni Filament ni la API las han ejecutado jamás. Estaban escritas y no
 * se ejecutaban (auditoría A11, y es peor de lo que decía: no era que no se
 * llamaran, es que no existían para el framework).
 *
 * Cada línea de este mapa es una policy que a partir de ahora sí corre.
 */
class AuthServiceProvider extends ServiceProvider
{
    /**
     * Mapa modelo → policy.
     *
     * @var array<class-string, class-string>
     */
    private const POLICIES = [
        AirFlightAirPlane::class => AirFlightPolicy::class,
        AirFlightRoute::class => AirFlightRoutePolicy::class,
        ApiToken::class => ApiTokenPolicy::class,
        Category::class => CategoryPolicy::class,
        Content::class => ContentPolicy::class,
        Curriculum::class => CurriculumPolicy::class,
        Email::class => EmailPolicy::class,
        EnergySystem::class => EnergySystemPolicy::class,
        Gallery::class => GalleryPolicy::class,
        GalleryImage::class => GalleryPolicy::class,
        HardwareDevice::class => HardwarePolicy::class,
        HardwareEnergy::class => HardwareEnergyPolicy::class,
        Keyboard::class => KeyCounterKeyboardPolicy::class,
        Mouse::class => KeyCounterMousePolicy::class,
        Newsletter::class => NewsletterPolicy::class,
        Platform::class => PlatformPolicy::class,
        Printer::class => PrinterPolicy::class,
        PrinterStack::class => PrinterPolicy::class,
        SmartPlantPlant::class => SmartPlantPolicy::class,
        SmartPlantRegister::class => SmartPlantRegisterPolicy::class,
        Tag::class => TagPolicy::class,
        Technology::class => TechnologyPolicy::class,
        User::class => UserPolicy::class,
    ];

    /**
     * Catálogos globales del sistema. Comparten policy por la misma razón que
     * los sensores: no tienen dueño y sólo los toca un administrador.
     *
     * Ninguno tenía policy, y un modelo sin policy es un modelo sin
     * restricciones: Filament autorizaba todas las acciones a cualquiera con
     * acceso al panel, rol `Editor` incluido (AR-SEC-01).
     *
     * @var array<int, class-string>
     */
    private const CATALOG_MODELS = [
        CurriculumAvailableRepositoryType::class,
        FileType::class,
        HardwareAvailableComponent::class,
        HardwareType::class,
        PrinterAvailableType::class,
    ];

    /**
     * Modelos de sensores de la estación meteorológica. Todos comparten la
     * misma policy porque comparten dueño y criterio: se leen en abierto y
     * sólo un administrador los toca a mano.
     *
     * @var array<int, class-string>
     */
    private const WEATHER_STATION_MODELS = [
        AirQuality::class,
        Eco2::class,
        Humidity::class,
        Light::class,
        Lightning::class,
        Pressure::class,
        Rain::class,
        Temperature::class,
        Tvoc::class,
        Wind::class,
        WindDirection::class,
    ];

    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        foreach (self::POLICIES as $model => $policy) {
            Gate::policy($model, $policy);
        }

        foreach (self::WEATHER_STATION_MODELS as $model) {
            Gate::policy($model, WeatherStationPolicy::class);
        }

        foreach (self::CATALOG_MODELS as $model) {
            Gate::policy($model, AdminCatalogPolicy::class);
        }

        Gate::define('viewWebSocketsDashboard', function ($user = null) {
            return $user && $user->isAdmin();
        });
    }
}
