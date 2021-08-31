<?php

namespace TromsFylkestrafikk\Camera\Http\Controllers;

use DateTime;
use DateInterval;
use DateTimezone;
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
            abort(Response::HTTP_NOT_FOUND);
        }

        return response()->file($files[0], $this->createCacheHeaders($camera, $files[0]));
    }

    protected function createCacheHeaders(Camera $camera, $filename)
    {
        $file_stats = stat($filename);
        $modified = DateTime::createFromFormat('U', $file_stats['mtime'], new DateTimezone(config('app.timezone')));
        return [
            'Cache-Control' => 'max-age=0, must-revalidate',
            'Content-Disposition' => sprintf(
                'inline; filename="camera-%s-%s.jpeg"',
                $camera->name,
                $modified->format('Y-m-d\TH:i:s'),
            ),
            'Etag' => md5(md5_file($filename) . $file_stats['mtime']),
            'Expires' => $modified->add(new DateInterval('P1M'))->format('r'),
            'Pragma' => 'public',
        ];
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
