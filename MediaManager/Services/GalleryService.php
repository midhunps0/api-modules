<?php
namespace Modules\Ynotz\MediaManager\Services;

use Illuminate\Support\Collection;
use Modules\Ynotz\MediaManager\Contracts\GalleryInterface;
use Modules\Ynotz\MediaManager\Models\MediaItem;

class GalleryService implements GalleryInterface
{
    public function getMediaItems(): Collection
    {
        return MediaItem::all();
    }
}
?>
