<?php

namespace App\Traits;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

trait HandlesBase64Uploads
{
    protected array $mimeMap = [
        'application/pdf' => 'pdf',
        'application/msword' => 'doc',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
        'application/vnd.ms-excel' => 'xls',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
        'image/jpeg' => 'jpg',
        'image/jpg'  => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp',
    ];

    public function saveBase64File(?string $base64, string $directory = 'uploads/other', bool $useOriginalName = false, ?string $originalName = null): ?string
    {
        if (! $base64) {
            return null;
        }

        if (preg_match('/^data:([^;]+);base64,(.+)$/s', $base64, $m)) {
            $mimeType = $m[1];
            $data = base64_decode($m[2], true);

            if ($data === false) {
                throw ValidationException::withMessages(['file' => 'Invalid base64 file provided.']);
            }

            $extension = $this->mimeMap[$mimeType] ?? Str::after($mimeType, '/');
            $extension = preg_replace('/[^a-z0-9]/i', '', $extension) ?: 'bin';
            $directory = trim($directory, '/');

            if ($useOriginalName && $originalName) {
                $name = pathinfo($originalName, PATHINFO_FILENAME);
                $name = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $name);
                $fileName = $name.'.'.$extension;
                $counter = 1;

                while (Storage::disk('public')->exists($directory.'/'.$fileName)) {
                    $fileName = $name.'_'.$counter.'.'.$extension;
                    $counter++;
                }
            } else {
                $fileName = Str::uuid().'.'.$extension;
            }

            $path = $directory.'/'.$fileName;

            Storage::disk('public')->put($path, $data);

            return $path;
        }

        return null;
    }

    public function deleteFile(?string $path): void
    {
        if ($path) {
            Storage::disk('public')->delete($path);
        }
    }
}
