<?php

namespace TromsFylkestrafikk\Camera\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use TromsFylkestrafikk\Camera\Models\Camera;
use TromsFylkestrafikk\Camera\Services\CameraTokenizer;

class CameraUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $camera;

    /**
     * The image filename.
     *
     * @var string
     */
    public $fileName;

    /**
     * The relative disk path to image.
     *
     * @var string
     */
    public $filePath;

    /**
     * The mime type of image.
     *
     * @var string
     */
    public $mime;

    /**
     * Image encoded in base64
     *
     * @var string
     */
    public $base64;

    /**
     * The image URL
     */
    public $url;

    /**
     * Create a new event instance.
     *
     * @param  \TromsFylkestrafikk\Camera\Models\Camera  $camera
     * @param  string  $imageFile
     * @return void
     */
    public function __construct(Camera $camera, $imageFile)
    {
        $imagePath = $camera->folderPath . '/' . $imageFile;
        $this->camera = $camera;
        $this->mime = mime_content_type($imagePath);
        $this->base64 = base64_encode(file_get_contents($imagePath));
        $this->fileName = $imageFile;
        $this->filePath = $camera->folder . '/' . $imageFile;
        $this->url = null;
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
