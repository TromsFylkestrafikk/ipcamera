<?php

namespace TromsFylkestrafikk\Camera\Image;

use DateTime;
use TromsFylkestrafikk\Camera\Models\Camera;
use TromsFylkestrafikk\Camera\Services\CameraTokenizer;

/**
 * Meta (and possibly actual) data of a single camera image.
 */
class Image
{
    /**
     * @var \TromsFylkestrafikk\Camera\Models\Camera
     */
    protected $camera = null;

    /**
     * Properties dragged with toArray() cast.
     */
    protected static $properties = [
        'fileName',
        'filePath',
        'modified',
        'mime',
        'base64',
        'url',
    ];

    /**
     * The image filename.
     *
     * @var string
     */
    public $fileName = null;

    /**
     * The relative path to image (after config('camera.folder')).
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
     * Create a new camera image.
     *
     * @param  \TromsFylkestrafikk\Camera\Models\Camera  $camera
     * @param  string  $imageFile
     * @return void
     */
    public function __construct(Camera $camera, $imageFile = null)
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

    public function toArray()
    {
        $ret = [];
        foreach (self::$properties as $property) {
            $ret[$property] = $this->{$property};
        }
        return $ret;
    }
}
