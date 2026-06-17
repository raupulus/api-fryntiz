<?php

namespace App\Http\Requests\Api\User\V2;

use App\Http\Requests\Api\BaseFormRequest;
use App\Models\User;

/**
 * Validación para creación de usuario en API V2.
 */
class StoreUserRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', User::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'nickname' => ['nullable', 'string', 'max:100', 'unique:users,nickname'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'role_id' => ['sometimes', 'integer', 'exists:user_roles,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre es obligatorio.',
            'nickname.unique' => 'Este nickname ya esta en uso.',
            'email.required' => 'El correo electronico es obligatorio.',
            'email.email' => 'El formato del correo electronico no es valido.',
            'email.unique' => 'Este correo electronico ya esta registrado.',
            'password.required' => 'La contrasena es obligatoria.',
            'password.min' => 'La contrasena debe tener al menos 8 caracteres.',
            'role_id.exists' => 'El rol especificado no existe.',
        ];
    }
}
