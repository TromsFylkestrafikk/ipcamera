<?php

namespace TromsFylkestrafikk\Camera\Models;

use DateTime;
use DateTimeZone;
use DateInterval;
use Illuminate\Broadcasting\Channel;
use Illuminate\Database\Eloquent\BroadcastsEvents;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use TromsFylkestrafikk\Camera\Services\CameraTokenizer;

/**
 * @property  int     $id                   Laravel ID on camera.
 * @property  string  $camera_id            The internal camera ID.
 * @property  string  $name                 Your custom name on camera
 * @property  string  $model                Camera manufacturer and model
 * @property  string  $ip
 * @property  string  $mac
 * @property  float   $latitude
 * @property  float   $longitude
 * @property  string  $currentFile          Current file
 * @property  string  $currentMime          Mime of current file.
 * @property  string  $currentDate          Date when current file was received
 * @property  bool    $active               Camera is actively receiving imagery
 * @property  string  $created_at           Creation timestamp on model.
 * @property  string  $updated_at           Last modification timestamp on model.
 * @property  string  $folder               Relative path to camera's folder
 * @property  string  $folderPath           Full path to camera's folder
 * @property  string  $fileRegex            Regex pattern for this camera's images
 * @property  string  $filePathRegex        Full file path regex for camera's images
 * @property  string  $currentPath          Full path to camera's current file
 * @property  string  $currentRelativePath  Relative path to camera's current file
 * @property  string  $currentUrl           URL to current image.
 * @property  bool    $hasStalled           Image updates has stalled.
 * @property  string  $cacheKey             Suitable cache key for this camera
 * @property  string  $currentCacheKey      Cache key for suitable for current file.
 * @method    self    isActive()            Query scope: Camera is actively receiving imagery
 * @method    self    stale()               Query scope: Camera isn't updated in 'max_age' interval.
 */
class Camera extends Model
{
    use HasFactory;
    use BroadcastsEvents;

    protected $table = 'ip_cameras';
    protected $guarded = ['id'];
    protected $hidden = ['ip', 'mac'];
    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];
    protected $appends = [
        'currentUrl',
        'currentRelativePath',
    ];

    public function broadcastOn($event)
    {
        return $event === 'updated' ? new Channel($this) : null;
    }

    /**
     * Get the full path to the camera's current file.
     *
     * @return string
     */
    public function getCurrentPathAttribute()
    {
        return $this->currentFile ? $this->folderPath . '/' . $this->currentFile : null;
    }

    /**
     * Get the relative path to this cameras current file.
     *
     * @return string
     */
    public function getCurrentRelativePathAttribute()
    {
        return $this->currentFile ? $this->folder . '/' . $this->currentFile : null;
    }

    /**
     * Get modification date of current image
     *
     * @return \DateTime
     */
    protected function getCurrentImageDate()
    {
        if (!$this->currentFile) {
            return null;
        }
        $modified = filemtime($this->currentPath);
        return DateTime::createFromFormat('U', $modified)
            ->setTimezone(new DateTimeZone(config('app.timezone')));
    }

    /**
     * URL of current image.
     *
     * @return string
     */
    public function getCurrentUrlAttribute()
    {
        if (!$this->active || !$this->currentFile) {
            return null;
        }
        $base64Threshold = config('camera.base64_encode_below');
        if (!$base64Threshold || filesize($this->currentPath) > $base64Threshold) {
            return url()->route('camera.file', [
                'camera' => $this->id,
                'file' => $this->currentFile
            ]);
        }
        return sprintf(
            "data:%s;base64,%s",
            $this->currentMime,
            base64_encode(file_get_contents($this->currentPath))
        );
    }

    /**
     * Last image is older than max age of cameras.
     *
     * @return bool
     */
    public function getHasStalledAttribute()
    {
        $maxAge = config('camera.max_age');
        if (!$maxAge) {
            return false;
        }
        $modDate = new DateTime($this->currentDate);
        if (! $modDate) {
            return true;
        }
        $minDate = (new DateTime())->sub(new DateInterval($maxAge));
        return $modDate < $minDate;
    }

    /**
     * The expanded relative path for this camera's images.
     *
     * @return string
     */
    public function getFolderAttribute()
    {
        $tokenizer = App::make(CameraTokenizer::class);
        return trim($tokenizer->expand(config('camera.folder'), $this), '/');
    }

    public function getFileRegexAttribute()
    {
        $tokenizer = App::make(CameraTokenizer::class);
        return $tokenizer->expand(config('camera.file_regex'), $this, true);
    }

    /**
     * The full file system path for this camera's images.
     *
     * @return string
     */
    public function getFolderPathAttribute()
    {
        return Storage::disk(config('camera.disk'))->path($this->folder);
    }

    public function getFilePathRegexAttribute()
    {
        return preg_quote($this->folderPath) . '/' . $this->fileRegex;
    }

    /**
     * Get a decent cache key for this model
     *
     * @return string
     */
    public function getCacheKeyAttribute()
    {
        return "tromsfylkestrafikk.camera.{$this->id}";
    }

    public function getCurrentCacheKeyAttribute()
    {
        return $this->cacheKey . '.current';
    }

    /**
     * Set file and w/it all file dependent attributes.
     */
    public function setCurrentFileAttribute($fileName)
    {
        $timeout = config('camera.cache_current');
        if ($timeout) {
            Cache::put($this->currentCacheKey, $fileName, config('camera.cache_current'));
        }
        $this->attributes['currentFile'] = $fileName;
        $this->attributes['currentMime'] = $fileName ? mime_content_type($this->currentPath) : null;
        $this->attributes['currentDate'] = $fileName ? $this->getCurrentImageDate()->format('Y-m-d H:i:s') : null;
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return self
     */
    public function scopeIsActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeStale($query)
    {
        $expires = config('camera.max_age');
        if (!$expires) {
            return;
        }
        $expired = (new DateTime())->sub(new DateInterval($expires));
        return $query->where('currentDate', '<', $expired);
    }
}
