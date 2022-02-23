<?php

namespace TromsFylkestrafikk\Camera\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Pipeline\Pipeline;
use Intervention\Image\ImageManagerStatic;
use Intervention\Image\Image;
use Symfony\Component\Finder\SplFileInfo;
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
     * Keep camera model up to date with real life.
     *
     * This is a janitor for the camera. It:
     *   1) Find and add new pictures not present in db.
     *   2) De-activates camera if the imagery is outdated.
     *
     * @return \TromsFylkestrafikk\Camera\Models\Camera
     */
    public function refresh()
    {
        $this->camera->ensureFoldersExists();
        $new = $this->addNewFiles();
        if (count($new)) {
            $this->camera->touch();
        }
        $this->camera->active = !$this->camera->hasStalled;
        if (!$this->camera->active) {
            Log::warning(sprintf(
                "IpCamera: Camera %d (%s) isn't receiving imagery. Deactivating it.",
                $this->camera->id,
                $this->camera->name,
            ));
        }
        return $this->camera;
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
