<?php

namespace TromsFylkestrafikk\Camera\Services;

use Illuminate\Support\Facades\Storage;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use TromsFylkestrafikk\Camera\Models\Camera;
use TromsFylkestrafikk\Camera\Events\CameraUpdated;

/**
 * Logic around camera's 'currentFile' handling.
 */
class CurrentHandler
{
    /**
     * @var \TromsFylkestrafikk\Camera\Models\Camera
     */
    protected $camera;

    public function __construct(Camera $camera)
    {
        $this->camera = $camera;
    }

    /**
     * Update our camera model with latest found image.
     *
     * @return \TromsFylkestrafikk\Camera\Models\Camera
     */
    public function updateWithLatest()
    {
        $latestFile = $this->getLatestFile()->getRelativePathname();
        if ($this->camera->currentFile !== $latestFile) {
            $this->camera->currentFile = $latestFile;
            $this->camera->save();
            CameraUpdated::dispatch($this->camera, $latestFile);
        }
        return $this;
    }

    /**
     * Get the latest updated file for our camera
     *
     * @return \Symfony\Component\Finder\SplFileInfo
     */
    public function getLatestFile()
    {
        $disk = Storage::disk(config('camera.disk'));
        $filePattern = sprintf("|%s$|", $this->camera->fileRegex);
        $files = iterator_to_array(
            Finder::create()
                ->files()
                ->in($this->camera->folderPath)
                ->name($filePattern)
                ->sortByChangedTime()
                ->reverseSorting(),
            false
        );

        return count($files) ? $files[0] : null;
    }
}
