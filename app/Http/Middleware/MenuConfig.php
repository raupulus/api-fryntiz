<?php

namespace App\Http\Middleware;

use App\Models\Content\ContentAvailableType;
use App\Models\Platform;
use Closure;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Str;
use JeroenNoten\LaravelAdminLte\Events\BuildingMenu;

/**
 * Class MenuConfig
 *
 * Este middleware prepara el menú de la aplicación según el role del usuario
 *
 * @package App\Http\Middleware
 */
class MenuConfig
{
    public function handle($request, Closure $next)
    {

        $contentMenu = config('adminlte_extra.menu.content');

        // TODO -> Añadir esto a caché de 1 hora
        // Intentando generar menú dinámico

        foreach (Platform::all() as $platform) {
            $newPlatformMenu = [
                'text' => Str::limit($platform->title, 22, '...'),
                'icon' => 'fas fa-fw fa-globe',
                'submenu' => [
                    [
                        'text' => 'Listado',
                        'icon' => 'fas fa-eye',
                        'url' => route('dashboard.content.create', $platform->id),

                        // TODO: Todas las entradas con errores de validación al comprobar autoría
                        //'label' => 4,
                        //'label_color' => 'warning',
                    ],
                    [
                        'text' => 'Nuevo',
                        'icon' => 'fas fa-plus',
                        'url' => route('dashboard.content.create', $platform->id),

                        // TODO: Dinamizar con las entradas nuevas sin publicar
                        //'label' => 7,
                        //'label_color' => 'success',
                    ]
                ]
            ];

            $contentMenu[] = $newPlatformMenu;
        }

        $tmp = config('adminlte_extra.menu.tmp');

        $menus = array_merge($contentMenu, $tmp);

        Event::listen(BuildingMenu::class, function (BuildingMenu $event) use ($menus) {

            foreach ($menus as $item) {
                $event->menu->add($item);
            }

        });


        return $next($request);
    }
}
