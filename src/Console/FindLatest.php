<?php

namespace TromsFylkestrafikk\Camera\Console;

use Illuminate\Console\Command;
use TromsFylkestrafikk\Camera\Models\Camera;
use TromsFylkestrafikk\Camera\Services\CameraService;

/**
 * Find latest image file per camera and update models.
 */
class FindLatest extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'camera:latest';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Find all latest images for all cameras';

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
        foreach (Camera::all() as $camera) {
            $new = CameraService::with($camera)->addNewFiles();
            if ($new) {
                $this->info(sprintf("Camera %d, %s: Added %d new files", $camera->id, $camera->name, count($new)));
            }
        }
        return 0;
    }
}
