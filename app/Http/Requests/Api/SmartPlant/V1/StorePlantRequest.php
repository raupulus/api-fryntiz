<?php

namespace App\Http\Requests\Api\SmartPlant\V1;

use App\Http\Requests\Api\BaseFormRequest;

/**
 * Class StorePlantRequest
 */
class StorePlantRequest extends BaseFormRequest
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
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            //
        ];
    }
}
