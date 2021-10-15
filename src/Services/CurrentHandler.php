<?php

namespace TromsFylkestrafikk\Camera\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
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
     * In addition, this broadcasts any updates to the image, if it's expired or
     * a new one has arrived on disk.
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
            Log::debug("Found cached image. Using existing assocciated with camera");
            return new Image($this->camera);
        }
        $latestFile = $this->findLatestFile();
        Log::debug("Latest file is " . $latestFile);
        if ($this->camera->currentFile !== $latestFile) {
            $this->camera->currentFile = $latestFile;
        }
        $image = new Image($this->camera);
        $this->camera->active = !$image->isExpired();
        if ($image->isExpired()) {
            $this->camera->currentFile = null;
            Log::warning(sprintf(
                "Camera %d (%s) isn't receiving imagery. Deactivating it. Latest seen file is '%s'",
                $this->camera->id,
                $this->camera->name,
                $this->camera->currentRelativePath
            ));
        }
        $this->camera->active = !$image->isExpired();
        if ($this->camera->isDirty()) {
            $this->camera->save();
            Log::debug("Camera is dirty. Announcing change in imagery");
            // Updated or expired $camera->currentFile. Broadcast change.
            CameraUpdated::dispatch($this->camera, $image);
        }
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
