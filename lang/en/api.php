<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| API V2 envelope messages
|--------------------------------------------------------------------------
|
| See the Spanish file for the rationale. The locale is picked by
| `App\Http\Middleware\SetLocale` from `Accept-Language` or `?lang=`.
|
*/

return [
    'unauthenticated' => 'Unauthenticated',
    'forbidden' => 'You are not authorized to perform this action',
    'not_found' => 'Resource not found',
    'method_not_allowed' => 'Method not allowed',
    'validation_failed' => 'The given data was invalid.',
    'server_error' => 'Internal server error',
    'too_many_requests' => 'Too many requests. Please try again later.',
    'payload_too_large' => 'The request body is too large',
    'endpoint_not_found' => 'API V2 - Endpoint not found',
    'v1_gone' => 'API V1 is deprecated and has been removed. Please update your clients to API V2.',
];
