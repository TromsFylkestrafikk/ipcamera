<?php

namespace TromsFylkestrafikk\Camera\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Spatie\Image\Image;
use TromsFylkestrafikk\Camera\Models\Camera;

class ProcessImage
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public $camera;
    public $image;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Camera $camera, Image $image)
    {
        $this->camera = $camera;
        $this->image = $image;
    }
}
