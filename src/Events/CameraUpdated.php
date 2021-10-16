<?php

namespace TromsFylkestrafikk\Camera\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use TromsFylkestrafikk\Camera\Models\Camera;

class CameraUpdated implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    /**
     * @var \TromsFylkestrafikk\Camera\Models\Camera
     */
    public $camera;


    /**
     * Create a new event instance.
     *
     * @param  \TromsFylkestrafikk\Camera\Models\Camera  $camera
     */
    public function __construct(Camera $camera)
    {
        $this->camera = $camera;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel
     */
    public function broadcastOn()
    {
        return new Channel("camera.{$this->camera->id}");
    }
}
