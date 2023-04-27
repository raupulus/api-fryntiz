<?php

return [
    ['header' => 'Gestión'],
    [
        'text' => 'Plataformas',
        'icon' => 'fas fa-fw fa-file',
        'submenu' => [
            [
                'text' => 'Listado',
                'icon' => 'far fa-fw fa-file',
                'route' => 'dashboard.platform.index',
            ],
            [
                'text' => 'Nueva',
                'icon' => 'fa fa-plus',
                'route' => 'dashboard.platform.create',
            ],
        ],
    ],
    [
        'text' => 'Categorías',
        'icon' => 'fas fa-fw fa-file',
        'submenu' => [
            [
                'text' => 'Listado',
                'route' => 'dashboard.category.index',
                'icon' => 'far fa-eye',
            ],
            [
                'text' => 'Nueva',
                'route' => 'dashboard.category.create',
                'icon' => 'fa fa-plus',
            ],
        ],
    ],
    [
        'text' => 'Etiquetas',
        'icon' => 'fas fa-fw fa-file',
        'submenu' => [
            [
                'text' => 'Listado',
                'route' => 'dashboard.tag.index',
                'icon' => 'far fa-eye',
            ],
            [
                'text' => 'Nueva',
                'route' => 'dashboard.tag.create',
                'icon' => 'fa fa-plus',
            ],
        ],
    ],

    ['header' => 'Módulos'],
    [
        'text' => 'Emails',
        'route' => 'dashboard.email.index',
        'icon' => 'fas fa-fw fa-envelope',
    ],

    [
        'text' => 'Curriculum Vitae',
        'icon' => 'fas fa-fw fa-users',
        'submenu' => [
            [
                'text' => 'Listado',
                'icon' => 'far fa-fw fa-file',
                'route' => 'dashboard.cv.index',
            ],
            [
                'text' => 'Nuevo',
                'icon' => 'fa fa-plus',
                'route' => 'dashboard.cv.create',
            ],
            [
                'text' => 'Gestionar Repositorios',
                'route' => 'dashboard.cv.repository_available_type.index',
                'icon' => 'far fa-fw fa-file',
            ],
        ],
    ],
    [
        'text' => 'Hardware',
        'icon' => 'fas fa-fw fa-users',
        'submenu' => [
            [
                'text' => 'Listado',
                'icon' => 'far fa-fw fa-file',
                'route' => 'dashboard.hardware.device.index',
            ],
            [
                'text' => 'Nuevo',
                'icon' => 'fa fa-plus',
                'route' => 'dashboard.hardware.device.create',
            ],
        ],
    ],
    [
        'text' => 'Nubes',
        'icon' => 'fas fa-fw fa-file',
        'submenu' => [
            [
                'text' => 'Pcloud',
                'icon' => 'far fa-fw fa-file',
                'url' => '#',
            ],
            [
                'text' => 'Synology',
                'icon' => 'far fa-fw fa-file',
                'url' => '#',
            ],
            [
                'text' => 'Dropbox',
                'icon' => 'far fa-fw fa-file',
                'url' => '#',
            ],
            [
                'text' => 'Google Drive',
                'icon' => 'far fa-fw fa-file',
                'url' => '#',
            ],
            [
                'text' => 'iCloud',
                'icon' => 'far fa-fw fa-file',
                'url' => '#',
            ],
        ],
    ],
    [
        'text' => 'Keycounter',
        'icon' => 'fas fa-fw fa-users',
        'submenu' => [
            [
                'text' => 'Estadísticas',
                'icon' => 'far fa-fw fa-file',
                'url' => '#',
            ],
            [
                'text' => 'Mantenimiento',
                'icon' => 'fa fa-plus',
                'url' => '#',
            ],
        ],
    ],
    [
        'text' => 'Airflights',
        'icon' => 'fas fa-fw fa-users',
        'submenu' => [
            [
                'text' => 'Estadísticas',
                'icon' => 'far fa-fw fa-file',
                'url' => '#',
            ],
            [
                'text' => 'Aviones',
                'icon' => 'far fa-fw fa-file',
                'url' => '#',
            ],
            [
                'text' => 'Mantenimiento',
                'icon' => 'fa fa-plus',
                'url' => '#',
            ],
        ],
    ],
    [
        'text' => 'Smart Plant',
        'icon' => 'fas fa-fw fa-users',
        'submenu' => [
            [
                'text' => 'Estadísticas',
                'icon' => 'far fa-fw fa-file',
                'url' => '#',
            ],
            [
                'text' => 'Mantenimiento',
                'icon' => 'fa fa-plus',
                'url' => '#',
            ],
        ],
    ],
    [
        'text' => 'Energy',
        'icon' => 'fas fa-fw fa-users',
        'submenu' => [
            [
                'text' => 'Estadísticas',
                'icon' => 'far fa-fw fa-file',
                'route' => 'dashboard.energy.index',
            ],
            [
                'text' => 'Mantenimiento',
                'icon' => 'fa fa-plus',
                'url' => '#',
            ],
        ],
    ],

    ['header' => 'Usuarios'],
    [
        'text' => 'Perfiles',
        'icon' => 'fas fa-fw fa-users',
        'submenu' => [
            [
                'text' => 'Ver Usuarios',
                'icon' => 'fa fa-eye',
                'route' => 'dashboard.users.index',
            ],
            [
                'text' => 'Añadir Usuario',
                'icon' => 'fa fa-plus',
                'url' => '#',
            ],
        ],
    ],
    [
        'text' => 'API',
        'icon' => 'fas fa-fw fa-users',
        'submenu' => [
            [
                'text' => 'Ver Usuarios',
                'icon' => 'fa fa-eye',
                'url' => '#',
            ],
            [
                'text' => 'Añadir Usuario',
                'icon' => 'fa fa-plus',
                'url' => '#',
            ],
        ],
    ],
    ['header' => 'account_settings'],
    [
        'text' => 'profile',
        'route' => 'dashboard.users.show',
        'icon' => 'fas fa-fw fa-user',
    ],
    [
        'text' => 'change_password',
        'url' => 'admin/settings',
        'icon' => 'fas fa-fw fa-lock',
    ],
    ['header' => 'Traducciones'],
    [
        'text' => 'Idiomas',
        'icon' => 'fas fa-fw fa-globe',
        'submenu' => [
            [
                'text' => 'Listar',
                'icon' => 'fas fa-fw fa-edit',
                'route' => 'dashboard.language.index',
            ],
            [
                'text' => 'Añadir',
                'icon' => 'fas fa-fw fa-plus-circle',
                'route' => 'dashboard.language.create',
            ],
        ],
    ],
    ['header' => 'Acciones'],
    [
        'text' => 'Home',
        'icon' => 'fas fa-fw fa-share',
        'icon_color' => 'cyan',
        'route' => 'home',
    ],
    ['header' => 'labels'],
    [
        'text' => 'important',
        'icon_color' => 'red',
        'url' => '#',
    ],
    [
        'text' => 'warning',
        'icon_color' => 'yellow',
        'url' => '#',
    ],
    [
        'text' => 'information',
        'icon_color' => 'cyan',
        'url' => '#',
    ],

    // Right Sidebar Items
    [
        'text' => 'Configuración',
        'icon_color' => 'cyan',
        'url' => '#',
        'rightsidebar' => true
    ],
    [
        'text' => 'API',
        'icon_color' => 'cyan',
        'url' => '#',
    ],
];
