<?php

namespace App\Http\Controllers\Dashboard\Content;

use App\Http\Controllers\Controller;
use App\Models\Content\Content;

/**
 * Class ContentAvailableCategoryController
 * @package App\Http\Controllers\Dashboard\Content
 */
class ContentController extends Controller
{
    public function index()
    {
        return view('dashboard.content.index');
    }

    public function create()
    {
        $content = new Content();
        return view('dashboard.content.add-edit')
            ->with([
                'content' => $content
            ]);
    }

    public function store()
    {
        //
    }

    public function edit()
    {
        return view('dashboard.content.edit');
    }

    public function show()
    {
        return view('dashboard.content.show');
    }

    public function update()
    {
        //
    }

    public function destroy()
    {
        //
    }

}
