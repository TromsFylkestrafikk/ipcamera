<?php

namespace TromsFylkestrafikk\Camera\Console;

use TromsFylkestrafikk\Camera\Models\Camera;
use Illuminate\Console\Command;

class CameraList extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'camera:list';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List available cameras';

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
        $cameras = Camera::select(['id', 'camera_id', 'name', 'active', 'updated_at'])
            ->get()
            ->map(function ($camera) {
                $camera->setAppends([]);
                return $camera;
            });
        $this->table(
            ['ID', 'Cam ID', 'Name', 'Live', 'Last update'],
            $cameras
        );
        return 0;
    }
}
