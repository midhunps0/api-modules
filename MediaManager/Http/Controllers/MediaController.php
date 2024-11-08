<?php

namespace Modules\Ynotz\MediaManager\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Modules\Ynotz\MediaManager\Helpers\MediaHelper;
use Modules\Ynotz\MediaManager\Models\GalleryItem;
use Modules\Ynotz\SmartPages\Http\Controllers\SmartController;

class MediaController
{
    public function fileUpload(Request $request)
    {
        $file = $request->file('file');

        return response()->json(
            [
                'success' => true,
                'data' => MediaHelper::tempFileUpload($file)
            ]
        );
    }

    public function fileDelete($request)
    {
        $tempFolder = config('mediaManager.temp_folder');
        $tempDisk = config('mediaManager.temp_disk');
        info($request->input('file'));
        Storage::disk($tempDisk)->delete($tempFolder.'/'.trim($request->input('file')));
        return response()->json([
            'success' => true
        ]);
    }

    public function gallery()
    {
        $galleryService = config('mediaManager.gallery_service');

        return response()->json([
            'items' => (new $galleryService)->getMediaItems()
        ]);
    }

    public function displayImage($variant, $ulid, $imagename)
    {
        $path = storage_path('images/' . $ulid.'/'.$variant.'/'.$imagename);

        if (!File::exists($path)) {
            MediaHelper::makeVariant($variant, $ulid);
        }

        $file = File::get($path);
        $type = File::mimeType($path);
        $response = Response::make($file, 200);
        $response->header("Content-Type", $type);
        return $response;
    }
}
