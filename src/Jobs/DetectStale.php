<?php

namespace TromsFylkestrafikk\Camera\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use TromsFylkestrafikk\Camera\Models\Camera;
use TromsFylkestrafikk\Camera\Image\Image;
use TromsFylkestrafikk\Camera\Services\CurrentHandler;

/**
 * Detect active cameras with stale imagery.
 */
class DetectStale implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        foreach (Camera::active()->stale()->get() as $staleCamera) {
            $updater = new CurrentHandler($staleCamera);
            $image = $updater->getImage();
        }
    }
}
