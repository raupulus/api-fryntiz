<?php

namespace App\Http\Requests\Dashboard\Hardware;

use Illuminate\Foundation\Http\FormRequest;
use function auth;
use function trim;

class DeviceStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return auth()->id() && auth()->user()->can('create', \App\Models\Hardware\HardwareDevice::class);
    }

    public function prepareForValidation()
    {
        $this->merge([
            'user_id' => auth()->id(),
            'description' => trim($this->get('description')),
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
            'user_id' => 'required|integer|exists:users,id',
            'hardware_type_id' => 'required|integer|exists:hardware_types,id',
            'referred_thing_id' => 'nullable|integer|exists:referred_things,id',
            'name' => 'required|string|max:255',
            'name_friendly' => 'required|string|max:255',
            'ref' => 'nullable|string|max:255',
            'brand' => 'nullable|string|max:255',
            'model' => 'nullable|string|max:255',
            'software_version' => 'nullable|string|max:255',
            'hardware_version' => 'nullable|string|max:255',
            'serial_number' => 'nullable|string|max:255',
            'battery_type' => 'nullable|string|max:255',
            'battery_nominal_capacity' => 'nullable|integer',
            'url_company' => 'nullable|string|max:255',
            'buy_at' => 'nullable|date',


        ];
    }
}
