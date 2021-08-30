<?php

namespace TromsFylkestrafikk\Camera\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Illuminate\Http\Response;
use Symfony\Component\Finder\Finder;
use TromsFylkestrafikk\Camera\Models\Camera;

class CameraController extends Controller
{
    /**
     * Get the latest image from camera.
     *
     * @return \Illuminate\Http\Response
     */
    public function getLatestImage(Camera $camera)
    {
        // @var \Illuminate\Contracts\Filesystem\Filesystem $disk
        $disk = Storage::disk(config('camera.disk'));
        $folder = $this->expandMacro(config('camera.folder'), $camera);
        $folderPath = $disk->path($folder);
        $filePattern = $this->expandMacro(config('camera.file_pattern'), $camera);
        $files = iterator_to_array(
            Finder::create()->files()->in($folderPath)->name($filePattern)->sortByChangedTime()->reverseSorting(),
            false
        );

        if (!count($files)) {
            return response('File not found', Response::HTTP_NOT_FOUND);
        }

        return response()->file($files[0]);
    }

    /**
     * Expand given macro for this camera.
     */
    protected function expandMacro($macro, Camera $camera)
    {
        $allowed = ['id', 'camera_id', 'name', 'ip', 'mac', 'latitude', 'longitude'];
        return preg_replace_callback('|\[\[(?<property>[-a-zA-Z_]+)\]\]|U', function ($matches) use ($allowed, $camera) {
            $prop = $matches['property'];
            return in_array($prop, $allowed) ? $camera->{$prop} : '';
        }, $macro);
    }
}
