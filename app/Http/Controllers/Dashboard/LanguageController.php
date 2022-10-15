<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use function array_keys;
use function response;

/**
 * Class LanguageController
 */
class LanguageController extends Controller
{
    public function index()
    {
        return view('dashboard.languages.index');
    }

    public function create()
    {
        return view('dashboard.languages.add-edit');
    }

    public function store()
    {
        //TODO
        return redirect()->route('dashboard.languages.index');
    }

    public function edit()
    {
        return view('dashboard.languages.add-edit');
    }

    public function update()
    {
        //TODO
        return redirect()->route('dashboard.languages.index');
    }

    public function destroy()
    {
        //TODO
        return redirect()->route('dashboard.languages.index');
    }
}
