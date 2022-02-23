<?php

namespace TromsFylkestrafikk\Camera\Services;

use Exception;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManagerStatic;
use Intervention\Image\Image;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Finder\Finder;
use TromsFylkestrafikk\Camera\Models\Camera;
use TromsFylkestrafikk\Camera\Models\Picture;

/**
 * Logic around camera's 'currentFile' handling.
 */
class CameraService
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
     * Static method accessor.
     *
     * @param string $methodName
     * @param array $arguments
     *
     * @return self
     */
    public static function with(Camera $camera)
    {
        return new static($camera);
    }
    /**
     * Keep camera model up to date with real life.
     *
     * This is a janitor for the camera. It:
     *   1) Asserts the required directories exists.
     *   2) Scans for and add new pictures not present in db.
     *   3) De-activates camera if the imagery is outdated.
     *
     * @return self
     */
    public function refresh()
    {
        $this->ensureFoldersExists();
        $this->addNewFiles();
        $this->deactivateIfStalled();
        return $this;
    }

    /**
     * Check and create necessary directories for camera.
     *
     * @return bool True on success.
     */
    public function ensureFoldersExists()
    {
        $incomingDisk = config('camera.incoming_disk');
        $disk = config('camera.disk');
        $ret = $this->createIfMissing($incomingDisk, $this->camera->incomingDir);
        if ($disk !== $incomingDisk) {
            return $ret && $this->createIfMissing($disk, $this->camera->dir);
        }
        return $ret;
    }

    /**
     * Deactivate camera if not receiving pictures anymore.
     *
     * @return self
     */
    public function deactivateIfStalled()
    {
        $this->camera->active = !$this->camera->hasStalled;
        if (!$this->camera->active && $this->camera->isDirty()) {
            Log::notice(sprintf(
                "Camera %d (%s) is not receiving images anymore. De-activating",
                $this->camera->id,
                $this->camera->name
            ));
        }
        $this->camera->save();
        return $this;
    }

    /**
     * Scan for images not present as picture models.
     *
     * @return \TromsFylkestrafikk\Camera\Models\Picture[]
     */
    public function addNewFiles()
    {
        $newFiles = array_map(
            fn (SplFileInfo $item) => $item->getRelativePathname(),
            $this->findNewFiles(10)
        );
        // Filter out existing ones.
        $newFiles = array_diff($newFiles, Picture::where('camera_id', $this->camera->id)
            ->whereIn('filename', $newFiles)
            ->get()
            ->pluck('filename')
            ->toArray());

        $new = [];
        foreach ($newFiles as $newFile) {
            $new[] = $this->createPicture($this->camera->fullIncomingDir . '/' . $newFile);
        }
        return $new;
    }

    /**
     * Create new Picture for camera using given file.
     *
     * This kicks off an image modification pipeline which allows interestees to
     * modify the image as an Intervention\Image\Image wrapper.
     *
     * @param string $inFile
     *
     * @return \TromsFylkestrafikk\Camera\Models\Picture
     */
    public function createPicture($inFile)
    {
        if (config('camera.incoming_disk') === config('camera.disk')) {
            $this->info("Incoming disk same as target. Not modifying incoming imagery", 'vv');
            return;
        }
        $picture = new Picture();
        $picture->camera_id = $this->camera->id;
        $picture->filename = basename($inFile);
        $image = ImageManagerStatic::make($inFile);
        $image = $this->applyImageManipulations($image);
        $outFile = $picture->fullPath;
        $image->save($outFile);
        // Sync modification time from input to output file.
        touch($outFile, filemtime($inFile));
        $picture->fill([
            'mime' => mime_content_type($outFile),
            'size' => filesize($outFile),
        ]);
        $picture->save();
        $this->camera->active = true;
        $this->camera->touch();
        return $picture;
    }

    /**
     * Send picture through manipulation pipeline.
     *
     * @param \Intervention\Image\Image $image
     *
     * @return \Intervention\Image\Image
     */
    protected function applyImageManipulations(Image $image) {
        return app(Pipeline::class)->send([
            'image' => $image,
            'camera' => $this->camera
        ])->through(config('camera.manipulators', []))
            ->then(function ($result) {
                return $result['image'];
            });
    }

    /**
     * Get a list of new files not present in db.
     *
     * @param string $directory  The directory to look in. Defaults to
     *   configured published directory for this camera.
     *
     * @return \Symfony\Component\Finder\SplFileInfo[]
     */
    protected function findNewFiles($count = null)
    {
        $filePattern = "|{$this->camera->fileRegex}$|";
        $directory = $this->camera->fullIncomingDir;
        $ret = iterator_to_array(
            Finder::create()
                ->files()
                ->in($directory)
                ->name($filePattern)
                ->filter(fn (SplFileInfo $finfo)
                    => Carbon::createFromFormat('U', $finfo->getMTime()) > new Carbon($this->camera->updated_at))
                ->sortByChangedTime()
                ->reverseSorting(),
            false
        );
        return $count ? array_slice($ret, 0, $count) : $ret;
    }

    /**
     * Create necessary directories for camera if they do not exist.
     *
     * @return bool
     */
    protected function createIfMissing($diskName, $folder)
    {
        $disk = Storage::disk($diskName);

        if (!$disk->has($folder)) {
            return $disk->makeDirectory($folder);
        }
        return true;
    }
}
