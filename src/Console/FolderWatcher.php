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
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
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
        $notifier = inotify_init();
        $watchers = [];

        // Add watchers for all available directories.
        foreach ($cameras as $camera) {
            $folder = $disk->path($tokenizer->expand($folderPattern, $camera));
            $this->line("Folder: $folder");
            if (!isset($watchers[$folder])) {
                $watchers[$folder] = inotify_add_watch($notifier, $folder, IN_MODIFY | IN_MOVED_TO | IN_CREATE);
            }
        }
        if (!count($watchers)) {
            $this->info("Could not find any folders to watch");
            return 0;
        }
        while (true) {
            $events = inotify_read($notifier);
            dump($events);
        }
        return 0;
    }
}
