<?php

namespace TromsFylkestrafikk\Camera\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use TromsFylkestrafikk\Camera\Models\Camera;

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
     * Scan for latest image for camera.
     *
     * In addition, this broadcasts any updates to the image, if it's expired or
     * a new one has arrived on disk.
     *
     * @return \TromsFylkestrafikk\Camera\Models\Camera
     */
    public function refresh()
    {
        if (Cache::get($this->camera->currentCacheKey)) {
            Log::debug("Found cached image. Using existing assocciated with camera");
            return $this->camera;
        }
        $latestFile = $this->findLatestFile();
        Log::debug("Latest file is " . $latestFile);
        if ($this->camera->currentFile !== $latestFile) {
            $this->camera->currentFile = $latestFile;
        }
        $this->camera->active = !$this->camera->hasStalled;
        if ($this->camera->active) {
            $this->camera->currentFile = null;
            Log::warning(sprintf(
                "Camera %d (%s) isn't receiving imagery. Deactivating it. Latest seen file is '%s'",
                $this->camera->id,
                $this->camera->name,
                $this->camera->currentRelativePath
            ));
        }
        if ($this->camera->isDirty()) {
            Log::debug("Camera is dirty. Announcing change in imagery");
            $this->camera->save();
        }
        return $this->camera;
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
        $filePattern = "|{$this->camera->fileRegex}$|";
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
