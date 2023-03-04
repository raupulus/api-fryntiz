<?php

namespace App\Http\Controllers\Dashboard\Hardware;

use App\Http\Controllers\Controller;
use App\Models\Hardware\HardwareDevice;
use Illuminate\Http\Request;

/**
 * Class HardwareEnergyController
 *
 * @package App\Http\Controllers\Dashboard\Hardware
 */
class HardwareEnergyController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $hardwareMonitor = HardwareDevice::where('hardware_type_id', 1)
            ->get();

        return view('dashboard.energy.index', compact('hardwareMonitor'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
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
     * @param  \App\Models\Hardware\HardwareType  $hardwareType
     * @return \Illuminate\Http\Response
     */
    public function show(HardwareEnergyController $hardwareType)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Hardware\HardwareType  $hardwareType
     * @return \Illuminate\Http\Response
     */
    public function edit(HardwareEnergyController $hardwareType)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Hardware\HardwareType  $hardwareType
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, HardwareEnergyController $hardwareType)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Hardware\HardwareType  $hardwareType
     * @return \Illuminate\Http\Response
     */
    public function destroy(HardwareEnergyController $hardwareType)
    {
        //
    }
}
