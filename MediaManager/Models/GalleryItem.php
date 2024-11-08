<?php

namespace Modules\Ynotz\MediaManager\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Ynotz\MediaManager\Contracts\MediaOwner;
use Modules\Ynotz\MediaManager\Traits\OwnsMedia;

class GalleryItem extends Model implements MediaOwner
{
    use HasFactory, OwnsMedia;

    public function getMediaStorage(): array
    {
        return [
            'image' => $this->storageLocation('public', 'gallery')
        ];
    }

    public function image(): Attribute
    {
        return Attribute::make(
            get: function ($v) {
                return $this->getSingleMediaForEAForm('image');
            }
        );
    }

    public function displayImage(): Attribute
    {
        return Attribute::make(
            get: function ($v) {
                return $this->getSingleMediaFilePath('image');
            }
        );
    }
}
