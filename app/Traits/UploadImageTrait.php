<?php

namespace App\Traits;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

trait UploadImageTrait
{
    public function simpleUploadImg($file, string $directory, $previousImage = null)
    {

        $this->deletePreviousImage($previousImage);

        if (preg_match('/^data:image\/(\w+);base64,/', $file, $matches)) {
            $extension = $matches[1];
            $file = substr($file, strpos($file, ',') + 1);
            $file = base64_decode($file);

            if ($file === false) {
                return null;
            }

            $filename = Str::uuid().'.'.$extension;
            $directory = trim($directory, '/');
            $filePath = $directory.'/'.$filename;

            Storage::disk('public')->put($filePath, $file);

            return $filePath;
        }

        if ($file instanceof UploadedFile) {
            $filename = Str::uuid().'.'.$file->getClientOriginalExtension();
            $directory = trim($directory, '/');

            return $file->storeAs($directory, $filename, 'public');
        }

        return null;
    }

    protected function deletePreviousImage(?string $filePath): void
    {
        if ($filePath && Storage::disk('public')->exists($filePath)) {
            Storage::disk('public')->delete($filePath);
        }
    }
}
