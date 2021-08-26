<?php

namespace TromsFylkestrafikk\Camera\Console;

use TromsFylkestrafikk\Camera\Models\Camera;
use Illuminate\Console\Command;

class CameraRemove extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'camera:rm
                           { id : Camera (Laravel) ID of camera to remove }
                           { --f|force : Force removal }';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove camera';

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
    public function handle()
    {
        $id = $this->argument('id');
        $camera = Camera::find($id);
        if (!$camera) {
            $this->error("Camera not found");
            return 1;
        }
        if (
            $this->option('force') || $this->confirm(sprintf(
                "Really delete camera '%s'",
                $camera->name
            ), false)
        ) {
            $camera->delete();
            $this->info(sprintf(
                "Camera '%s' with ID '%d' was successfully deleted",
                $camera->name,
                $camera->id
            ));
        } else {
            $this->info("Operation aborted");
        }
        return 0;
    }
}
