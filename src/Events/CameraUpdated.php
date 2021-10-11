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
    public $fileName = null;

    /**
     * The relative disk path to image.
     *
     * @var string
     */
    public $filePath = null;

    /**
     * Last modification date of image.
     */
    public $modified = null;

    /**
     * The mime type of image.
     *
     * @var string
     */
    public $mime = null;

    /**
     * Image encoded in base64
     *
     * @var string
     */
    public $base64 = null;

    /**
     * The image URL
     */
    public $url = null;

    /**
     * Create a new event instance.
     *
     * @param  \TromsFylkestrafikk\Camera\Models\Camera  $camera
     * @param  string  $imageFile
     * @return void
     */
    public function __construct(Camera $camera, $imageFile)
    {
        $this->camera = $camera;
        if ($imageFile === null) {
            return;
        }
        $imagePath = $camera->folderPath . '/' . $imageFile;
        $this->mime = mime_content_type($imagePath);
        $this->base64 = filesize($imagePath) < config('camera.base64_encode_below')
            ? base64_encode(file_get_contents($imagePath))
            :  null;
        $this->fileName = $imageFile;
        $this->filePath = $camera->folder . '/' . $imageFile;
        $this->modified = DateTime::createFromFormat('U', filemtime($imagePath))->format('c');
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
