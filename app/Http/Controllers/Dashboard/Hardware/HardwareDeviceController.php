<?php

namespace App\Http\Controllers\Dashboard\Hardware;

use App\Http\Controllers\Controller;
use App\Models\Hardware\HardwareDevice;
use Illuminate\Http\Request;
use function view;

class HardwareDeviceController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // TODO → Quitar antes de publicar!!! Esto muestra lo del usuario logueado
        //$devices = HardwareDevice::where('user_id', auth()->user()->id)
        //->get();
        $devices = HardwareDevice::all();


        return view('dashboard.hardware.index', [
            'devices' => $devices,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $device = new HardwareDevice();

        return view('dashboard.hardware.add-edit', [
            'device' => $device,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
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
     * @param  \App\Models\Hardware\HardwareDevice  $hardwareDevice
     * @return \Illuminate\Http\Response
     */
    public function edit(HardwareDeviceController $device)
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
