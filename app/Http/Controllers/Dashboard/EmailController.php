<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\BaseWithTableCrudController;
use App\Models\Email;
use Illuminate\Http\Request;
use \Illuminate\Contracts\View\View;

class EmailController extends BaseWithTableCrudController
{

    protected static function getModel(): string
    {
        return Email::class;
    }


    /**
     * Listado con todos los correos.
     *
     * @return View
     */
    public function index(): View
    {
        return view('dashboard.emails.index')->with([
            'model' => Email::class,
        ]);
    }
}
