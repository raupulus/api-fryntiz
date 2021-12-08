<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\CV\Curriculum;
use App\Models\File;
use Illuminate\Http\Request;
use function auth;
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
        $cv = new Curriculum();

        return view('dashboard.curriculums.add-edit')->with([
            'cv' => $cv,
        ]);
    }

    public function store(Request $request)
    {
        if (auth()->guest()) {
            return redirect()->route('login');
        }

        if ( ! $request->has('cv_id')) {
            $cv = new Curriculum([
                'user_id' => auth()->user()->id,
            ]);
        } else {
            $cv = Curriculum::find($request->cv_id);
        }

        if ($cv->id && $cv->user_id != auth()->user()->id) {
            return redirect()->back()->with('error', 'You are not authorized to edit this curriculum.');
        }


        $cv->fill($request->all());
        $cv->save();

        if ($request->hasFile('image')) {
            $file = File::addFile($request->file('image'), 'cv', true, $cv->image_id);

            if (!$cv->image_id && $file) {
                $cv->image_id = $file->id;
                $cv->save();
            }
        }

        /*
        dd('Llega', $cv, $request->all(), $cv->image, $cv->image ?
            $cv->image->thumbnails : null, $cv->image ? $cv->image->fileType :
            null);
        */

        return redirect()->route('dashboard.cv.index');
    }

    public function edit()
    {
        return view('dashboard.cv.add-edit');
    }

    public function update(Request $request)
    {
        return $this->store($request);
    }

    public function destroy()
    {
        //TODO
        return redirect()->route('dashboard.cv.index');
    }
}
