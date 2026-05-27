<?php

namespace App\Http\Controllers\Dashboard\Hardware;

use App\Http\Controllers\Controller;
use App\Models\Hardware\HardwareDevice;
use App\Models\Hardware\HardwareType;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Class HardwareEnergyController
 */
class HardwareEnergyController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return Response
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
     * @return Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  HardwareType  $hardwareType
     * @return Response
     */
    public function show(HardwareEnergyController $hardwareType)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  HardwareType  $hardwareType
     * @return Response
     */
    public function edit(HardwareEnergyController $hardwareType)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  HardwareType  $hardwareType
     * @return Response
     */
    public function update(Request $request, HardwareEnergyController $hardwareType)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  HardwareType  $hardwareType
     * @return Response
     */
    public function destroy(HardwareEnergyController $hardwareType)
    {
        //
    }
}
