<?php

namespace App\Http\Requests\Api;

use App\Exceptions\JsonAuthorizationException;
use App\Exceptions\JsonValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class BaseFormRequest extends FormRequest
{
    protected function failedAuthorization(): void
    {
        throw new JsonAuthorizationException;
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new JsonValidationException($validator);
    }
}
