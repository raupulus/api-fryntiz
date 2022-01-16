<?php

namespace App\Http\Requests\Cv;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

/**
 * Class StoreCvExperienceOtherRequest
 * @package App\Http\Requests\Cv
 */
class StoreCvExperienceAccreditedRequest extends FormRequest
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
            'title' => Str::of($this->name)->trim()->ucfirst()
                ->replaceMatches('/\s\s+/', '')->__toString(),
            'position' => Str::of($this->name)->trim()
                ->replaceMatches('/\s\s+/', '')->__toString(),
            'company' => Str::of($this->name)->trim()
                ->replaceMatches('/\s\s+/', '')->__toString(),
            'description' => Str::of($this->description)->trim()->ucfirst()
                ->replaceMatches('/\s\s+/', '')->__toString(),
            'note' => Str::of($this->description)->trim()->ucfirst()
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
            'title' => 'required|string|min:3|max:511',
            'position' => 'required|string|min:3|max:255',
            'company' => 'required|string|min:3|max:511',
            'description' => 'nullable|string|min:10|max:1500',
            'note' => 'nullable|string|min:12|max:1500',
            'start_at' => 'nullable|date|before_or_equal:today',
            'end_at' => 'nullable|date|after_or_equal:start_at',
        ];
    }
}
