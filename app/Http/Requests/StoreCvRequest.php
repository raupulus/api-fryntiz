<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Class StoreCvRequest
 * @package App\Http\Requests
 */
class StoreCvRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Prepare the data for validation.
     *
     * @return void
     */
    protected function prepareForValidation()
    {
        $this->merge([
            'is_active' => $this->is_active ? true : false,
            'is_downloadable' => $this->is_downloadable ? true : false,
            'is_default' => $this->is_default ? true : false,
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'image' => 'nullable|image|max:2048',
            'title' => 'required|string|max:511',
            'is_active' => 'boolean',
            'is_downloadable' => 'boolean',
            'is_default' => 'boolean',
            'presentation' => 'nullable|string',
        ];
    }
}
