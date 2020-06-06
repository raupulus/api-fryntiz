<?php

namespace App\Http\Controllers\KeyCounter;

use function view;

class KeyCounterController extends Controller
{
    public function index()
    {
        return view('keycounter.index')->with([
            'keyboard' => \App\Keycounter\Keyboard::all(),
            'mouse' => \App\Keycounter\Mouse::all(),
        ]);
    }
}
