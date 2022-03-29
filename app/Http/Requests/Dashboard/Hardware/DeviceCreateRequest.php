<?php

namespace App\Http\Requests\Dashboard\Hardware;

use App\Models\Hardware\HardwareDevice;
use Illuminate\Foundation\Http\FormRequest;
use function auth;

class DeviceCreateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return auth()->id() && auth()->user()->can('create', HardwareDevice::class);
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
