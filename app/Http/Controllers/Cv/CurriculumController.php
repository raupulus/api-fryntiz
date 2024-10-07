<?php

namespace App\Http\Controllers\Cv;

use App\Http\Controllers\Controller;

class CurriculumController extends Controller
{

    public function getPdfDefault()
    {
        $filePath = public_path('pdf/curriculum_vitae.pdf');
        return response()->download($filePath);
    }
}
