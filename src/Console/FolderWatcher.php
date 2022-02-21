<?php

namespace TromsFylkestrafikk\Camera\Console;

use Exception;
use Illuminate\Console\Command;
use TromsFylkestrafikk\Camera\Models\Camera;
use TromsFylkestrafikk\Camera\Models\Picture;
use TromsFylkestrafikk\Camera\Services\CurrentHandler;

/**
 * Watch for new image files and broadcast its presence.
 */
class FolderWatcher extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'camera:watch';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Folder watcher. Broadcast to listening channels when images are modified or dumped to disk.';

    /**
     * List of wathed directories as inotify watch descriptors, keyed by descriptor.
     *
     * @var array
     */
    protected $wDescs;

    /**
     * List of watched descriptors and cameras keyed by folder names.
     *
     * @var array
     */
    protected $wDirs;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->watched = [];
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        if (!extension_loaded('inotify')) {
            $this->warn("The Inotify extension is required for this service.");
            $this->info("  - https://www.php.net/manual/en/book.inotify.php\n  - https://pecl.php.net/package/inotify");
            return 1;
        }

        $cameras = Camera::all();
        $notifier = inotify_init();

        // Add watchers for all available directories.
        $this->wDirs = [];
        foreach ($cameras as $camera) {
            // @var \TromsFylkestrafikk\Camera\Models\Camera $camera
            $exists = $camera->ensureFoldersExists();
            if (!$exists) {
                $this->warn("Failed to create necessary directories for {$camera->name}: {$camera->fullIncomingDir}, {$camera->fullDir}");
                continue;
            }
            $folder = $camera->fullIncomingDir;
            $this->info("Looking at folder: $folder", 'vv');
            if (!isset($this->wDirs[$folder])) {
                $wd = inotify_add_watch($notifier, $folder, IN_CLOSE_WRITE);
                if (!$wd) {
                    $this->warn("Could not create a inotify watch descriptor on directory $folder.");
                    continue;
                }
                $this->info("Adding inotify listener on folder: '{$folder}'", 'v');
                $this->wDirs[$folder] = [
                    'wd' => $wd,
                    'cameras' => [$camera],
                ];
                $this->wDescs[$wd] = $folder;
            } else {
                $this->warn("Several cameras share same dir: $folder");
                $this->wDirs[$folder]['cameras'][] = $camera;
            }
        }
        if (!count($this->wDirs)) {
            $this->info("No folders found. Nothing to do.");
            return 0;
        }
        while (true) {
            $events = inotify_read($notifier);
            $this->info(sprintf("DEBUG: Got %d inotify events", count($events)), 'vvv');
            try {
                $this->handleInotifyEvents($events);
            } catch (Exception $e) {
                $this->error(sprintf(
                    "Error: Exception in inotify event handler (%s[%d]): %s",
                    $e->getFile(),
                    $e->getLine(),
                    $e->getMessage()
                ));
            }
        }
        return 0;
    }

    protected function handleInotifyEvents($events)
    {
        $filesSeen = [];
        foreach ($events as $event) {
            $dir = $this->wDescs[$event['wd']];
            $fileName = $event['name'];
            $filePath = $dir . '/' . $fileName;
            $this->info("Incoming file: '{$filePath}'", 'v');
            // No need to broadcast several events on the same file.
            if (!empty($filesSeen[$filePath])) {
                $this->info("File already seen. continue", 'v');
                continue;
            }
            $filesSeen[$filePath] = true;
            // And we don't care what kind of event ($event['mask']) we handle
            // since we don't know the mechanisms behind populating the
            // destination directories with new images.
            $camera = $this->getCameraFromEvent($event, $filePath);
            if (!$camera) {
                $this->info(sprintf("No camera found for icoming file '%s'", $filePath), 'v');
                continue;
            }
            $camera->refresh();
            $this->info("Camera found: '{$camera->name}'. Broadcasting.", 'vv');
            $curHandler = new CurrentHandler($camera);
            $picture = $curHandler->createPicture($camera, $filePath);
            $camera->active = true;
            $camera->save();
        }
    }

    /**
     * Get the IP camera that this new file belongs to.
     *
     * I.e. Reverse filename => Camera instance lookup.
     *
     * @param array $event
     *
     * @return \TromsFylkestrafikk\Camera\Models\Camera
     */
    protected function getCameraFromEvent($event)
    {
        $dir = $this->wDescs[$event['wd']];
        $cameras = $this->wDirs[$dir]['cameras'];
        $filePath = $dir . '/' . $event['name'];
        if (count($cameras) === 1) {
            return $cameras[0];
        }
        // Shit. We need to loop through all cameras, expand the full configured
        // path regex for destination images, and see which one that matches
        // the full file path of the dumped image.  Aaand hope that not several
        // cameras matches the same file.
        $pickFirst = config('camera.pick_first_match');
        $camera = null;
        foreach ($cameras as $camCand) {
            $filePathRegex = "|^{$camCand->filePathRegex}$|";
            $this->info("DEBUG: Comparing files:\n  - {$filePath}\n  - {$filePathRegex}", 'vvv');
            if (preg_match($filePathRegex, $filePath)) {
                $this->info("DEBUG: Got hit on $filePath", 'vvv');
                if ($pickFirst) {
                    $this->info("DEBUG: Config set to pick first. Returning '{$camCand->name}'", 'vvv');
                    return $camCand;
                }
                if ($camera) {
                    $this->error(sprintf(
                        "Several cameras match incoming file %s:\n  - %s\n  - %s\n  - ...\nAborting!",
                        $filePath,
                        $camera->name,
                        $camCand->name
                    ));
                    return null;
                }
                $camera = $camCand;
            }
        }
        return $camera;
    }
}
