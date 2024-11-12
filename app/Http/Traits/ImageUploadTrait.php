<?php

namespace App\Http\Traits;

use Storage;

trait ImageUploadTrait
{
    public function saveImage($upload, $path)
    {
        $mimeTypeArray = explode('/', $upload->getMimeType());
        $fileType = ($mimeTypeArray[0] == 'image' ? 'image' : $mimeTypeArray[1]);

        if ($fileType == 'image') {
            Storage::put($path . '/' . $upload->getClientOriginalName(), file_get_contents($upload), env('FILESYSTEM_DISK'));
            return Storage::url($path . '/' . $upload->getClientOriginalName());
        }
    }

    public function savePdf($pdf, $path)
    {
        Storage::put($path, $pdf->output(), env('FILESYSTEM_DISK'));
        return Storage::url($path);
    }
}
