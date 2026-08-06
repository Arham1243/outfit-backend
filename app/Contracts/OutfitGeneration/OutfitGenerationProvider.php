<?php

namespace App\Contracts\OutfitGeneration;

interface OutfitGenerationProvider
{
    public function name(): string;

    /**
     * @throws \App\Services\OutfitGeneration\OutfitGenerationException
     */
    public function createBaseModel(?int $heightCm, ?string $gender): string;

    /**
     * Apply a wardrobe garment onto the model image.
     *
     * @return string URL or local storage path usable as the next model input
     *
     * @throws \App\Services\OutfitGeneration\OutfitGenerationException
     */
    public function applyGarment(string $modelImage, string $productImage): string;
}
