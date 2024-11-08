<?php

use Modules\Ynotz\MediaManager\Services\GalleryService;

    return [
        'temp_disk' => 'public',
        'temp_folder' => 'tmp',
        'temp_thumbnail_folder' => 'thumbnail',
        'images_disk' => 'local',
        'videos_disk' => 'local',
        'files_disk' => 'local',
        'images_folder' => 'public/images',
        'videos_folder' => 'public/videos',
        'files_folder' => 'public/files',
        'gallery_service' => GalleryService::class,
        'gallery_route' => 'mediamanager.gallery',
        'ulid_separator' => '_::_',
        'tmp_separator' => '_::::_',
        'upload_thumbnail_size' => [
            'width' => 150,
            'height' => 150
        ]
    ];
?>
