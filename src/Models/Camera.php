<?php

namespace TromsFylkestrafikk\Camera\Models;

use DateTime;
use DateInterval;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Storage;
use TromsFylkestrafikk\Camera\Services\CameraTokenizer;

/**
 * TromsFylkestrafikk\Camera\Models\Camera
 *
 * @property int $id Laravel internal ID
 * @property string $camera_id Custom camera ID
 * @property string|null $name Custom name of camera
 * @property string|null $model Camera maker and model
 * @property string|null $ip IP address of camera in field
 * @property string|null $mac Camera MAC address
 * @property float|null $latitude Camera's latitude in the field
 * @property float|null $longitude Camera's longitude in the field
 * @property int $active Camera is active and receiving images
 * @property \datetime|null $created_at
 * @property \datetime|null $updated_at
 * @property-read string $dir
 * @property-read string $file_path_regex
 * @property-read string $file_regex
 * @property-read string $full_dir
 * @property-read string $full_incoming_dir
 * @property-read bool $has_stalled
 * @property-read string $incoming_dir
 * @property-read \Illuminate\Database\Eloquent\Collection|\TromsFylkestrafikk\Camera\Models\Picture[] $pictures
 * @property-read int|null $pictures_count
 * @method static \Illuminate\Database\Eloquent\Builder|Camera isActive()
 * @method static \Illuminate\Database\Eloquent\Builder|Camera newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Camera newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Camera query()
 * @method static \Illuminate\Database\Eloquent\Builder|Camera stale()
 * @method static \Illuminate\Database\Eloquent\Builder|Camera whereActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Camera whereCameraId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Camera whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Camera whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Camera whereIp($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Camera whereLatitude($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Camera whereLongitude($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Camera whereMac($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Camera whereModel($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Camera whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Camera whereUpdatedAt($value)
 * @mixin \Eloquent
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
        $modDate = new DateTime($this->updated_at);
        $minDate = (new DateTime())->sub(new DateInterval($maxAge));
        return $modDate < $minDate;
    }

    /**
     * The expanded relative path for this camera's incoming images.
     *
     * @return string
     */
    public function getIncomingDirAttribute()
    {
        $tokenizer = App::make(CameraTokenizer::class);
        return trim($tokenizer->expand(config('camera.incoming_folder'), $this), '/');
    }

    /**
     * The full file system path for this camera's images.
     *
     * @return string
     */
    public function getFullIncomingDirAttribute()
    {
        return Storage::disk(config('camera.incoming_disk'))->path($this->incomingDir);
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
        return Storage::disk(config('camera.disk'))->path($this->dir);
    }

    /**
     * Regular expression of picture files within camera directory.
     *
     * @return string
     */
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
        return preg_quote($this->camera->full_dir) . '/' . $this->file_regex;
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
        return $query->whereDate('updated_at', '<', $expired);
    }
}
