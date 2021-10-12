<?php

namespace TromsFylkestrafikk\Camera\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use TromsFylkestrafikk\Camera\Image\Image;
use TromsFylkestrafikk\Camera\Models\Camera;

class CameraUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @var \TromsFylkestrafikk\Camera\Models\Camera
     */
    public $camera;

    /**
     * The image associated with this update.
     *
     * @var \TromsFylkestrafikk\Camera\Image\Image
     */
    public $image = null;

    /**
     * Create a new event instance.
     *
     * @param  \TromsFylkestrafikk\Camera\Models\Camera  $camera
     * @param  string  $imageFile
     * @return void
     */
    public function __construct(Camera $camera, Image $image)
    {
        $this->camera = $camera;
        $this->image = $image;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel
     */
    public function broadcastOn()
    {
        return new Channel("camera.{$this->camera->id}");
    }
}
