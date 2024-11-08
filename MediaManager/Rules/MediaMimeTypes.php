<?php

namespace Modules\Ynotz\MediaManager\Rules;

// use File;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Modules\Ynotz\MediaManager\Models\MediaItem;
use Illuminate\Contracts\Validation\InvokableRule;
use Illuminate\Support\Facades\File;

class MediaMimeTypes implements InvokableRule
{
    private $types;

    public function __construct($types)
    {
        $this->types = $types;
    }

    /**
     * Run the validation rule.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     * @return void
     */
    public function __invoke($attribute, $value, $fail)
    {
        if (strpos($value, config('mediaManager.ulid_separator')) === false) {
            $arr = explode(config('mediaManager.tmp_separator'), $value);
            $ulid = $arr[0];

            $tempDisk = config('mediaManager.temp_disk');
            $tempFolder = config('mediaManager.temp_folder');
            $filepath = $tempFolder.DIRECTORY_SEPARATOR.$value;

            $fpath = Storage::disk($tempDisk)->path($filepath);
        } else {
            $arr = explode(config('mediaManager.ulid_separator'), $value);
            $ulid = $arr[0];

            $mediaItem = MediaItem::where('ulid', $ulid)->get()->first();
            $fpath = $mediaItem->filepath;
        }

        if (!in_array(File::extension($fpath), $this->types)) {
            $fail($value.': '.__('- invalid type.'));
        }
    }
}
