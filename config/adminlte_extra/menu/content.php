<?php


return [
    ['header' => 'Contenido'],
    [
        'text' => 'Todos',
        'route' => 'dashboard.content.index',
        'icon' => 'far fa-fw fa-file',
    ],

    [
        'text' => 'Categorías',
        'url' => '#',
        'icon' => 'far fa-fw fa-file',
    ],

    // TODO → Dinamizar por tipo de contenido
    [
        'text' => 'blog',
        'url' => 'admin/blog',
        'can' => 'manage-blog', // Esto asigna permiso para ver la opción
    ],
    [
        'text' => 'pages',
        'url' => '#',
        'icon' => 'far fa-fw fa-file',
        'label' => 4,
        'label_color' => 'success',
    ],
    [
        'text' => 'noticias',
        'url' => '#',
        'icon' => 'far fa-fw fa-file',
        'label' => 4,
        'label_color' => 'success',
    ],

];

