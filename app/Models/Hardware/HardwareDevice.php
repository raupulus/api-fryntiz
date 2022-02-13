<?php

namespace App\Models\Hardware;

use App\Http\Requests\Api\Hardware\V1\StoreSolarChargeRequest;
use App\Models\BaseModels\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Request;
use function array_filter;

/**
 * Class HardwareDevice
 *
 * @package App\Models\Hardware
 */
class HardwareDevice extends BaseModel
{
    use HasFactory;

    protected $table = 'hardware_devices';

    protected $fillable = ['name', 'name_friend', 'ref', 'model', 'software_version',
        'hardware_version', 'serial_number', 'battery_type', 'battery_nominal_capacity',
        'description', 'buy_at'];


    public static function createModel(HardwareDevice $device, $request)
    {

    }

    public function updateModel(Request|StoreSolarChargeRequest $request)
    {
        //$dataFromSolar = $request->only(['hardware', 'version',
        //    'serial_number','battery_type', 'nominal_battery_capacity']);

        $data = [
            'serial_number' => $request->get('serial_number'),
            'hardware_version' => $request->get('hardware_version') ?? $request->get('hardware'),
            'software_version' => $request->get('software_version') ?? $request->get('version'),
            'battery_type' => $request->get('battery_type'),
            'battery_nominal_capacity' => $request->get('battery_nominal_capacity') ?? $request->get('nominal_battery_capacity'),
        ];

        $this->fill(array_filter($data, 'strlen'));

        return $this;
    }
}
