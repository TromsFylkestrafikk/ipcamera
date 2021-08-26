<?php

namespace TromsFylkestrafikk\Camera\Console;

use TromsFylkestrafikk\Camera\Models\Camera;
use Illuminate\Console\Command;

class CameraShow extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'camera:show
                            { id : Camera (Laravel) ID }';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Show full info about given camera';

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
        $cameraArray = $camera->toArray();
        $cols = [];
        foreach ($cameraArray as $key => $val) {
            $cols[] = [$key, $val];
        }
        $this->table(['Property', 'Value'], $cols);
        return 0;
    }
}
