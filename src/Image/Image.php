<?php

namespace TromsFylkestrafikk\Camera\Image;

use DateTime;
use DateTimeZone;
use Illuminate\Routing\UrlGenerator;
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
        'url',
        'expired',
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
     * The image URL
     */
    public $url = null;

    /**
     * @var bool
     */
    public $expired = null;

    /**
     * Create a new camera image.
     *
     * @param  \TromsFylkestrafikk\Camera\Models\Camera  $camera
     * @param  string  $imageFile
     * @return void
     */
    public function __construct(Camera $camera)
    {
        $this->camera = $camera;
        if ($this->isExpired()) {
            return;
        }
        $this->fileName = $camera->currentFile;
        $this->filePath = $camera->currentRelativePath;
        $this->modified = DateTime::createFromFormat(
            'U',
            filemtime($camera->currentPath),
            new DateTimeZone(config('app.timezone'))
        )->format('c');
        $this->mime = mime_content_type($camera->currentPath);
        $this->url = $this->getImageUrl($camera, $camera->currentPath);
    }

    /**
     * Return true if current image is expired.
     *
     * It will also return true if any errors occur, like currentFile doesn't
     * exist or is empty.
     *
     * @return bool
     */
    public function isExpired()
    {
        if ($this->expired !== null) {
            return $this->expired;
        }
        $this->expired = $this->isExpiredReal();
        return $this->expired;
    }

    protected function isExpiredReal()
    {
        $maxAge = config('camera.max_age');
        if (!$maxAge) {
            return false;
        }

        if (! $this->camera->currentFile) {
            return true;
        }
        $modified = filemtime($this->camera->currentPath);
        if (!$modified) {
            return true;
        }
        $minDate = (new DateTime())->sub(new DateInterval($maxAge));
        $modDate = DateTime::createFromFormat('U', $modified);
        return $modDate < $minDate;
    }

    public function toArray()
    {
        $ret = [];
        foreach (self::$properties as $property) {
            $ret[$property] = $this->{$property};
        }
        return $ret;
    }

    protected function getImageUrl()
    {
        $base64Threshold = config('camera.base64_encode_below');
        if (!$base64Threshold || filesize($this->camera->currentPath) > $base64Threshold) {
            return url()->route('camera.file', [
                'camera' => $this->camera->id,
                'file' => $this->camera->currentFile
            ]);
        }
        return sprintf(
            "data:%s;base64,%s",
            $this->mime,
            base64_encode(file_get_contents($this->camera->currentPath))
        );
    }
}
