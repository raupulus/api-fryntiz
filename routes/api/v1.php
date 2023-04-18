<?php

use App\Http\Controllers\Api\Auth\V1\LoginController;
use App\Http\Controllers\Api\Auth\V1\RegisterController;
use App\Http\Controllers\Api\User\V1\UserController;
use App\Models\CV\Curriculum;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

######################################################
##                   Autenticación
######################################################
Route::group(['prefix' => 'v1/auth'], function () {
    ## Ruta para hacer login.
    Route::post('/login', [LoginController::class, 'login'])
        //->middleware(\Illuminate\Session\Middleware\StartSession::class)
        ->name('api.v1.auth.login');

    ## Ruta para crear un nuevo registro de usuario.
    Route::post('/signup', [RegisterController::class, 'create'])
        //->middleware(\Illuminate\Session\Middleware\StartSession::class)
        ->name('api.v1.auth.signup');

    ## Grupo de rutas protegidos por token de autenticación.
    Route::group(['middleware' => 'auth:sanctum'], function () {
        ## Cierra la sesión de un usuario e invalida su token actual.
        Route::post('/logout', [LoginController::class, 'logout'])
            ->name('api.v1.auth.logout');

        ## Cierra la sesión de un usuario e invalida su token actual.
        Route::post('/delete-account', [RegisterController::class, 'destroy'])
            ->name('api.v1.auth.delete_account');
    });
});

######################################################
##                   Autenticación
######################################################

Route::group(['prefix' => 'v1/contact', 'middleware' => ['cors']], function () {
    ## Envía un formulario.
    Route::post('/send', [\App\Http\Controllers\EmailController::class, 'send'])
        ->name('api.v1.contact.send');
});


######################################################
##                   Usuarios
######################################################
Route::group(['prefix' => 'v1/user', 'middleware' => 'auth:sanctum'], function () {
    ## Devuelve todos los usuarios.
    Route::get('/index', [UserController::class, 'index'])
        ->name('api.v1.user.index');

    ## Devuelve un usuario en específico.
    Route::post('/show', [UserController::class, 'show'])
        ->name('api.v1.user.show');

    ## Crear un nuevo usuario.
    Route::post('/create', [UserController::class, 'create'])
        ->name('api.v1.user.create');

    ## Actualizar un usuario.
    Route::put('/update', [UserController::class, 'update'])
        ->name('api.v1.user.update');

    ## Eliminar un usuario.
    Route::delete('/delete', [UserController::class, 'destroy'])
        ->name('api.v1.user.delete');
});

######################################################
##                   Plataformas
######################################################
Route::group(['prefix' => 'v1/platform', 'middleware' => ['cors']], function () {
    Route::post('{slug}/info', function () {
        return response()->json([
            'platforms' => [
                'title' => 'Mis Plataformas',
                'description' => 'Listado de plataformas',
                'elements' => [
                    [
                        'slug' => 'api-fryntiz',
                        'name' => 'My Api',
                        'url' => 'https://api.fryntiz.dev',
                        'icon' => ''

                    ],
                    [
                        'slug' => 'curriculum-vitae',
                        'name' => 'Curriculum',
                        'url' => 'https://curriculum.fryntiz.es',
                        'icon' => ''

                    ],
                    [
                        'slug' => 'la-guia-linux',
                        'name' => 'La Guía Linux',
                        'url' => 'https://laguialinux.es',
                        'icon' => ''

                    ],
                ]
            ],
            'technologies' => [
                'vue',
                'tailwind'
            ],
            'resources' => [
                'title' => 'Recursos',
                'description' => 'Listado de recursos',
                'elements' => [
                    [
                        'name' => 'Gitlab',
                        'url' => 'https://gitlab.com/fryntiz/www.fryntiz.es',
                        'icon' => ''

                    ],
                    [
                        'name' => 'Github',
                        'url' => 'https://github.com/fryntiz/www.fryntiz.es',
                        'icon' => ''

                    ],
                ]
            ],
            'pages' => [
                [
                    'title' => 'Link1',
                    'url' => 'https://www.fryntiz.es',
                ],
                [
                    'title' => 'Link2',
                    'url' => 'https://www.fryntiz.es',
                ],
            ],
            'message' => 'Hola soy un mensaje de prueba',
            'name' => 'Fryntiz',
            'version' => 1.0,
            'author' => 'Raúl Caro Pastorino',
        ]);
    });


    Route::post('{slug}/projects', function (Request $request, $slug) {
        $category = $request->get('category');
        $search = $request->get('search');


        return JsonHelper::success([
            'elements' => [
                [
                    'id' => 1,
                    'title' => "Título? Poner sobre imagen?",
                    'description' => "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Vivamus quis porta dui. Ut eu iaculis massa. Sed ornare ligula lacus, quis iaculis dui porta volutpat. In sit amet posuere magna..",
                    'image' => "https://source.unsplash.com/collection/1346951/1000x500?sig=1",
                    'tags' => ["smart plant", "solar"],
                    'categories' => ['Laravel', 'PHP'],
                    'links' => [
                        [
                            'type' => "twitter",
                            'name' => "Twitter",
                            'url' => "https://twitter.com/xxx",
                        ],

                        [
                            'type' => "gitlab",
                            'name' => "Gitlab",
                            'url' => "https://gitlab.com/xxx",
                        ],

                        [
                            'type' => "web",
                            'name' => "Web",
                            'url' => "https://web.com/xxx",
                        ],
                    ],
                ],

                [
                    'id' => 2,
                    'title' => "Título2? Poner sobre imagen?",
                    'description' => "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Vivamus quis porta dui. Ut eu iaculis massa. Sed ornare ligula lacus, quis iaculis dui porta volutpat. In sit amet posuere magna..",
                    'image' => "https://source.unsplash.com/collection/1346951/1000x500?sig=1",
                    'tags' => ["smart plant", "solar"],
                    'categories' => ['Laravel', 'PHP'],
                    'links' => [
                        [
                            'type' => "twitter",
                            'name' => "Twitter",
                            'url' => "https://twitter.com/xxx",
                        ],

                        [
                            'type' => "gitlab",
                            'name' => "Gitlab",
                            'url' => "https://gitlab.com/xxx",
                        ],

                        [
                            'type' => "web",
                            'name' => "Web",
                            'url' => "https://web.com/xxx",
                        ],
                    ],
                ]
            ],
            'categories' => [
                [
                    'slug' => "all",
                    'name' => "Todos",
                ],

                [
                    'slug' => "laravel",
                    'name' => "Laravel",
                ],

                [
                    'slug' => "php",
                    'name' => "PHP",
                ],

                [
                    'slug' => "python",
                    'name' => "Python",
                ],

                [
                    'slug' => "vuejs",
                    'name' => "VueJS",
                ],
                [
                    'slug' => "javascript",
                    'name' => "Javascript",
                ],
                [
                    'slug' => "Raspberry",
                    'name' => "raspberry",
                ],
                [
                    'slug' => "arduino",
                    'name' => "Arduino",
                ],
            ],
            'currentCategorySlug' => $category ?? 'all',
        ]);


        $cv = Curriculum::where('user_id', 2)->where('is_default', 1)->first();

        if (!$cv) {
            return JsonHelper::success([]);
        }

        $projects = $cv->projects(); //->where('slug', $slug);

        if ($category) {
            $projects->where('category', $category);
        }

        // TODO → Buscar también por cadena en tags y descripción
        if ($search) {
            $projects->where('name', 'like', "%$search%");
        }

        return JsonHelper::success([
            'total' => $cv->projects()->count(),
            'results' => $projects->count(),
            'elements' => $projects->get()
        ]);
    });
});

/**
 * Ruta por defecto cuando no se encuentra una petición.
 */
Route::fallback(function () {
    return response()->json(['message' => 'Page Not Found'], 404);
});
