<?php

namespace TromsFylkestrafikk\Camera\Services;

use DateInterval;
use DateTime;
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
            CameraUpdated::dispatch($this->camera, $this->isOutdated() ? null : $latestFile);
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

    /**
     * Return true if current image is outdated.
     *
     * It will also return true if any errors occur, like currentFile doesn't
     * exist or is empty.
     *
     * @return bool
     */
    public function isOutdated()
    {
        if (! $this->camera->currentFile) {
            return true;
        }
        $maxAge = config('camera.max_age');

        if (!$maxAge) {
            return false;
        }
        $modified = filemtime($this->camera->currentPath);
        if (!$modified) {
            return true;
        }
        $minDate = (new DateTime())->sub(new DateInterval($maxAge));
        $modDate = DateTime::createFromFormat('U', $modified);
        return $modDate < $minDate;
    }
}
