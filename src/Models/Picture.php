<?php

namespace TromsFylkestrafikk\Camera\Models;

use DateTime;
use DateInterval;
use Illuminate\Broadcasting\Channel;
use Illuminate\Database\Eloquent\BroadcastsEvents;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * TromsFylkestrafikk\Camera\Models\Picture
 *
 * @property int $id
 * @property int $camera_id Reference to camera model
 * @property string $filename File name of picture
 * @property string $mime File's mime type
 * @property int $size File's final file size
 * @property bool $published File is processed and ready for publishing
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \TromsFylkestrafikk\Camera\Models\Camera|null $camera
 * @property-read bool $expired True if this picture is older than configured expiry duration
 * @property-read string $full_path Full file system path of picture
 * @property-read string $path Picture path relative to configured camera disk
 * @property-read string $full_incoming_path Full file system path of incoming, unprocessed picture.
 * @property-read string $incoming_path Picture incoming path relative to configured camera incoming disk.
 * @property-read mixed $url URL to binary picture file, suitable for <img src>
 * @method static \Illuminate\Database\Eloquent\Builder|Picture newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Picture newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Picture query()
 * @method static \Illuminate\Database\Eloquent\Builder|Picture published()
 * @method static \Illuminate\Database\Eloquent\Builder|Picture whereCameraId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Picture whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Picture whereFilename($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Picture whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Picture whereMime($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Picture whereSize($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Picture whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Picture extends Model
{
    use HasFactory;
    use BroadcastsEvents;

    protected $table = 'ip_camera_pictures';
    protected $fillable = ['filename', 'mime', 'size', 'published'];

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
        return $this->camera->full_dir . '/' . $this->filename;
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

    /**
     * Picture's full path to incoming file.
     *
     * @return string
     */
    public function getFullIncomingPathAttribute()
    {
        return $this->camera->full_incoming_dir . '/' . $this->filename;
    }

    /**
     * Picture's incoming file path, relative to incoming disk.
     *
     * @return string
     */
    public function getIncomingPathAttribute()
    {
        return $this->camera->incoming_dir . '/' . $this->filename;
    }

    /**
     * True if this picture is older than configured expiry duration.
     *
     * @return bool
     */
    public function getExpiredAttribute()
    {
        $expires = config('camera.max_age');
        if (!$expires) {
            return false;
        }
        $expiry = (new DateTime())->sub(new DateInterval($expires));
        return $expiry > new DateTime($this->created_at);
    }

    /**
     * URL to binary picture file, suitable for <img src>
     *
     * @return string
     */
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
            $this->mime,
            base64_encode(file_get_contents($this->path))
        );
    }

    public function broadcastOn($event)
    {
        if (!$this->published) {
            return;
        }
        if (in_array($event, ['created', 'updated'])) {
            return new Channel($this->camera);
        }
    }

    /**
     * Only fetch published pictures.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     */
    public function scopePublished($query)
    {
        return $query->where('published', true);
    }
}
