<?php

namespace TromsFylkestrafikk\Camera\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Intervention\Image\ImageManagerStatic;
use Intervention\Image\Image;
use Symfony\Component\Finder\Finder;
use TromsFylkestrafikk\Camera\Events\ProcessImage;
use TromsFylkestrafikk\Camera\Models\Camera;
use TromsFylkestrafikk\Camera\Services\CameraTokenizer;

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
     * Scan for latest image, update state and save (broadcast) new state.
     *
     * This is a janitor for the camera. It does mainly three things:
     *   1) Finds/assert latest file is associated with model.
     *   2) De-activates camera if the imagery is outdated.
     *   3) Saves and thereby broadcasts changes to model
     *
     * Also, it caches the currently found file for a configurable amount, so
     * many, simultaneous requests doesn't all searches the file system for the
     * same file.
     *
     * @return \TromsFylkestrafikk\Camera\Models\Camera
     */
    public function refresh()
    {
        if (Cache::get($this->camera->currentCacheKey)) {
            return $this->camera;
        }
        $this->camera->ensureFoldersExists();
        $latestFile = $this->findLatestFile($this->camera->incomingPath);
        if ($this->camera->currentFile !== $latestFile) {
            $incomingFile = $this->camera->incomingPath . '/' . $latestFile;
            $this->processIncomingFile($incomingFile);
        }
        $this->camera->active = !$this->camera->hasStalled;
        if (!$this->camera->active) {
            $this->camera->currentFile = null;
            Log::warning(sprintf(
                "IpCamera: Camera %d (%s) isn't receiving imagery. Deactivating it. Latest seen file is '%s'",
                $this->camera->id,
                $this->camera->name,
                $this->camera->currentRelativePath
            ));
        }
        if ($this->camera->isDirty()) {
            Log::debug("IpCamera: Camera is dirty. Announcing change in imagery");
            $this->camera->save();
        } else {
            $timeout = config('camera.cache_current');
            Cache::put($this->camera->currentCacheKey, $this->camera->currentFile, $timeout);
        }
        return $this->camera;
    }

    /**
     * Get the latest updated file for our camera
     *
     * @param string $directory  The directory to look in. Defaults to
     *   configured published directory for this camera.
     *
     * @return string|null
     */
    protected function findLatestFile($directory = null)
    {
        $filePattern = "|{$this->camera->fileRegex}$|";
        if (!$directory) {
            $directory = $this->camera->folderPath;
        }
        $files = iterator_to_array(
            Finder::create()
                ->files()
                ->in($directory)
                ->name($filePattern)
                ->sortByChangedTime()
                ->reverseSorting(),
            false
        );

        return count($files) ? $files[0]->getRelativePathname() : null;
    }

    /**
     * Process new incoming file to camera.
     *
     * This invokes the event 'TromsFylkestrafikk\Camera\Events\ProcessImage'
     * which allows listeners to modify the image as an Spatie\Image\Image
     * wrapper. It's then saved in the published folder. Camera is updated with
     * latest file, though not saved.
     */
    public function processIncomingFile($inFile)
    {
        $fileName = basename($inFile);
        if (config('camera.incoming_disk') === config('camera.disk')) {
            $this->info("Incoming disk same as target. Not modifying incoming imagery", 'vv');
            return;
        }
        $outFile = $this->camera->folderPath . '/' . $fileName;
        // $var \Intervention\Image\Image $image
        $image = ImageManagerStatic::make($inFile);
        $this->applyImageManipulations($image);
        ProcessImage::dispatch($this->camera, $image);
        $image->save($outFile);
        // Sync modification time from input to output file.
        touch($outFile, filemtime($inFile));
        $this->camera->currentFile = $fileName;
    }

    protected function applyImageManipulations(Image $image)
    {
        $inc = sprintf(
            "%s/%s",
            base_path(config('camera.processor_dir')),
            app(CameraTokenizer::class)->expand(config('camera.processor_inc'), $this->camera)
        );
        Log::debug("Camera currenthandler inc file: $inc");
        if (file_exists($inc)) {
            $processor = include($inc);
            $processor($image, $this->camera);
        }
    }
}
