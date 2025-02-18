<?php

use App\Http\Controllers\Dashboard\CategoryController;
use App\Http\Controllers\Dashboard\Content\ContentController;
use App\Http\Controllers\Dashboard\CurriculumController;
use App\Http\Controllers\Dashboard\Cv\CurriculumAcademicComplementaryController;
use App\Http\Controllers\Dashboard\Cv\CurriculumAcademicComplementaryOnlineController;
use App\Http\Controllers\Dashboard\Cv\CurriculumAcademicTrainingController;
use App\Http\Controllers\Dashboard\Cv\CurriculumAvailableRepositoryTypeController;
use App\Http\Controllers\Dashboard\Cv\CurriculumCollaborationController;
use App\Http\Controllers\Dashboard\Cv\CurriculumExperienceAccreditedController;
use App\Http\Controllers\Dashboard\Cv\CurriculumExperienceAdditionalController;
use App\Http\Controllers\Dashboard\Cv\CurriculumExperienceNoAccreditedController;
use App\Http\Controllers\Dashboard\Cv\CurriculumExperienceOtherController;
use App\Http\Controllers\Dashboard\Cv\CurriculumExperienceSelfEmployedController;
use App\Http\Controllers\Dashboard\Cv\CurriculumHobbyController;
use App\Http\Controllers\Dashboard\Cv\CurriculumJobController;
use App\Http\Controllers\Dashboard\Cv\CurriculumProjectController;
use App\Http\Controllers\Dashboard\Cv\CurriculumRepositoryController;
use App\Http\Controllers\Dashboard\Cv\CurriculumServiceController;
use App\Http\Controllers\Dashboard\Cv\CurriculumSkillController;
use App\Http\Controllers\Dashboard\Hardware\HardwareDeviceController;
use App\Http\Controllers\Dashboard\Hardware\HardwareEnergyController;
use App\Http\Controllers\Dashboard\LanguageController;
use App\Http\Controllers\Dashboard\PlatformController;
use App\Http\Controllers\Dashboard\TagController;
use App\Http\Controllers\Dashboard\Users\UserController;
use App\Http\Controllers\Dashboard\EmailController;
use Illuminate\Support\Facades\Route;
use \App\Http\Controllers\Dashboard\Content\ContentPageController;
use \App\Http\Controllers\Dashboard\AdminLteController;
use \App\Http\Controllers\Dashboard\TechnologyController;


############################################################
##                      Dashboard                         ##
############################################################
Route::group(['prefix' => '/', 'middleware' => ['auth', 'verified']], function () {
    Route::get('/', function () {
        return view('dashboard.index');
    })->name('dashboard.index');


    ## Listado con todos los tipos de archivos que hay en la plataforma.
    Route::get('/app/file-types', [AdminLteController::class, 'fileTypesIndex'])
        ->name('dashboard.app.file_types.index');

    Route::post('/app/file-types/update/icon/{fileType}', [AdminLteController::class, 'fileTypesIconUpdate'])
        ->name('dashboard.app.file_types.update.icon');
});

############################################################
##                      Emails                            ##
############################################################
Route::group(['prefix' => '/emails', 'middleware' => ['auth', 'verified']],
    function () {

        Route::get('/index', [EmailController::class, 'index'])
            ->name('dashboard.email.index');


        Route::group(['prefix' => '/ajax'], function () {
            Route::post('/table/get', [EmailController::class, 'ajaxTableGetQuery'])
                ->name('dashboard.email.ajax.table.get');

            ## Acciones sobre datos de la tabla [update, create...]
            Route::match(['put', 'patch', 'post'], '/table/action', [EmailController::class, 'ajaxTableActions'])
                ->name('dashboard.email.ajax.table.actions');
        });

    });

############################################################
##                      Energía                           ##
############################################################
Route::group(['prefix' => '/energy', 'middleware' => ['auth', 'verified']],
    function () {

        Route::get('/index', [HardwareEnergyController::class, 'index'])
            ->name('dashboard.energy.index');

        Route::get('/create', [HardwareEnergyController::class, 'create'])
            ->name('dashboard.energy.create');

        Route::post('/store', [HardwareEnergyController::class, 'store'])
            ->name('dashboard.energy.store');

        Route::get('/{model}/edit', [HardwareEnergyController::class, 'edit'])
            ->name('dashboard.energy.edit');

        Route::match(['post', 'put', 'patch'], '/update/{model?}', [HardwareEnergyController::class, 'update'])
            ->name('dashboard.energy.update');

        Route::match(['POST', 'DELETE'], '/destroy/{model?}', [HardwareEnergyController::class, 'destroy'])
            ->name('dashboard.energy.destroy');
    });

############################################################
##                    Plataformas                         ##
############################################################
Route::group(['prefix' => '/platform', 'middleware' => ['auth', 'verified']],
    function () {
        Route::get('/index', [PlatformController::class, 'index'])
            ->name('dashboard.platform.index');
        Route::get('/create', [PlatformController::class, 'create'])
            ->name('dashboard.platform.create');
        Route::post('/store', [PlatformController::class, 'store'])
            ->name('dashboard.platform.store');
        Route::get('/{model}/edit', [PlatformController::class, 'edit'])
            ->name('dashboard.platform.edit');
        Route::match(['post', 'put', 'patch'], '/update/{model?}', [PlatformController::class, 'update'])
            ->name('dashboard.platform.update');
        Route::match(['POST', 'DELETE'], '/destroy/{model?}', [PlatformController::class, 'destroy'])
            ->name('dashboard.platform.destroy');

        Route::group(['prefix' => '/ajax'], function () {
            Route::post('/table/get', [PlatformController::class, 'ajaxTableGetQuery'])
                ->name('dashboard.platform.ajax.table.get');

            ## Acciones sobre datos de la tabla [update, create...]
            Route::match(['put', 'patch', 'post'], '/table/action', [PlatformController::class, 'ajaxTableActions'])
                ->name('dashboard.platform.ajax.table.actions');
        });
    });

############################################################
##                     Etiquetas                          ##
############################################################
Route::group(['prefix' => '/tag', 'middleware' => ['auth', 'verified']],
    function () {
        Route::get('/index', [TagController::class, 'index'])
            ->name('dashboard.tag.index');
        Route::get('/create', [TagController::class, 'create'])
            ->name('dashboard.tag.create');
        Route::post('/store', [TagController::class, 'store'])
            ->name('dashboard.tag.store');
        Route::get('/{tag}/edit', [TagController::class, 'edit'])
            ->name('dashboard.tag.edit');
        Route::match(['post', 'put', 'patch'], '/update/{tag?}', [TagController::class, 'update'])
            ->name('dashboard.tag.update');
        Route::match(['POST', 'DELETE'], '/destroy/{tag?}', [TagController::class, 'destroy'])
            ->name('dashboard.tag.destroy');

        Route::group(['prefix' => '/ajax'], function () {

            /*
            Route::get('/get/all', [TagController::class, 'ajaxGetTags'])
                ->name('dashboard.tag.ajax.get.all');
            */

            Route::post('/table/get', [TagController::class, 'ajaxTableGetQuery'])
                ->name('dashboard.tag.ajax.table.get');

            ## Acciones sobre datos de la tabla [update, create...]
            Route::match(['put', 'patch', 'post'], '/table/action', [TagController::class, 'ajaxTableActions'])
                ->name('dashboard.tag.ajax.table.actions');
        });
    });

############################################################
##                     Categorías                         ##
############################################################
Route::group(['prefix' => '/category', 'middleware' => ['auth', 'verified']],
    function () {
        Route::get('/index', [CategoryController::class, 'index'])
            ->name('dashboard.category.index');
        Route::get('/create', [CategoryController::class, 'create'])
            ->name('dashboard.category.create');
        Route::post('/store', [CategoryController::class, 'store'])
            ->name('dashboard.category.store');
        Route::get('/{model}/edit', [CategoryController::class, 'edit'])
            ->name('dashboard.category.edit');
        Route::match(['post', 'put', 'patch'], '/update/{category?}', [CategoryController::class, 'update'])
            ->name('dashboard.category.update');
        Route::match(['POST', 'DELETE'], '/destroy/{category?}', [CategoryController::class, 'destroy'])
            ->name('dashboard.category.destroy');

        Route::group(['prefix' => '/ajax'], function () {

            /*
            Route::get('/get/all', [TagController::class, 'ajaxGetTags'])
                ->name('dashboard.tag.ajax.get.all');
            */

            Route::get('/html/get/{category}/subcategories/{content?}', [CategoryController::class, 'ajaxGetHtmlSubcategories'])
                ->name('dashboard.category.ajax.html.subcategories');

            Route::post('/table/get', [CategoryController::class, 'ajaxTableGetQuery'])
                ->name('dashboard.category.ajax.table.get');

            ## Acciones sobre datos de la tabla [update, create...]
            Route::match(['put', 'patch', 'post'], '/table/action', [CategoryController::class, 'ajaxTableActions'])
                ->name('dashboard.category.ajax.table.actions');
        });
    });

############################################################
##                     Tecnologías                        ##
############################################################
Route::group(['prefix' => '/technology', 'middleware' => ['auth', 'verified']],
    function () {
        Route::get('/index', [TechnologyController::class, 'index'])
            ->name('dashboard.technology.index');
        Route::get('/create', [TechnologyController::class, 'create'])
            ->name('dashboard.technology.create');
        Route::post('/store', [TechnologyController::class, 'store'])
            ->name('dashboard.technology.store');
        Route::get('/{technology}/edit', [TechnologyController::class, 'edit'])
            ->name('dashboard.technology.edit');
        Route::match(['post', 'put', 'patch'], '/update/{technology?}', [TechnologyController::class, 'update'])
            ->name('dashboard.technology.update');
        Route::match(['POST', 'DELETE'], '/destroy/{technology?}', [TechnologyController::class, 'destroy'])
            ->name('dashboard.technology.destroy');

        Route::group(['prefix' => '/ajax'], function () {
            Route::post('/table/get', [TechnologyController::class, 'ajaxTableGetQuery'])
                ->name('dashboard.technology.ajax.table.get');

            ## Acciones sobre datos de la tabla [update, create...]
            Route::match(['put', 'patch', 'post'], '/table/action', [TechnologyController::class, 'ajaxTableActions'])
                ->name('dashboard.technology.ajax.table.actions');
        });
    });

############################################################
##                      Usuarios                          ##
############################################################
Route::group(['prefix' => '/users', 'middleware' => ['auth', 'verified']],
    function () {
        Route::get('/index', [UserController::class, 'index'])
            ->name('dashboard.users.index');
        Route::get('/show/{user?}', [UserController::class, 'show'])
            ->name('dashboard.users.show');
        Route::get('/create', [UserController::class, 'create'])
            ->name('dashboard.users.create');
        Route::post('/store', [UserController::class, 'store'])
            ->name('dashboard.users.store');
        Route::get('/{user}/edit', [UserController::class, 'edit'])
            ->name('dashboard.users.edit');
        Route::match(['put', 'patch'], '/update/{device}', [UserController::class, 'update'])
            ->name('dashboard.users.update');
        Route::post('/destroy/{user}', [UserController::class, 'destroy'])
            ->name('dashboard.users.destroy');
    });

############################################################
##                      Hardware                          ##
############################################################
Route::group(['prefix' => '/hardware', 'middleware' => ['auth', 'verified']],
    function () {
        Route::group(['prefix' => '/device', 'middleware' => ['auth', 'verified']],
            function () {
                Route::get('/index', [HardwareDeviceController::class, 'index'])
                    ->name('dashboard.hardware.device.index');
                Route::get('/create', [HardwareDeviceController::class, 'create'])
                    ->name('dashboard.hardware.device.create');
                Route::post('/store', [HardwareDeviceController::class, 'store'])
                    ->name('dashboard.hardware.device.store');
                Route::get('/{device}/edit', [HardwareDeviceController::class, 'edit'])
                    ->name('dashboard.hardware.device.edit');
                Route::match(['put', 'patch'], '/update/{device}', [HardwareDeviceController::class, 'update'])
                    ->name('dashboard.hardware.device.update');
                Route::post('/destroy/{device}', [HardwareDeviceController::class, 'destroy'])
                    ->name('dashboard.hardware.device.destroy');
            });
    });

############################################################
##                      Contenido                         ##
############################################################
Route::group(['prefix' => '/content', 'middleware' => ['auth', 'verified']],
    function () {
        Route::get('/index/{platform?}/{slug?}', [ContentController::class, 'index'])
            ->name('dashboard.content.index');
        Route::get('/{platform?}/create', [ContentController::class, 'create'])
            ->name('dashboard.content.create');
        Route::post('/store', [ContentController::class, 'store'])
            ->name('dashboard.content.store');
        Route::get('/{model}/edit', [ContentController::class, 'edit'])
            ->middleware('can:edit,model')
            ->name('dashboard.content.edit');
        Route::match(['put', 'patch'], '/update/{content}', [ContentController::class, 'update'])
            ->name('dashboard.content.update');
        Route::match(['post', 'delete'], '/destroy/{content?}', [ContentController::class, 'destroy'])
            ->name('dashboard.content.destroy');

        ## Crea una nueva página
        Route::post('/{content}/add/page', [ContentController::class, 'addPage'])
            ->name('dashboard.content.add.page');

        ## Guarda SEO de un contenido
        Route::match(['put', 'patch'], '/seo/store/{content}', [ContentController::class, 'seoStore'])
            ->name('dashboard.content.seo.store');

        ## Guarda metadatos de un contenido
        Route::match(['put', 'patch'], '/metadata/store/{content}', [ContentController::class, 'metadataStore'])
            ->name('dashboard.content.metadata.store');

        ## Guarda Metadatos de un contenido
        Route::match(['put', 'patch'], '/metadata/store/{content}', [ContentController::class, 'metadataStore'])
            ->name('dashboard.content.metadata.store');


        ## Ruta para previsualizar el contenido
        Route::get('/preview/full/{content}', [ContentController::class, 'preview'])
            ->name('dashboard.content.preview.full');

        ## Elimina una página
        Route::post('/delete/{page}/{content?}', [ContentPageController::class, 'safeDestroy'])
            ->name('dashboard.content.page.delete');



        Route::group(['prefix' => '/ajax'], function () {

            ## Devuelve información del contenido por plataforma (etiquetas, categorías, contenidos...)
            Route::match(['put', 'patch', 'post'], '/get/select/info/from/{platform}/platform', [ContentController::class, 'ajaxGetSelectInfoFromPlataform'])
                ->name('dashboard.content.ajax.get.select.info.from.platform');

            Route::match(['put', 'patch', 'post'], '/get/{platform}/content-related/filtered', [ContentController::class, 'ajaxGetContentRelatedFiltered'])
                ->name('dashboard.content.ajax.get.content.related.filtered');


            Route::post('/table/get', [ContentController::class, 'ajaxTableGetQuery'])
                ->name('dashboard.content.ajax.table.get');

            ## Acciones sobre datos de la tabla [update, create...]
            Route::match(['put', 'patch', 'post'], '/table/action', [ContentController::class, 'ajaxTableActions'])
                ->name('dashboard.content.ajax.table.actions');

            ## Crea una nueva etiqueta/s asociada al contenido
            Route::post('/tag/create', [ContentController::class, 'ajaxTagCreate'])
                ->name('dashboard.content.ajax.tag.create');

            ## Crea una nueva categoría/s asociada al contenido
            Route::post('/category/create', [ContentController::class, 'ajaxCategoryCreate'])
                ->name('dashboard.content.ajax.category.create');



            ## Sube un archivo asociado a un contenido
            Route::post('/upload/file/{contentModel?}', [ContentController::class, 'ajaxStoreFile'])
                ->name('dashboard.content.ajax.upload.file');

            ## Actualiza los metadatos de un archivo asociado a un contenido
            Route::patch('/update/metadata/file/{contentFile}/{file}', [ContentController::class, 'ajaxUpdateMetadataFile'])
                ->name('dashboard.content.ajax.update.metadata.file');

            ## Elimina un archivo asociado a un contenido
            Route::post('/upload/remove/file', [ContentController::class, 'ajaxRemoveFile'])
                ->name('dashboard.content.ajax.upload.remove.file');



            /******* Páginas ********/

            ## Crea una nueva página asociada al contenido
            Route::post('/page/create/{content}', [ContentController::class, 'ajaxPageCreate'])
                ->name('dashboard.content.ajax.page.create');

            ## Actualiza el contenido de una página existente
            Route::post('/page/{contentPage}/{contentType}/update', [ContentController::class, 'ajaxPageUpdate'])
                ->name('dashboard.content.ajax.page.update');

            ## Devuelve el contenido RAW de una página
            Route::get('/page/{contentPage}/get', [ContentController::class, 'ajaxPageGetContent'])
                    ->name('dashboard.content.ajax.page.get.content');

            ## Añade/Actualiza una imagen de una página
            Route::post('/page/upload/image/{contentPage}/update', [ContentPageController::class, 'ajaxUpdateImage'])
                ->name('dashboard.content.ajax.page.upload.image.update');

            ## Comprueba si es un slug válido para la página dentro de la plataforma
            Route::post('/page/check/slug/{page?}', [ContentPageController::class, 'ajaxCheckSlug'])
                ->name('dashboard.content.ajax.page.check.slug');

            ## Obtiene metadatos de una página externa
            Route::get('/external/page/get/metadata', [ContentPageController::class, 'ajaxGetUrlMetadata'])
                ->name('dashboard.content.ajax.external.page.get.metadata');
        });
    });







############################################################
##                       Idiomas                          ##
############################################################
Route::group(['prefix' => '/language', 'middleware' => ['auth', 'verified']],
    function () {
        Route::get('/index', [LanguageController::class, 'index'])
            ->name('dashboard.language.index');
        Route::get('/create', [LanguageController::class, 'create'])
            ->name('dashboard.language.create');
        Route::post('/store', [LanguageController::class, 'store'])
            ->name('dashboard.language.store');
        Route::get('/edit/{id}', [LanguageController::class, 'edit'])
            ->name('dashboard.language.edit');
        Route::post('/update/{id}', [LanguageController::class, 'update'])
            ->name('dashboard.language.update');
        Route::post('/destroy', [LanguageController::class, 'destroy'])
            ->name('dashboard.language.destroy');
    });

############################################################
##                     Curriculum                         ##
############################################################
// https://laravel.com/docs/8.x/controllers#actions-handled-by-resource-controller
Route::group(['prefix' => '/cv', 'middleware' => ['auth', 'verified']],
    function () {
        ## Curriculums
        Route::get('/index', [CurriculumController::class, 'index'])
            ->name('dashboard.cv.index');
        Route::get('/create', [CurriculumController::class, 'create'])
            ->name('dashboard.cv.create');
        Route::post('/store', [CurriculumController::class, 'store'])
            ->name('dashboard.cv.store');
        Route::get('/edit/{id}', [CurriculumController::class, 'edit'])
            ->name('dashboard.cv.edit');
        Route::post('/update/{id}', [CurriculumController::class, 'update'])
            ->name('dashboard.cv.update');
        Route::post('/destroy', [CurriculumController::class, 'destroy'])
            ->name('dashboard.cv.destroy');

        ## Tipos de repositorios disponibles.
        Route::group(['prefix' => '/repository-available-type'], function () {
            Route::get('/index',
                [CurriculumAvailableRepositoryTypeController::class, 'index'])
                ->name('dashboard.cv.repository_available_type.index');
            Route::get('/create',
                [CurriculumAvailableRepositoryTypeController::class, 'create'])
                ->name('dashboard.cv.repository_available_type.create');
            Route::get('/edit/{id}',
                [CurriculumAvailableRepositoryTypeController::class, 'edit'])
                ->name('dashboard.cv.repository_available_type.edit');
            Route::post('/store', [CurriculumAvailableRepositoryTypeController::class, 'store'])
                ->name('dashboard.cv.repository_available_type.store');
            Route::post('/update/{id}', [CurriculumAvailableRepositoryTypeController::class, 'update'])
                ->name('dashboard.cv.repository_available_type.update');
            Route::post('/destroy', [CurriculumAvailableRepositoryTypeController::class, 'destroy'])
                ->name('dashboard.cv.repository_available_type.destroy');
        });

        ## Repositorios
        Route::group(['prefix' => '/repository'], function () {
            Route::get('/index/{id}', [CurriculumRepositoryController::class, 'index'])
                ->name('dashboard.cv.repository.index');
            Route::get('/create', [CurriculumRepositoryController::class, 'create'])
                ->name('dashboard.cv.repository.create');
            Route::post('/store/{cv_id}', [CurriculumRepositoryController::class, 'store'])
                ->name('dashboard.cv.repository.store');
            Route::get('/edit/{id}', [CurriculumRepositoryController::class, 'edit'])
                ->name('dashboard.cv.repository.edit');
            Route::post('/update/{id}', [CurriculumRepositoryController::class, 'update'])
                ->name('dashboard.cv.repository.update');
            Route::post('/destroy/{id}', [CurriculumRepositoryController::class, 'destroy'])
                ->name('dashboard.cv.repository.destroy');
        });

        ## Servicios
        Route::group(['prefix' => '/service'], function () {
            Route::get('/index/{id}', [CurriculumServiceController::class, 'index'])
                ->name('dashboard.cv.service.index');
            Route::get('/create', [CurriculumServiceController::class, 'create'])
                ->name('dashboard.cv.service.create');
            Route::post('/store/{cv_id}', [CurriculumServiceController::class, 'store'])
                ->name('dashboard.cv.service.store');
            Route::get('/edit/{id}', [CurriculumServiceController::class, 'edit'])
                ->name('dashboard.cv.service.edit');
            Route::post('/update/{id}', [CurriculumServiceController::class, 'update'])
                ->name('dashboard.cv.service.update');
            Route::post('/destroy/{id}', [CurriculumServiceController::class, 'destroy'])
                ->name('dashboard.cv.service.destroy');
        });

        ## Formación Académica Complementaria
        Route::group(['prefix' => '/academic-complementary'], function () {
            Route::get('/index/{id}', [CurriculumAcademicComplementaryController::class, 'index'])
                ->name('dashboard.cv.academic_complementary.index');
            Route::get('/create', [CurriculumAcademicComplementaryController::class, 'create'])
                ->name('dashboard.cv.academic_complementary.create');
            Route::post('/store/{cv_id}', [CurriculumAcademicComplementaryController::class, 'store'])
                ->name('dashboard.cv.academic_complementary.store');
            Route::get('/edit/{id}', [CurriculumAcademicComplementaryController::class, 'edit'])
                ->name('dashboard.cv.academic_complementary.edit');
            Route::post('/update/{id}', [CurriculumAcademicComplementaryController::class, 'update'])
                ->name('dashboard.cv.academic_complementary.update');
            Route::post('/destroy/{id}', [CurriculumAcademicComplementaryController::class, 'destroy'])
                ->name('dashboard.cv.academic_complementary.destroy');
        });

        ## Formación Académica Online
        Route::group(['prefix' => '/academic-complementary-online'], function () {
            Route::get('/index/{id}', [CurriculumAcademicComplementaryOnlineController::class, 'index'])
                ->name('dashboard.cv.academic_complementary_online.index');
            Route::get('/create', [CurriculumAcademicComplementaryOnlineController::class, 'create'])
                ->name('dashboard.cv.academic_complementary_online.create');
            Route::post('/store/{cv_id}', [CurriculumAcademicComplementaryOnlineController::class, 'store'])
                ->name('dashboard.cv.academic_complementary_online.store');
            Route::get('/edit/{id}', [CurriculumAcademicComplementaryOnlineController::class, 'edit'])
                ->name('dashboard.cv.academic_complementary_online.edit');
            Route::post('/update/{id}', [CurriculumAcademicComplementaryOnlineController::class, 'update'])
                ->name('dashboard.cv.academic_complementary_online.update');
            Route::post('/destroy/{id}', [CurriculumAcademicComplementaryOnlineController::class, 'destroy'])
                ->name('dashboard.cv.academic_complementary_online.destroy');
        });

        ## Formación Académica
        Route::group(['prefix' => '/academic-training'], function () {
            Route::get('/index/{id}', [CurriculumAcademicTrainingController::class,
                'index'])
                ->name('dashboard.cv.academic_training.index');
            Route::get('/create', [CurriculumAcademicTrainingController::class, 'create'])
                ->name('dashboard.cv.academic_training.create');
            Route::post('/store/{cv_id}', [CurriculumAcademicTrainingController::class, 'store'])
                ->name('dashboard.cv.academic_training.store');
            Route::get('/edit/{id}', [CurriculumAcademicTrainingController::class, 'edit'])
                ->name('dashboard.cv.academic_training.edit');
            Route::post('/update/{id}', [CurriculumAcademicTrainingController::class, 'update'])
                ->name('dashboard.cv.academic_training.update');
            Route::post('/destroy/{id}', [CurriculumAcademicTrainingController::class, 'destroy'])
                ->name('dashboard.cv.academic_training.destroy');
        });

        ## Colaboraciones
        Route::group(['prefix' => '/collaboration'], function () {
            Route::get('/index/{id}', [CurriculumCollaborationController::class, 'index'])
                ->name('dashboard.cv.collaboration.index');
            Route::get('/create', [CurriculumCollaborationController::class, 'create'])
                ->name('dashboard.cv.collaboration.create');
            Route::post('/store/{cv_id}', [CurriculumCollaborationController::class, 'store'])
                ->name('dashboard.cv.collaboration.store');
            Route::get('/edit/{id}', [CurriculumCollaborationController::class, 'edit'])
                ->name('dashboard.cv.collaboration.edit');
            Route::post('/update/{id}', [CurriculumCollaborationController::class, 'update'])
                ->name('dashboard.cv.collaboration.update');
            Route::post('/destroy/{id}', [CurriculumCollaborationController::class, 'destroy'])
                ->name('dashboard.cv.collaboration.destroy');
        });

        ## Experiencia Laboral Acreditada
        Route::group(['prefix' => '/experience-accredited'], function () {
            Route::get('/index/{id}', [CurriculumExperienceAccreditedController::class, 'index'])
                ->name('dashboard.cv.experience_accredited.index');
            Route::get('/create', [CurriculumExperienceAccreditedController::class, 'create'])
                ->name('dashboard.cv.experience_accredited.create');
            Route::post('/store/{cv_id}', [CurriculumExperienceAccreditedController::class, 'store'])
                ->name('dashboard.cv.experience_accredited.store');
            Route::get('/edit/{id}', [CurriculumExperienceAccreditedController::class, 'edit'])
                ->name('dashboard.cv.experience_accredited.edit');
            Route::post('/update/{id}', [CurriculumExperienceAccreditedController::class, 'update'])
                ->name('dashboard.cv.experience_accredited.update');
            Route::post('/destroy/{id}', [CurriculumExperienceAccreditedController::class, 'destroy'])
                ->name('dashboard.cv.experience_accredited.destroy');
        });

        ## Experiencia Laboral Adicional
        Route::group(['prefix' => '/experience-additional'], function () {
            Route::get('/index/{id}', [CurriculumExperienceAdditionalController::class, 'index'])
                ->name('dashboard.cv.experience_additional.index');
            Route::get('/create', [CurriculumExperienceAdditionalController::class, 'create'])
                ->name('dashboard.cv.experience_additional.create');
            Route::post('/store/{cv_id}', [CurriculumExperienceAdditionalController::class, 'store'])
                ->name('dashboard.cv.experience_additional.store');
            Route::get('/edit/{id}', [CurriculumExperienceAdditionalController::class, 'edit'])
                ->name('dashboard.cv.experience_additional.edit');
            Route::post('/update/{id}', [CurriculumExperienceAdditionalController::class, 'update'])
                ->name('dashboard.cv.experience_additional.update');
            Route::post('/destroy/{id}', [CurriculumExperienceAdditionalController::class, 'destroy'])
                ->name('dashboard.cv.experience_additional.destroy');
        });

        ## Experiencia Laboral No Acreditada
        Route::group(['prefix' => '/experience-no-accredited'], function () {
            Route::get('/index/{id}', [CurriculumExperienceNoAccreditedController::class, 'index'])
                ->name('dashboard.cv.experience_no_accredited.index');
            Route::get('/create', [CurriculumExperienceNoAccreditedController::class, 'create'])
                ->name('dashboard.cv.experience_no_accredited.create');
            Route::post('/store/{cv_id}', [CurriculumExperienceNoAccreditedController::class, 'store'])
                ->name('dashboard.cv.experience_no_accredited.store');
            Route::get('/edit/{id}', [CurriculumExperienceNoAccreditedController::class, 'edit'])
                ->name('dashboard.cv.experience_no_accredited.edit');
            Route::post('/update/{id}', [CurriculumExperienceNoAccreditedController::class, 'update'])
                ->name('dashboard.cv.experience_no_accredited.update');
            Route::post('/destroy/{id}', [CurriculumExperienceNoAccreditedController::class, 'destroy'])
                ->name('dashboard.cv.experience_no_accredited.destroy');
        });

        ## Experiencia Laboral Otros
        Route::group(['prefix' => '/experience-other'], function () {
            Route::get('/index/{id}', [CurriculumExperienceOtherController::class, 'index'])
                ->name('dashboard.cv.experience_other.index');
            Route::get('/create', [CurriculumExperienceOtherController::class, 'create'])
                ->name('dashboard.cv.experience_other.create');
            Route::post('/store/{cv_id}', [CurriculumExperienceOtherController::class, 'store'])
                ->name('dashboard.cv.experience_other.store');
            Route::get('/edit/{id}', [CurriculumExperienceOtherController::class, 'edit'])
                ->name('dashboard.cv.experience_other.edit');
            Route::post('/update/{id}', [CurriculumExperienceOtherController::class, 'update'])
                ->name('dashboard.cv.experience_other.update');
            Route::post('/destroy/{id}', [CurriculumExperienceOtherController::class, 'destroy'])
                ->name('dashboard.cv.experience_other.destroy');
        });

        ## Experiencia Laboral Autoempleado (Autónomo o freelance)
        Route::group(['prefix' => '/experience-selfemployed'], function () {
            Route::get('/index/{id}', [CurriculumExperienceSelfEmployedController::class, 'index'])
                ->name('dashboard.cv.experience_selfemployed.index');
            Route::get('/create', [CurriculumExperienceSelfEmployedController::class, 'create'])
                ->name('dashboard.cv.experience_selfemployed.create');
            Route::post('/store/{cv_id}', [CurriculumExperienceSelfEmployedController::class, 'store'])
                ->name('dashboard.cv.experience_selfemployed.store');
            Route::get('/edit/{id}', [CurriculumExperienceSelfEmployedController::class, 'edit'])
                ->name('dashboard.cv.experience_selfemployed.edit');
            Route::post('/update/{id}', [CurriculumExperienceSelfEmployedController::class, 'update'])
                ->name('dashboard.cv.experience_selfemployed.update');
            Route::post('/destroy/{id}', [CurriculumExperienceSelfEmployedController::class, 'destroy'])
                ->name('dashboard.cv.experience_selfemployed.destroy');
        });

        ## Aficciones
        Route::group(['prefix' => '/hobby'], function () {
            Route::get('/index/{id}', [CurriculumHobbyController::class, 'index'])
                ->name('dashboard.cv.hobby.index');
            Route::get('/create', [CurriculumHobbyController::class, 'create'])
                ->name('dashboard.cv.hobby.create');
            Route::post('/store/{cv_id}', [CurriculumHobbyController::class, 'store'])
                ->name('dashboard.cv.hobby.store');
            Route::get('/edit/{id}', [CurriculumHobbyController::class, 'edit'])
                ->name('dashboard.cv.hobby.edit');
            Route::post('/update/{id}', [CurriculumHobbyController::class, 'update'])
                ->name('dashboard.cv.hobby.update');
            Route::post('/destroy/{id}', [CurriculumHobbyController::class, 'destroy'])
                ->name('dashboard.cv.hobby.destroy');
        });

        ## Trabajos
        Route::group(['prefix' => '/job'], function () {
            Route::get('/index/{id}', [CurriculumJobController::class, 'index'])
                ->name('dashboard.cv.job.index');
            Route::get('/create', [CurriculumJobController::class, 'create'])
                ->name('dashboard.cv.job.create');
            Route::post('/store/{cv_id}', [CurriculumJobController::class, 'store'])
                ->name('dashboard.cv.job.store');
            Route::get('/edit/{id}', [CurriculumJobController::class, 'edit'])
                ->name('dashboard.cv.job.edit');
            Route::post('/update/{id}', [CurriculumJobController::class, 'update'])
                ->name('dashboard.cv.job.update');
            Route::post('/destroy/{id}', [CurriculumJobController::class, 'destroy'])
                ->name('dashboard.cv.job.destroy');
        });

        ## Proyectos
        Route::group(['prefix' => '/project'], function () {
            Route::get('/index/{id}', [CurriculumProjectController::class, 'index'])
                ->name('dashboard.cv.project.index');
            Route::get('/create', [CurriculumProjectController::class, 'create'])
                ->name('dashboard.cv.project.create');
            Route::post('/store/{cv_id}', [CurriculumProjectController::class, 'store'])
                ->name('dashboard.cv.project.store');
            Route::get('/edit/{id}', [CurriculumProjectController::class, 'edit'])
                ->name('dashboard.cv.project.edit');
            Route::post('/update/{id}', [CurriculumProjectController::class, 'update'])
                ->name('dashboard.cv.project.update');
            Route::post('/destroy/{id}', [CurriculumProjectController::class, 'destroy'])
                ->name('dashboard.cv.project.destroy');
        });

        ## Habilidades
        Route::group(['prefix' => '/skill'], function () {
            Route::get('/index/{id}', [CurriculumSkillController::class, 'index'])
                ->name('dashboard.cv.skill.index');
            Route::get('/create', [CurriculumSkillController::class, 'create'])
                ->name('dashboard.cv.skill.create');
            Route::post('/store/{cv_id}', [CurriculumSkillController::class, 'store'])
                ->name('dashboard.cv.skill.store');
            Route::get('/edit/{id}', [CurriculumSkillController::class, 'edit'])
                ->name('dashboard.cv.skill.edit');
            Route::post('/update/{id}', [CurriculumSkillController::class, 'update'])
                ->name('dashboard.cv.skill.update');
            Route::post('/destroy/{id}', [CurriculumSkillController::class, 'destroy'])
                ->name('dashboard.cv.skill.destroy');
        });

    }
);
