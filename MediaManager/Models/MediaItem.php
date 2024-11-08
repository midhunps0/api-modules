<?php

namespace Modules\Ynotz\MediaManager\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MediaItem extends Model
{
    use HasFactory;

    protected $hidden = [
        'id',
        'created_at',
        'filepath',
        'updated_at',
        'disk'
    ];

    protected $fillable = [
        'ulid',
        'filename',
        'filepath',
        'disk',
        'type',
        'size',
        'mime_type',
    ];

    protected $appends = [
        'url'
    ];

    protected function url(): Attribute
    {
        return Attribute::make(
            get: fn ($value, $attributes) => Storage::disk($attributes['disk'])->url($attributes['filepath'])
        );
    }

    public function instances()
    {
        return $this->hasMany(MediaInstance::class, 'media_item_id', 'id');
    }
}
