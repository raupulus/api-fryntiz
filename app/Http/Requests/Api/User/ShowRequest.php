<?php

namespace App\Http\Requests\Api\User;

use App\Http\Requests\Api\BaseFormRequest;
use App\Models\User;
use function auth;

/**
 * Class ShowRequest
 */
class ShowRequest extends BaseFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        $user = User::findOrFail($this->user_id);

        return auth()->id() && auth()->user()->can('view', $user);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'user_id' => 'required|integer|exists:users,id',
        ];
    }
}
