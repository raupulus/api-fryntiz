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
        $contentCreateSubmenu = [
            'text' => 'Nuevo',
            'icon' => 'fas fa-fw fa-users',
            'submenu' => []
        ];



        /**
        foreach (Platform::all() as $platform) {
            $contentCreateSubmenu['submenu'][] = [
                'text' => Str::limit($platform->title, 22, '...'),
                'url' => route('dashboard.content.create', $platform->id),
                'icon' => 'fas fa-plus',
            ];
        }
         */


        // TODO -> Añadir esto a caché de 1 hora
        // Intentando generar menú dinámico

        $contentTypes = ContentAvailableType::all();

        foreach (Platform::all() as $platform) {
            $newPlatformMenu = [
                'text' => Str::limit($platform->title, 22, '...'),
                'icon' => 'fas fa-fw fa-users',
                'submenu' => []
            ];


            ## Menú para Crear
            $createMenu = [
                'text' => 'Nuevo',
                'icon' => 'fas fa-plus',
                'submenu' => []
            ];

            ## Cada tipo de contenido
            foreach ($contentTypes as $contentType) {
                $createMenu['submenu'][] = [
                    'text' => Str::limit($contentType->name, 22, '...'),
                    'url' => route('dashboard.content.create', $platform->id),
                    'icon' => 'fas fa-plus',
                ];
            }

            $newPlatformMenu['submenu'][] = $createMenu;

            $contentMenu[] = $newPlatformMenu;
        }






        //$contentMenu[] = $contentCreateSubmenu;



        $tmp = config('adminlte_extra.menu.tmp');

        $menus = array_merge($contentMenu, $tmp);


        Event::listen(BuildingMenu::class, function (BuildingMenu $event) use ($menus) {

            foreach ($menus as $item) {
                $event->menu->add($item);
            }
            //$event->menu->add($contentMenu);
        });




        /*


        $contentRoutes = \App\Models\Platform::pluck('id')->map(function
        ($platformId) {
            return route('dashboard.content.create', $platformId);
        })->toArray();

        $menu['content'] = $contentRoutes;
*/


        return $next($request);
    }
}
