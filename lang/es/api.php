<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Mensajes del envelope de la API V2
|--------------------------------------------------------------------------
|
| Son los mensajes que devuelve la API cuando el que responde no es un
| controlador, sino un handler de `bootstrap/app.php` o un `render()` de
| `app/Exceptions/`. Estaban escritos a mano —y dos de ellos en inglés, que era
| lo único de la API que no salía traducido— antes de la revisión de
| 2026-09-02.
|
| El idioma lo elige `App\Http\Middleware\SetLocale` a partir de
| `Accept-Language` o de `?lang=`.
|
*/

return [
    'unauthenticated' => 'No autenticado',
    'forbidden' => 'No autorizado para realizar esta acción',
    'not_found' => 'Recurso no encontrado',
    'method_not_allowed' => 'Método no permitido',
    'validation_failed' => 'Los datos proporcionados no son válidos.',
    'server_error' => 'Error interno del servidor',
    'too_many_requests' => 'Demasiadas peticiones. Inténtalo de nuevo más tarde.',
    'payload_too_large' => 'El cuerpo de la petición es demasiado grande',
    'endpoint_not_found' => 'API V2 - Endpoint no encontrado',
    'v1_gone' => 'La API V1 está obsoleta y ha sido eliminada. Por favor, actualice sus clientes a la API V2.',
];
