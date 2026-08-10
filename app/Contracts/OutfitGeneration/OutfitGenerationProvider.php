<?php

namespace App\Contracts\OutfitGeneration;

interface OutfitGenerationProvider
{
    public function name(): string;

    /**
     * Single-pass outfit generation: face/body reference + all garments in one API call.
     *
     * @param  list<string>  $garmentImages
     * @return string Relative storage path of the generated outfit image
     *
     * @throws \App\Services\OutfitGeneration\OutfitGenerationException
     */
    public function generateFullOutfit(
        ?int $heightCm,
        ?string $gender,
        ?string $faceImage,
        ?string $faceMode,
        array $garmentImages,
        string $outputRelativePath,
    ): string;
}
