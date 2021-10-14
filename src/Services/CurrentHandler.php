<?php

namespace TromsFylkestrafikk\Camera\Services;

use DateInterval;
use DateTime;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use TromsFylkestrafikk\Camera\Image\Image;
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

    /**
     * @var \TromsFylkestrafikk\Camera\Image\Image
     */
    protected $image = null;

    public function __construct(Camera $camera)
    {
        $this->camera = $camera;
    }

    /**
     * Get latest image as Image object.
     *
     * @return \TromsFylkestrafikk\Camera\Image\Image
     */
    public function getLatestImage()
    {
        if (!$this->image) {
            $this->image = $this->getLatestImageReal();
        }
        return $this->image;
    }

    /**
     * Logic around creating Image
     *
     * @return \TromsFylkestrafikk\Camera\Image\Image
     */
    protected function getLatestImageReal()
    {
        if (Cache::get($this->camera->currentCacheKey)) {
            return new Image($this->camera);
        }
        $latestFile = $this->findLatestFile();
        if ($this->camera->currentFile !== $latestFile) {
            $this->camera->currentFile = $latestFile;
        }
        $image = new Image($this->camera);
        if ($image->isExpired()) {
            $this->camera->active = false;
        }
        if ($this->camera->isDirty()) {
            // Updated or expired $camera->currentFile. Broadcast change.
            CameraUpdated::dispatch($camera, $image);
        }
        $this->camera->save();
        return $image;
    }

    /**
     * Get metadata about the current/latest image, if still valid.
     *
     * @return array
     */
    public function getLatestImageMeta()
    {
        return $this->getLatestImage()->toArray();
    }

    /**
     * Get the latest updated file for our camera
     *
     * @return string|null
     */
    protected function findLatestFile()
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

        return count($files) ? $files[0]->getRelativePathname() : null;
    }
}
