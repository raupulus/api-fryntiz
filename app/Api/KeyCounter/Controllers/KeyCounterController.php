<?php

namespace App\Http\Controllers\Keycounter;

use App\Http\Controllers\Controller;
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
