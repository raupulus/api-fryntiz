<?php

namespace App\Http\Requests\Cv;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

/**
 * Class StoreCvSkillRequest
 */
class StoreCvSkillRequest extends FormRequest
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
            'name' => Str::of($this->name)->trim()->ucfirst()
                ->replaceMatches('/\s\s+/', '')->__toString(),
            'level' => $this->level ? (int) $this->level : 0,
            'description' => Str::of($this->description)->trim()->ucfirst()
                ->replaceMatches('/\s\s+/', '')->__toString(),
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
            'name' => 'required|string|min:3|max:511',
            'level' => 'required|number|bewteen:1,10',
            'description' => 'nullable|string|min:10|max:1500',
        ];
    }
}
