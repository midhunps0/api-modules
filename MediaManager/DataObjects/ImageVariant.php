<?php
namespace Modules\Ynotz\MediaManager\DataObjects;

class ImageVariant
{
    public function __construct(
        public string $name,
        public int $maxWidth,
        public int $maxHeight
    )
    {
        # code...
    }
}
