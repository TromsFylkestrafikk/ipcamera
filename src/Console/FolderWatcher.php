<?php

namespace TromsFylkestrafikk\Camera\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use TromsFylkestrafikk\Camera\Models\Camera;
use TromsFylkestrafikk\Camera\Services\CameraTokenizer;

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
     * @var \TromsFylkestrafikk\Camera\Services\CameraTokenizer
     */
    protected $tokenizer;

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
    public function handle(CameraTokenizer $tokenizer)
    {
        if (!extension_loaded('inotify')) {
            $this->warn("The Inotify extension is required for this service.");
            $this->line("  - https://www.php.net/manual/en/book.inotify.php\n  - https://pecl.php.net/package/inotify");
            return 1;
        }

        $cameras = Camera::all();
        $disk = Storage::disk(config('camera.disk'));
        $folderPattern = config('camera.folder');
        $this->info(config('camera.disk'));
        $this->line($folderPattern);
        $this->tokenizer = $tokenizer;
        $notifier = inotify_init();

        // Add watchers for all available directories.
        $this->wDirs = [];
        foreach ($cameras as $camera) {
            $folder = $disk->path($tokenizer->expand($folderPattern, $camera));
            $this->line("Folder: $folder");
            if (!isset($this->wDirs[$folder])) {
                $wd = inotify_add_watch($notifier, $folder, IN_MODIFY | IN_MOVED_TO | IN_CREATE);
                if (!$wd) {
                    $this->warn("Could not create a inotify watch descriptor on directory $folder.");
                    continue;
                }
                $this->wDirs[$folder] = [
                    'wd' => $wd,
                    'cameras' => [$camera],
                ];
                $this->wDescs[$wd] = $folder;
            } else {
                $this->wDirs[$folder]['cameras'][] = $camera;
            }
        }
        if (!count($this->wDirs)) {
            $this->info("Could not find any folders to watch");
            return 0;
        }
        while (true) {
            $events = inotify_read($notifier);
            $this->handleInotifyEvents($events);
        }
        return 0;
    }

    protected function handleInotifyEvents($events)
    {
        $filesSeen = [];
        foreach ($events as $event) {
            $dir = $this->wDescs[$event['wd']];
            $filePath = $dir . '/' . $event['name'];
            // No need to broadcast several events on the same file.
            if ($filesSeen[$filePath]) {
                continue;
            }
            $filesSeen[$filePath] = true;
            // And we don't care what kind of event ($event['mask']) we handle
            // since we don't know the mechanisms behind populating the
            // destination directories with new images.
            $camera = $this->getCameraFromEvent($event, $filePath);
            if (!$camera) {
                $this->warn(sprintf("No camera found for icoming file '%s'", $filePath));
                continue;
            }
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
        // path pattern for destination images, and see which one that matches
        // the full file path of the dumped image.  Aaand hope that not several
        // cameras matches the same file.
        $filePattern = config('camera.file_pattern');
        $pickFirst = config('camera.pick_first_match');
        $camera = null;
        foreach ($cameras as $camCand) {
            $fileRegex = $this->tokenizer->expand($filePattern, $camCand, true);
            $filePathRegex = sprintf("|^%s/%s$|", preg_quote($dir), $fileRegex);
            if (preg_match($filePathRegex, $filePath)) {
                if ($pickFirst) {
                    return $camCand;
                }
                if ($camera) {
                    throw new Exception(sprintf(
                        "Several cameras match incoming file %s:\n  - %s\n  - %s\n  - ...\nAborting!",
                        $filePath,
                        $camera->name,
                        $camCand->name
                    ));
                }
                $camera = $camCand;
            }
        }
        return $camera;
    }
}
