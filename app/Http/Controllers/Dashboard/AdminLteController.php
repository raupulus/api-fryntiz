<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\FileType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\View\View;
use Image;

class AdminLteController extends Controller
{


    /**
     * Muestra el listado con todos los tipos de archivos que hay en la plataforma.
     *
     * @return View
     */
    public function fileTypesIndex(): View
    {

        $fileTypes = FileType::select([
            'id',
            'type',
            'mime',
            'icon128',
        ])
            ->orderBy('mime')
            ->get();


        $types = $fileTypes->pluck('type')->unique();

        return view('dashboard.adminlte.file_types', [
            'fileTypes' => $fileTypes,
            'types' => $types,
        ]);
    }


    public function fileTypesIconUpdate(Request $request, FileType $fileType): JsonResponse
    {
        $request->validate([
            'image' => 'required|image',
        ]);

        ## Borro imágenes anteriores que ya no se usan
        if ($fileType->icon128) {
            $storagePath32 = storage_path('app/public/' . $fileType->icon32);
            $storagePath16 = storage_path('app/public/' . $fileType->icon16);

            $oldFilesPath = [
                storage_path('app/public/' . $fileType->icon128),
                storage_path('app/public/' . $fileType->icon64),
                storage_path('app/public/' . $fileType->icon32),
                storage_path('app/public/' . $fileType->icon16),
            ];

            foreach ($oldFilesPath as $oldFilePath) {
                if (file_exists($oldFilePath)) {
                    try {
                        unlink($oldFilePath);
                    } catch (\Exception $e) {

                    }
                }
            }


        }


        if ($request->file('image') instanceof UploadedFile) {
            $image = $request->file('image');

            $tmpPath = $image->getRealPath();
            $path = 'icons/file_types';
            $fullPath = storage_path('app/public/' . $path);

            $name = \Str::random(32);

            $extension = $image->getClientOriginalExtension();
            $format = 'jpg';

            $mime = $image->getMimeType();

            if (!$extension) {
                $extension = explode('/', $mime)[1];
            }

            if (in_array($extension, ['png', 'jpg', 'jpeg', 'gif'])) {
                $extension = 'webp';
                $format = 'webp';
            }

            $name128 = $name . '_128.' . $extension;
            $name64 = $name . '_64.' . $extension;
            $name32 = $name . '_32.' . $extension;
            $name16 = $name . '_16.' . $extension;

            // 1 - Redimensionar a 128
            $image128 = Image::make($tmpPath)->resize(128, 128)
                ->save($fullPath . '/' . $name128, 90, $format);

            // 2 - Redimensionar a 64
            $image64 = Image::make($tmpPath)->resize(64, 64)
                ->save($fullPath . '/' . $name64, 90, $format);

            // 3 - Redimensionar a 32
            $image32 = Image::make($tmpPath)->resize(32, 32)
                ->save($fullPath . '/' . $name32, 90, $format);

            // 4 - Redimensionar a 16
            $image16 = Image::make($tmpPath)->resize(16, 16)
                ->save($fullPath . '/' . $name16, 90, $format);

            $fileType->icon128 = $path . '/' . $name128;
            $fileType->icon64 = $path . '/' . $name64;
            $fileType->icon32 = $path . '/' . $name32;
            $fileType->icon16 = $path . '/' . $name16;
            $fileType->save();
        }

        return \JsonHelper::success([
            'msg' => 'Icono actualizado',
            'url' => $fileType->urlImage,
        ]);
    }
}
