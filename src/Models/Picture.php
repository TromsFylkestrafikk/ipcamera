<?php

namespace TromsFylkestrafikk\Camera\Models;

use Illuminate\Broadcasting\Channel;
use Illuminate\Database\Eloquent\BroadcastsEvents;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;
use TromsFylkestrafikk\Camera\Services\CameraTokenizer;

class Picture extends Model
{
    use HasFactory;
    use BroadcastsEvents;

    protected $table = 'ip_camera_pictures';
    protected $fillable = ['filename', 'mime', 'size'];

    protected $appends = ['url'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function camera() {
        return $this->belongsTo(Camera::class);
    }

    /**
     * Get the full file system path of picture.
     *
     * @return string
     */
    public function getFullPathAttribute()
    {
        return $this->camera->fullDir . '/' . $this->filename;
    }

    /**
     * Get the picture path relative to configured camera disk.
     *
     * @return string
     */
    public function getPathAttribute()
    {
        return $this->camera->dir . '/' . $this->filename;
    }

    public function getFileRegexAttribute()
    {
        $tokenizer = App::make(CameraTokenizer::class);
        return $tokenizer->expand(config('camera.file_regex'), $this, true);
    }

    /**
     * Get regex pattern for the full file system path for this camera.
     *
     * @return string
     */
    public function getFilePathRegexAttribute()
    {
        return preg_quote($this->camera->fullDir) . '/' . $this->fileRegex;
    }

    public function getUrlAttribute()
    {
        $base64Threshold = config('camera.base64_encode_below');
        if (!$base64Threshold || $this->size > $base64Threshold || !file_exists($this->path)) {
            return url()->route('camera.picture.download', [
                'picture' => $this,
            ]);
        }
        return sprintf(
            "data:%s;base64,%s",
            $this->currentMime,
            base64_encode(file_get_contents($this->path))
        );
    }

    public function broadcastOn($event)
    {
        if ($event === 'created') {
            return new Channel($this->camera);
        }
    }
}
