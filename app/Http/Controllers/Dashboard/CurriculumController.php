<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use function redirect;
use function view;

/**
 * Class CurriculumController
 */
class CurriculumController extends Controller
{
    public function index()
    {
        return view('dashboard.curriculums.index');
    }

    public function create()
    {
        return view('dashboard.curriculums.add-edit');
    }

    public function store()
    {
        //TODO
        return redirect()->route('dashboard.curriculums.index');
    }

    public function edit()
    {
        return view('dashboard.curriculums.add-edit');
    }

    public function update()
    {
        //TODO
        return redirect()->route('dashboard.curriculums.index');
    }

    public function destroy()
    {
        //TODO
        return redirect()->route('dashboard.curriculums.index');
    }
}
