<?php
namespace Modules\Ynotz\MediaManager\Helpers;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MediaHelper
{
    public static function tempFileUpload($file)
    {
        $name = $file->getClientOriginalName();
        $name = str_replace($file->getClientOriginalExtension(), '', $name);
        $name = Str::swap(
            [
                config('mediaManager.ulid_separator') => '',
                config('mediaManager.tmp_separator') => '',
                ' ' => '_',
                '.' =>'',
                '-' => ''
            ],
            $name
        );
        // $name = time().rand(0,99).config('mediaManager.tmp_separator').substr($name, 0, 20).'.'.$file->extension();
        $ulid = Str::ulid();
        $name = substr($name, 0, 20).'.'.$file->extension();

        $tempFolder = config('mediaManager.temp_folder').DIRECTORY_SEPARATOR.$ulid;
        $tempThumbnailFolder = config('mediaManager.temp_folder').DIRECTORY_SEPARATOR.$ulid.DIRECTORY_SEPARATOR.config('mediaManager.temp_thumbnail_folder');
        $tempDisk = config('mediaManager.temp_disk');
        $path = trim($file->storeAs($tempFolder, $name, $tempDisk));


        // $storagePath = $destFolder.DIRECTORY_SEPARATOR.$ulid.DIRECTORY_SEPARATOR.'original'.DIRECTORY_SEPARATOR.$fname;
        $thumbnailStoragePath = Storage::disk($tempDisk)->path($tempThumbnailFolder.DIRECTORY_SEPARATOR.$name);

        $thumbnailSize = config('mediaManager.upload_thumbnail_size');
        self::createSizeVariantFile(Storage::disk($tempDisk)->path($path), $thumbnailStoragePath, $thumbnailSize['width'], $thumbnailSize['height']);

        return [
            'unique_name' => $ulid.config('mediaManager.tmp_separator').$name,
            'url' => Storage::disk($tempDisk)->url($path),
            'thumbnail_url' => Storage::disk($tempDisk)->url($tempThumbnailFolder.DIRECTORY_SEPARATOR.$name)
        ];
    }

    public static function createSizeVariantFile($filePath, $variantFilePath, $maxWidth, $maxHeight)
    {
        /**
         * @var \Imagick
         */
        $image = new \Imagick($filePath);

        // Resize the image to 200x200 pixels
        $image->resizeImage($maxWidth, $maxHeight, \Imagick::FILTER_LANCZOS, 1, true);

        // Write the image to disk
        // $image->writeImage($variantFilePath);
        $tArr = explode(DIRECTORY_SEPARATOR, $variantFilePath);
        array_pop($tArr);
        $dir = implode(DIRECTORY_SEPARATOR, $tArr);
        if(!is_dir($dir)) {
            mkdir( $dir , 0755, true);
        }
        $result = false;
        if($f=fopen($variantFilePath, "w")){
            $result = $image->writeImageFile($f);
        }
        return $result;
    }
}
?>
