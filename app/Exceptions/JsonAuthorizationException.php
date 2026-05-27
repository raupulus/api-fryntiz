<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
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
     *
     * @return JsonResponse
     */
    public function render($request)
    {
        if ($request->is('api/v2/*')) {
            return response()->json([
                'success' => false,
                'message' => $this->message,
            ], 403);
        }

        return JsonHelper::forbidden($this->message, 403);
    }
}
