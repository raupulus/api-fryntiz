<?php

namespace App\Http\Controllers\SmartPlant;

use App\Http\Controllers\Controller;
use App\Models\SmartPlant\SmartPlantPlant;

/**
 * Class SmartPlantController
 *
 * @package App\Http\Controllers\SmartPlantController
 */
class SmartPlantController extends Controller
{
    /**
     * LLeva a la vista resumen con datos para depurar subidas.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function index()
    {
        $smartplants = SmartPlantPlant::all();

        return view('smartplant.index')->with([
            'smartplants' => $smartplants,
        ]);
    }

    public function show()
    {
        //
    }
}
