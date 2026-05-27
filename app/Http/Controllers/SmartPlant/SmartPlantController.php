<?php

namespace App\Http\Controllers\SmartPlant;

use App\Http\Controllers\Controller;
use App\Models\SmartPlant\SmartPlantPlant;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;

/**
 * Class SmartPlantController
 */
class SmartPlantController extends Controller
{
    /**
     * LLeva a la vista resumen con datos para depurar subidas.
     *
     * @return Application|Factory|View
     */
    public function index()
    {
        $smartplants = SmartPlantPlant::with(['registers' => function ($query) {
            $query->latest()->take(10);
        }])->get();

        return view('smartplant.index')->with([
            'smartplants' => $smartplants,
        ]);
    }

    public function show()
    {
        //
    }
}
