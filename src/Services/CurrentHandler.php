<?php

namespace TromsFylkestrafikk\Camera\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Pipeline\Pipeline;
use Intervention\Image\ImageManagerStatic;
use Intervention\Image\Image;
use Symfony\Component\Finder\Finder;
use TromsFylkestrafikk\Camera\Models\Camera;
use TromsFylkestrafikk\Camera\Models\Picture;

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
        $this->camera->ensureFoldersExists();
        $latestFile = $this->findLatestFile($this->camera->fullIncomingDir);
        if ($this->camera->currentFile !== $latestFile) {
            $incomingFile = $this->camera->fullIncomingDir . '/' . $latestFile;
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
            $directory = $this->camera->fullDir;
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
     * Create new Picture for camera using given file.
     *
     * This kicks off an image modification pipeline which allows interestees to
     * modify the image as an Intervention\Image\Image wrapper.
     *
     * @param \TromsFylkestrafikk\Camera\Models\Camera $camera
     * @param string $inFile
     *
     * @return \TromsFylkestrafikk\Camera\Models\Picture
     */
    public function createPicture($camera, $inFile)
    {
        if (config('camera.incoming_disk') === config('camera.disk')) {
            $this->info("Incoming disk same as target. Not modifying incoming imagery", 'vv');
            return;
        }
        $picture = new Picture();
        $picture->camera_id = $camera->id;
        $picture->filename = basename($inFile);
        $outFile = $picture->fullPath;
        /** @var \Intervention\Image\Image $image */
        $image = ImageManagerStatic::make($inFile);
        $image = $this->applyImageManipulations($image);
        $image->save($outFile);
        // Sync modification time from input to output file.
        touch($outFile, filemtime($inFile));
        $picture->fill([
            'mime' => mime_content_type($outFile),
            'size' => filesize($outFile),
        ]);
        $picture->save();
    }

    protected function applyImageManipulations(Image $image) {
        return app(Pipeline::class)->send([
            'image' => $image,
            'camera' => $this->camera
        ])->through(config('camera.manipulators', []))
            ->then(function ($result) {
                return $result['image'];
            });
    }
}
