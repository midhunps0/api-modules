<?php
namespace Modules\Ynotz\MediaManager\Traits;

use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Modules\Ynotz\MediaManager\Models\MediaItem;
use Modules\Ynotz\AccessControl\Models\Permission;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Modules\Ynotz\MediaManager\DataObjects\ImageVariant;
use Modules\Ynotz\MediaManager\Helpers\MediaHelper;
use stdClass;

trait OwnsMedia
{
    public function mediaPermissions($property = null, $variant = null)
    {
        $query = $this->morphToMany(Permission::class, 'mediaowner', 'media_permissions', 'mediaowner_id', 'permission_id');
        if (isset($property)) {
            $query->where('property', $property);
        }
        if (isset($variant)) {
            $query->where('variant', $variant);
        }
        return $query->get();
    }

    // public function variants(): array
    // {
    //     return [

    //     ];
    // }

    public function media()
    {
        return $this->morphToMany(MediaItem::class, 'mediaowner', 'media_instances', 'mediaowner_id', 'mediaitem_id');
    }

    public function storageLocation(
        string $disk,
        string $folder
    ):array {
        return [
            'disk' => $disk,
            'folder' => $folder
        ];
    }

    public function attachMedia(MediaItem $mediaItem, string $property, array $customProps = []): void
    {
        $ulid = Str::ulid();
        $attachdata = [
            'id' => $ulid,
            'property' => $property,
            'created_by' => auth()->user()->id
        ];
        if (count($customProps) > 0) {
            $attachdata['custom_properties'] = json_encode($customProps);
        }

        $this->media()->attach(
            $mediaItem,
            $attachdata
        );
    }

    public function addOneMediaFromEAInput(string $property, string $input): void
    {
        // if (strpos($input, config('mediaManager.ulid_separator')) === false) {
        if (strpos($input, config('mediaManager.tmp_separator')) != false) {
            $arr = explode(config('mediaManager.tmp_separator'), $input);
            $ulid = $arr[0];
            $fname = $arr[1];
            $tempDisk = config('mediaManager.temp_disk');
            $tempFolder = config('mediaManager.temp_folder');

            $filepath = Storage::disk($tempDisk)->path($tempFolder.DIRECTORY_SEPARATOR.$ulid.DIRECTORY_SEPARATOR.$fname);
            $mimeType = mime_content_type($filepath);
            $fileType = explode(DIRECTORY_SEPARATOR, $mimeType)[0];
            $size = Storage::disk($tempDisk)->size($tempFolder.DIRECTORY_SEPARATOR.$ulid.DIRECTORY_SEPARATOR.$fname);

            $destFolder = '';
            $destDisk = '';

            if (isset($this->getMediaStorage()[$property])) {
                $destFolder = $this->getMediaStorage()[$property]['folder'] ?? '';
                $destDisk = $this->getMediaStorage()[$property]['disk'] ?? '';
            }

            if ($destDisk == '' || $destFolder == '') {
                switch($fileType) {
                    case 'image':
                        $destFolder = config('mediaManager.images_folder');
                        $destDisk = config('mediaManager.images_disk');
                        break;
                    case 'video':
                        $destFolder = config('mediaManager.videos_folder');
                        $destDisk = config('mediaManager.videos_disk');
                        break;
                    default:
                        $destFolder = config('mediaManager.files_folder');
                        $destDisk = config('mediaManager.files_disk');
                        break;
                }
            }

            $sourcePath = $tempFolder.DIRECTORY_SEPARATOR.$ulid.DIRECTORY_SEPARATOR.$fname;
            $storagePath = $destFolder.DIRECTORY_SEPARATOR.$ulid.DIRECTORY_SEPARATOR.'original'.DIRECTORY_SEPARATOR.$fname;

            if (Storage::disk($tempDisk)->get($sourcePath) == null) {
                throw new FileNotFoundException('Something went wrong. Couldn\'t save the '.$property.' file.');
            }

            Storage::disk($destDisk)->put(
                $storagePath,
                Storage::disk($tempDisk)->get($tempFolder.DIRECTORY_SEPARATOR.$ulid.DIRECTORY_SEPARATOR.$fname)
            );

            Storage::disk($tempDisk)->delete($tempFolder.DIRECTORY_SEPARATOR.$ulid.DIRECTORY_SEPARATOR.$fname);
            Storage::disk($tempDisk)->delete($tempFolder.DIRECTORY_SEPARATOR.$ulid.DIRECTORY_SEPARATOR.config('mediaManager.temp_thumbnail_folder').DIRECTORY_SEPARATOR.$fname);

            rmdir(Storage::disk($tempDisk)->path($tempFolder.DIRECTORY_SEPARATOR.$ulid.DIRECTORY_SEPARATOR.config('mediaManager.temp_thumbnail_folder')));
            rmdir(Storage::disk($tempDisk)->path($tempFolder.DIRECTORY_SEPARATOR.$ulid));

            $x = [
                'ulid' => $ulid,
                'filename' => $fname,
                'filepath' => $storagePath,
                'disk' => $destDisk,
                'type' => $fileType,
                'size' => $size, //size of the file in bytes
                'mime_type' => $mimeType,
            ];

            $mediaItem = MediaItem::create($x);

            $this->attachMedia($mediaItem, $property);

        } else {
            // $ulid = str_replace(config('mediaManager.ulid_separator'), '', $input);
            $arr = explode(config('mediaManager.ulid_separator'), $input);
            $ulid = $arr[0];
            // $fname = $arr[1];
            $mediaItem = MediaItem::where('ulid', $ulid)->get()->first();

            if ($mediaItem != null) {
                $this->attachMedia($mediaItem, $property);
            }
        }

        // Do conversions if defined (check if conversions array exists)
        if (isset($this->getImageSizeVariants()[$property])) {
            if (!isset($this->getImageSizeVariants()[$property]['process_on_upload']) || $this->getImageSizeVariants()[$property]['process_on_upload']) {
                $absoluteStoragePath = Storage::disk($destDisk)->path($storagePath);

                //if queue available, queue job, else convert now (to be implemented later)
                // $originalFile = Storage::disk($destDisk)->get($storagePath);
                foreach ($this->getImageSizeVariants()[$property]['variants'] as $v) {
                    $theClass = $this->class;
                    if (!($v instanceof ImageVariant)) {
                        throw new InvalidFormatException("Argument should be an instance of ImageVariant for variants in getImageSizeVariants inside $theClass");
                    }
                    $variantStoragePath = str_replace('original', $v->name, $absoluteStoragePath);
                    $fileSaved = MediaHelper::createSizeVariantFile($absoluteStoragePath, $variantStoragePath, $v->maxWidth, $v->maxHeight);
                    if($fileSaved) {
                        info('Vriant saved');
                    } else {
                        info('Failed to save variant');
                    }
                    // Storage::disk($destDisk)->put($variantStoragePath, $variantFile);
                }
            }
        }
    }

    public function addMediaFromEAInput(string $property, array|string $vals): void
    {
        if (is_array($vals)) {
            foreach ($vals as $input) {
                $this->addOneMediaFromEAInput($property, $input);
            }
        } else {
            $this->addOneMediaFromEAInput($property, $vals);
        }
    }

    public function getAllMedia(string $property): Collection
    {
        return $this->morphToMany(MediaItem::class, 'mediaowner', 'media_instances', 'mediaowner_id', 'mediaitem_id')->where('property', $property)->get();
    }
    // public function morphToMany($related, $name, $table = null, $foreignKey = null, $otherKey = null, $inverse = false){}

    public function getSingleMedia(string $property): MediaItem|null
    {
        return $this->morphToMany(MediaItem::class, 'mediaowner', 'media_instances', 'mediaowner_id', 'mediaitem_id')->where('property', $property)->get()->first();
    }

    public function getSingleMediaFilePath(string $property): string|null
    {
        $m = $this->morphToMany(MediaItem::class, 'mediaowner', 'media_instances', 'mediaowner_id', 'mediaitem_id')
            ->where('property', $property)
            ->get()->first();
        return $m ? $m->filepath : null;
    }

    public function getSingleMediaFileName(string $property): string|null
    {
        $m = $this->morphToMany(MediaItem::class, 'mediaowner', 'media_instances', 'mediaowner_id', 'mediaitem_id')
            ->where('property', $property)
            ->get()->first();
        return $m ? $m->filename : null;
    }

    public function getSingleMediaForEAForm(string $property): array
    {
        return [
            'path' => $this->getSingleMediaUrl($property),
            'ulid' => $this->getSingleMediaUlid($property)
        ];
    }
    public function getSingleMediaUrl(string $property): string|null
    {
        $m = $this->morphToMany(MediaItem::class, 'mediaowner', 'media_instances', 'mediaowner_id', 'mediaitem_id')
            ->where('property', $property)
            ->get()->first();
        return $m ? $m->url : null;
    }

    public function getSingleMediaUlid(string $property): string|null
    {
        $m = $this->morphToMany(MediaItem::class, 'mediaowner', 'media_instances', 'mediaowner_id', 'mediaitem_id')
            ->where('property', $property)
            ->get()->first();
        return $m ? $m->ulid : null;
    }

    // Example:
    // public function getImageSizeVariants(): array
    // {
        // return [
        //     'image' => [
        //         'process_on_upload' => true,
        //         'variants' => [
        //             new ImageVariant('thumbnail', 150, 150),
        //             new ImageVariant('small', 300, 300),
        //             new ImageVariant('medium', 600, 600),
        //             new ImageVariant('large', 900, 900),
        //         ]
        //     ]
        // ];
    // }

    // Exaple:
    // public function getMediaStorage(): array
    // {
    //     return [
    //         'image'=> [
    //             'disk' => 'public',
    //             'folder' => 'public/images/photo'
    //         ],
    //     ];
    // }

    public function deleteAllMedia(string $property): void
    {
        $this->media()->wherePivot('property', 'like', $property)
            ->detach();
    }

    public function syncMedia(string $property, $items)
    {
        $existing = [];
        $new = [];
        if (is_array($items)) {
            foreach ($items as $item) {
                if (strpos($item, config('mediaManager.ulid_separator')) === false) {
                    $new[] = $item;
                } else {
                    $arr = explode(config('mediaManager.ulid_separator'), $item);
                    $ulid = $arr[0];
                    // $fname = $arr[1];
                    // $ulid = str_replace(config('mediaManager.ulid_separator'), '', $item);
                    $mi = MediaItem::where('ulid', $ulid)->get()->first();
                    if (isset($mi)) {
                        $existing[] = $mi->id;
                    }
                }
            }
        } else {
            if (strpos($items, config('mediaManager.ulid_separator')) === false) {
                $new[] = $items;
            } else {
                $arr = explode(config('mediaManager.tmp_separator'), $items);
                $ulid = $arr[0];
                // $fname = $arr[1];
                // $ulid = str_replace(config('mediaManager.ulid_separator'), '', $items);
                $mi = MediaItem::where('ulid', $ulid)->get()->first();
                if (isset($mi)) {
                    $existing[] = $mi->id;
                }
            }
        }

        $this->media()->wherePivot('property', $property)
            ->sync($existing);
        $this->addMediaFromEAInput($property, $new);
    }

    public function setSizeVariantFile($filePath, $variantFilePath, $maxWidth, $maxHeight)
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
        if($f=fopen($variantFilePath, "w")){
            $image->writeImageFile($f);
        }
    }

    public function mediaData():Attribute
    {
        $imageUrls = [];
        foreach ($this->getImageSizeVariants() as $property => $data) {
            $imageUrls[$property] = [];
            foreach($this->getAllMedia($property) as $mediaItem) {
                $theUrl = $mediaItem->url;
                $arr = [];
                $arr['original'] = $theUrl;
                foreach ($data['variants'] as $v) {
                    $arr[$v->name] = str_replace('original', $v->name, $theUrl);
                }
                $imageUrls[$property][] = [
                    'unique_name' => $mediaItem->ulid.
                    config('mediaManager.ulid_separator').
                    $mediaItem->filename,
                    'variants' => $arr
                ];
            }
        }
        return Attribute::make(
            get: function ($v) use($imageUrls) {
                return $imageUrls;
            }
        );
    }
}
?>
