<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Contracts\Validation\Validator;
use JsonHelper;

/**
 * Class JsonValidationException
 *
 * @package App\Exceptions
 */
class JsonValidationException extends Exception
{
    protected $validator;

    protected $message = 'The given data was invalid.';

    public function __construct(Validator $validator)
    {
        $this->validator = $validator;
    }

    public function report()
    {
        return false;
    }

    public function render($request)
    {
        return JsonHelper::failed(
            $this->message,
            $this->validator->errors()->toArray(),
            422,
            0
        );
    }
}
