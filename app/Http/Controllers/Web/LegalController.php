<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

/**
 * Páginas legales públicas (Aviso Legal, Privacidad y Cookies).
 */
class LegalController extends Controller
{
    public function notice(): View
    {
        return view('legal.notice');
    }

    public function privacy(): View
    {
        return view('legal.privacy');
    }

    public function cookies(): View
    {
        return view('legal.cookies');
    }
}
