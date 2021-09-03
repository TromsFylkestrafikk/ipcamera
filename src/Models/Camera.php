<?php

namespace TromsFylkestrafikk\Camera\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Storage;
use TromsFylkestrafikk\Camera\Services\CameraTokenizer;

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
}
