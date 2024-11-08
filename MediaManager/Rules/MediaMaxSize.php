<?php

namespace Modules\Ynotz\MediaManager\Rules;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Modules\Ynotz\MediaManager\Models\MediaItem;
use Illuminate\Contracts\Validation\InvokableRule;

class MediaMaxSize implements InvokableRule
{
    private $maxSize;
    private $unit;

    public function __construct($maxSize, $unit)
    {
        $this->maxSize = $maxSize;
        $this->unit = $unit;
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
            $fileName = $arr[1];
            $tempDisk = config('mediaManager.temp_disk');
            $tempFolder = config('mediaManager.temp_folder');
            $filepath = $tempFolder.DIRECTORY_SEPARATOR.$ulid.DIRECTORY_SEPARATOR.$fileName;
            $fileSize = Storage::disk($tempDisk)->size($filepath);
        } else {
            $arr = explode(config('mediaManager.ulid_separator'), $value);
            $ulid = $arr[0];

            $mediaItem = MediaItem::where('ulid', $ulid)->get()->first();
            $fileSize = $mediaItem->size;
        }

        if ($fileSize > $this->getSizeInBytes()) {
            $fail($value.': '.__('- size exceeds ').$this->maxSize.' '.$this->unit.'.');
        }
    }

    private function getSizeInBytes()
    {
        $size = $this->maxSize;
        switch (Str::lower($this->unit)) {
            case 'gb':
                $size = $this->maxSize * 1024 * 1024 * 1024;
                break;
            case 'mb':
                $size = $this->maxSize * 1024 * 1024;
                break;
            case 'kb':
                $size = $this->maxSize * 1024;
                break;
        }
        return $size;
    }
}
