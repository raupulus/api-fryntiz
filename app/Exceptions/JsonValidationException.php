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
        parent::__construct($this->message);
        $this->validator = $validator;
    }

    /**
     * Devuelve los errores de validación.
     */
    public function errors(): array
    {
        return $this->validator->errors()->toArray();
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
