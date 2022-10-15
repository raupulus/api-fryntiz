<?php

namespace App\Exceptions;

use Exception;
use JsonHelper;

/**
 * Class JsonAuthorizationException
 */
class JsonAuthorizationException extends Exception
{
    /**
     * @var string
     */
    protected $message = 'You are not authorized to perform this action.';

    public function report()
    {
        return false;
    }

    /**
     * Render the exception as an HTTP response.
     *
     * @param $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function render($request)
    {
        return JsonHelper::forbidden($this->message, 403);
    }
}
