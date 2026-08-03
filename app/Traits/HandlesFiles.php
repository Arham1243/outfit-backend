<?php

namespace App\Traits;

use Illuminate\Http\Request;

trait HandlesFiles
{
    use HandlesBase64Uploads;

    protected function handleFile(Request $request, $model, string $field, string $folder, bool $useOriginalName = false, ?string $originalName = null): void
    {
        if (! $request->has($field)) {
            return;
        }

        $newFile = $request->{$field};
        $oldFile = $model->getOriginal($field);

        if (is_null($newFile)) {
            if ($oldFile) {
                $this->deleteFile($oldFile);
            }
            $model->{$field} = null;
            $request->merge([$field => null]);
        } elseif (is_string($newFile) && str_starts_with($newFile, 'data:')) {
            if ($oldFile) {
                $this->deleteFile($oldFile);
            }
            $path = $this->saveBase64File($newFile, $folder, $useOriginalName, $originalName);
            $model->{$field} = $path;
            $request->merge([$field => $path]);
        } elseif (is_string($newFile) && filter_var($newFile, FILTER_VALIDATE_URL)) {
            $parsedPath = parse_url($newFile, PHP_URL_PATH);
            $parsedPath = ltrim((string) $parsedPath, '/');
            if (str_ends_with($parsedPath, '/public')) {
                $parsedPath = substr($parsedPath, 0, -7);
            }
            $model->{$field} = $parsedPath;
            $request->merge([$field => $parsedPath]);
        }
    }
}
