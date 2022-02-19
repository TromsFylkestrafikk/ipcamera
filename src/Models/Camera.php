<?php

namespace TromsFylkestrafikk\Camera\Models;

use DateTime;
use DateTimeZone;
use DateInterval;
use Illuminate\Broadcasting\Channel;
use Illuminate\Database\Eloquent\BroadcastsEvents;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;
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
 * @property  bool    $active               Camera is actively receiving imagery
 * @property  string  $created_at           Creation timestamp on model.
 * @property  string  $updated_at           Last modification timestamp on model.
 * @property  string  $incoming             Relative path to camera's incoming folder.
 * @property  string  $fullIncoming         Full path to camera's incoming folder
 * @property  string  $dir                  Relative path to camera's folder
 * @property  string  $fullDir              Full file system folder path to camera's folder
 * @property  bool    $hasStalled           Image updates has stalled.
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

    public function broadcastOn($event)
    {
        return $event === 'updated' ? new Channel($this) : null;
    }

    public function pictures()
    {
        return $this->hasMany(Picture::class);
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
     * The expanded relative path for this camera's incoming images.
     *
     * @return string
     */
    public function getIncomingAttribute()
    {
        $tokenizer = App::make(CameraTokenizer::class);
        return trim($tokenizer->expand(config('camera.incoming_folder'), $this), '/');
    }

    /**
     * The full file system path for this camera's images.
     *
     * @return string
     */
    public function getFullIncomingAttribute()
    {
        return Storage::disk(config('camera.incoming_disk'))->path($this->diskIncoming);
    }

    /**
     * Camera's folder path, as utilized by Laravel disks.
     *
     * @return string
     */
    public function getDirAttribute()
    {
        $tokenizer = App::make(CameraTokenizer::class);
        return trim($tokenizer->expand(config('camera.folder'), $this), '/');
    }

    /**
     * The full file system folder path for this camera's images.
     *
     * @return string
     */
    public function getFullDirAttribute()
    {
        return Storage::disk(config('camera.disk'))->path($this->folder);
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

    public function ensureFoldersExists()
    {
        $incomingDisk = config('camera.incoming_disk');
        $disk = config('camera.disk');
        $ret = $this->createIfMissing($incomingDisk, $this->diskIncoming);
        if ($disk !== $incomingDisk) {
            return $ret && $this->createIfMissing($disk, $this->folder);
        }
        return $ret;
    }

    /**
     * Create necessary directories for camera if they do not exist.
     *
     * @return bool
     */
    protected function createIfMissing($diskName, $folder)
    {
        $disk = Storage::disk($diskName);

        if (!$disk->has($folder)) {
            return $disk->makeDirectory($folder);
        }
        return true;
    }
}
