<?php

namespace TromsFylkestrafikk\Camera\Models;

use DateTime;
use DateInterval;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use TromsFylkestrafikk\Camera\Services\CameraTokenizer;

/**
 * @property int $id
 * @property string $camera_id
 * @property string $name
 * @property string $model
 * @property string $ip
 * @property string $mac
 * @property float $latitude
 * @property float $longitude
 * @property string $currentFile
 * @property string $folder Relative path to camera's folder
 * @property string $folderPath Full path to camera's folder
 * @property string $fileRegex Regex pattern for this camera's images
 * @property string $filePathRegex Full file path regex for camera's images
 * @property string $currentPath Full path to camera's current file
 * @property string $currentRelativePath Relative path to camera's current file
 * @property string $cacheKey Suitable cache key for this camera
 * @property string $currentCacheKey Cache key for suitable for current file.
 * @method self active() Camera is actively receiving imagery
 * @method self stale() Camera isn't updated in 'max_age' interval.
 */
class Camera extends Model
{
    use HasFactory;

    protected $table = 'ip_cameras';
    protected $guarded = ['id'];
    protected $hidden = ['ip', 'mac'];
    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

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
     * Add cache around current image when set.
     */
    public function setCurrentFileAttribute($image)
    {
        $timeout = config('camera.cache_current');
        if ($timeout) {
            Cache::put($this->currentCacheKey, $image, config('camera.cache_current'));
        }
        $this->attributes['currentFile'] = $image;
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return self
     */
    public function scopeActive($query)
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
        return $query->where('updated_at', '<', $expired);
    }
}
