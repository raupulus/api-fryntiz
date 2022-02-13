<?php

namespace App\Http\Requests\Api\Hardware\V1;

use App\Http\Requests\Api\BaseFormRequest;
use App\Models\Hardware\HardwareDevice;
use function auth;

/**
 * Class IndexSolarChargeRequest
 */
class IndexSolarChargeRequest extends BaseFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return auth()->user()->can('indexSolarCharge', HardwareDevice::class);
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
