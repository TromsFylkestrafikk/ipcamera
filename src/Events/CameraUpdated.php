<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CameraUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    protected $cameraId;
    protected $imageFile;

    /**
     * Create a new event instance.
     *
     * @param  string  $cameraId
     * @param  string  $imageFile
     * @return void
     */
    public function __construct($cameraId, $imageFile)
    {
        $this->cameraId = $cameraId;
        $this->imageFile = $imageFile;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     */
    public function broadcastOn()
    {
        return new Channel('camera');
    }

    /**
     * Get the data to broadcast.
     *
     * @return array
     */
    public function broadcastWith()
    {
        return [
            'cameraId' => $this->cameraId,
            'imageFile' => $this->imageFile,
        ];
    }
}
