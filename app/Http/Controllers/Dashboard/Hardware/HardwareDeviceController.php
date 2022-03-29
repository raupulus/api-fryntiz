<?php

namespace App\Http\Controllers\Dashboard\Hardware;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\Hardware\DeviceCreateRequest;
use App\Http\Requests\Dashboard\Hardware\DeviceIndexRequest;
use App\Http\Requests\Dashboard\Hardware\DeviceStoreRequest;
use App\Models\Hardware\HardwareDevice;
use Illuminate\Http\Request;
use function view;

class HardwareDeviceController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param \App\Http\Requests\Dashboard\Hardware\DeviceIndexRequest $request
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function index(DeviceIndexRequest $request)
    {
        $devices = HardwareDevice::where('user_id', auth()->user()->id)->get();

        return view('dashboard.hardware.index', [
            'devices' => $devices,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @param \App\Http\Requests\Dashboard\Hardware\DeviceCreateRequest $request
     * @param \App\Models\Hardware\HardwareDevice                       $device
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function create(DeviceCreateRequest $request, HardwareDevice $device)
    {
        return view('dashboard.hardware.add-edit', [
            'device' => $device,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \App\Http\Requests\Dashboard\Hardware\DeviceStoreRequest $request
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(DeviceStoreRequest $request)
    {
        dd($request->validated(), $request->all());


        $device = HardwareDevice::create($request->validated());

        //TODO → guardar imagen
        if ($request->hasFile('image')) {
        }

        return redirect()->route('dashboard.hardware.device.index');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Hardware\HardwareDevice  $hardwareDevice
     * @return \Illuminate\Http\Response
     */
    public function show(HardwareDeviceController $hardwareDevice)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param \App\Models\Hardware\HardwareDevice $device
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function edit(HardwareDevice $device)
    {

        return view('dashboard.hardware.add-edit', [
            'device' => $device,
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Hardware\HardwareDevice  $hardwareDevice
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, HardwareDeviceController $hardwareDevice)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Hardware\HardwareDevice  $hardwareDevice
     * @return \Illuminate\Http\Response
     */
    public function destroy(HardwareDeviceController $hardwareDevice)
    {
        //
    }
}
