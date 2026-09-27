<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Livewire: sólo lo que cambia respecto a la configuración del paquete
|--------------------------------------------------------------------------
|
| Laravel mezcla este fichero con el del paquete por claves de primer nivel:
| `payload` sustituye entero al del paquete, así que van sus cuatro claves.
|
| `max_size` pasa de 1 MB a 8 MB (G4 de la auditoría de contenidos): una
| página larga viaja entera al guardar, y con 1 MB una de más de ~250 KB
| fallaba con un error poco claro. Delante, nginx y PHP tienen que admitir
| también 8 MB (`client_max_body_size`, `post_max_size`).
*/

return [
    'payload' => [
        'max_size' => 8 * 1024 * 1024,
        'max_nesting_depth' => 10,
        'max_calls' => 50,
        'max_components' => 200,
    ],
];
