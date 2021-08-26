<?php

namespace TromsFylkestrafikk\Camera\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;

class CameraController extends Controller
{
    /**
     * Get the latest camera image.
     */
    public function getLatestImage($id)
    {
        $publicPath = "camera/latest";
        $imageFile = null;
        $errorMsg = "Public path was not found!";
        if (Storage::disk('public')->exists($publicPath)) {
            // Scan for image files.
            $errorMsg = "No image file found!";
            $fileId = $id . '_';
            $fullPath = Storage::disk('public')->path($publicPath);
            $files = collect(File::files($fullPath))
                ->filter(function ($file) use ($fileId) {
                    if (strpos($file->getBaseName(), $fileId) !== 0) {
                        // Ignore image files from other cameras.
                        return false;
                    }
                    return $file->getExtension() === 'jpg';
                })
                ->map(function ($file) {
                    return $file->getBaseName();
                });

            // Sort by filename in descending order. The latest image file
            // is then found at the top of the list.
            $sorted = $files->sortByDesc(function ($item) {
                return $item;
            });
            $imageFile = $sorted->first();
        }

        if ($imageFile) {
            $errorMsg = null;
        }

        return response([
            "success" => true,
            "imageFile" => $imageFile,
            "id" => $id,
            "error" => $errorMsg,
        ]);
    }
}
