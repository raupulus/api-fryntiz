<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Currículum: datos fijos del propietario
|--------------------------------------------------------------------------
|
| Lo que sale en la cabecera y en la barra lateral de TODOS los currículums
| (vista web y PDF) y que no cambia de un CV a otro: el contacto público y un
| par de datos breves. Va aquí y no repetido en cada fila de `cv`.
|
| El email NO se toma de `users.email`: ese es el de acceso al panel y puede no
| ser público. En un currículum sólo sale este.
|
*/

return [
    'contact' => [
        'email' => 'public@raupulus.dev',
        'location' => 'Chipiona (Cádiz), España',
        'website' => 'https://raupulus.dev',
        'linkedin' => 'https://www.linkedin.com/in/raulcaropastorino',
        'github' => 'https://github.com/raupulus',
    ],

    // Datos breves junto al contacto (ocupan el hueco que antes tenía la edad).
    'details' => [
        'Teletrabajo',
        'Carné B y vehículo propio',
    ],
];
