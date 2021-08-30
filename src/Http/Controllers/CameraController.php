<?php

namespace TromsFylkestrafikk\Camera\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Illuminate\Http\Response;
use TromsFylkestrafikk\Camera\Models\Camera;

class CameraController extends Controller
{
    /**
     * Get the latest camera image.
     */
    public function getLatestImage(Camera $camera)
    {
        $publicPath = "camera/latest";
        $imageFile = null;
        if (!Storage::disk('public')->exists($publicPath)) {
            return response([
                "success" => false,
                "error" => "Public path was not found!",
            ], Response::HTTP_NOT_FOUND);
        }

        // Scan for image files.
        $fileId = $camera->id . '_';
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

        if (!$imageFile) {
            return response([
                "success" => false,
                "error" => "Public path was not found!",
            ], Response::HTTP_NOT_FOUND);
        }

        return response([
            "success" => true,
            "imageFile" => $imageFile,
            "id" => $camera->id,
        ]);
    }
}
