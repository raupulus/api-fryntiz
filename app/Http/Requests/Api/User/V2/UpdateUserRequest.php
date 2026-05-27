<?php

namespace App\Http\Requests\Api\User\V2;

use App\Http\Requests\Api\BaseFormRequest;

/**
 * Validación para actualización de usuario en API V2.
 */
class UpdateUserRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('user'));
    }

    public function rules(): array
    {
        $userId = $this->route('user')?->id ?? $this->route('user');

        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'nickname' => ['sometimes', 'nullable', 'string', 'max:100', 'unique:users,nickname,'.$userId],
            'email' => ['sometimes', 'string', 'email', 'max:255', 'unique:users,email,'.$userId],
        ];
    }

    public function messages(): array
    {
        return [
            'name.max' => 'El nombre no puede superar los 255 caracteres.',
            'nickname.unique' => 'Este nickname ya esta en uso.',
            'email.email' => 'El formato del correo electronico no es valido.',
            'email.unique' => 'Este correo electronico ya esta registrado.',
        ];
    }
}
