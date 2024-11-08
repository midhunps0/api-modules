<?php
namespace Modules\Ynotz\MediaManager\Contracts;

use Illuminate\Support\Collection;

interface GalleryInterface
{
    public function getMediaItems(): Collection;
}
?>
