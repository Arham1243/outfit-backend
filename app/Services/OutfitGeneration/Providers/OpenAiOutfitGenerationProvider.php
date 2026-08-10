<?php

namespace App\Services\OutfitGeneration\Providers;

use App\Contracts\OutfitGeneration\OutfitGenerationProvider;
use App\Services\OpenAi\OpenAiClient;
use App\Services\OpenAi\OpenAiException;
use App\Services\OutfitGeneration\OutfitGenerationException;

class OpenAiOutfitGenerationProvider implements OutfitGenerationProvider
{
    public function __construct(
        private readonly OpenAiClient $openAiClient,
    ) {}

    public function name(): string
    {
        return 'openai';
    }

    /**
     * Single-pass outfit generation: face/body reference + all garments in one API call.
     * Avoids sequential edits that degrade face quality and litter tmp files.
     *
     * @param  list<string>  $garmentImages
     *
     * @throws OutfitGenerationException
     */
    public function generateFullOutfit(
        ?int $heightCm,
        ?string $gender,
        ?string $faceImage,
        ?string $faceMode,
        array $garmentImages,
        string $outputRelativePath,
    ): string {
        if ($garmentImages === []) {
            throw new OutfitGenerationException('At least one garment image is required.');
        }

        $mode = $this->normalizeFaceMode($faceMode);
        $references = $this->buildOutfitReferences($mode, $faceImage, $garmentImages);
        $prompt = $this->buildFullOutfitPrompt($heightCm, $gender, $mode, count($references), count($garmentImages));

        return $this->wrap(fn () => $this->openAiClient->editImage(
            $references,
            $prompt,
            [],
            $outputRelativePath
        ));
    }

    /**
     * @param  list<string>  $garmentImages
     * @return list<string>
     */
    private function buildOutfitReferences(string $faceMode, ?string $faceImage, array $garmentImages): array
    {
        $references = [];

        if ($faceMode === 'ai_model') {
            $references[] = $this->openAiClient->resolveStudioReferenceAbsolutePath();
        } elseif ($this->faceModeUsesReference($faceMode) && $faceImage !== null && $faceImage !== '') {
            $references[] = $faceImage;
        }

        foreach ($garmentImages as $garmentImage) {
            $references[] = $garmentImage;
        }

        return $references;
    }

    private function buildFullOutfitPrompt(
        ?int $heightCm,
        ?string $gender,
        string $faceMode,
        int $referenceCount,
        int $garmentCount,
    ): string {
        $heightText = ($heightCm !== null && $heightCm > 0)
            ? sprintf('approximately %d cm tall', $heightCm)
            : 'natural height';

        $genderText = match ($gender) {
            'female' => 'woman',
            'male' => 'man',
            default => 'person',
        };

        $garmentStart = $faceMode === 'ai_model' ? 2 : 1;
        $garmentEnd = $referenceCount;
        $garmentRange = $garmentCount === 1
            ? "Image {$garmentStart}"
            : "Images {$garmentStart} through {$garmentEnd}";

        $base = "Create a professional full-body studio fashion photograph on a plain white background. "
            ."The person should be {$heightText}, {$genderText}, standing in a relaxed front-facing pose. "
            ."{$garmentRange} ".'show the clothing items to wear together. '
            .'Photorealistic, clean, high-quality fashion photo with even studio lighting. No text, logos, or watermarks. ';

        return match ($faceMode) {
            'user_face' => $base
                .'Image 1 is the face reference — use this person\'s exact face, including glasses, facial hair, skin tone, and all facial features. '
                .'Fit the clothing naturally to their body proportions. Do not distort or add artifacts to the face.',
            'user_body_ai_face' => $base
                .'Image 1 is a body proportion reference only — match the build and height, but generate a different realistic face.',
            default => $base
                .'Image 1 is a studio pose and framing reference only — do not copy any person from it. '
                .'Generate a realistic generic fashion model with a new anonymous human face wearing all garments.',
        };
    }

    private function normalizeFaceMode(?string $faceMode): string
    {
        return in_array($faceMode, ['user_face', 'ai_model', 'user_body_ai_face'], true)
            ? $faceMode
            : 'ai_model';
    }

    private function faceModeUsesReference(string $faceMode): bool
    {
        return in_array($faceMode, ['user_face', 'user_body_ai_face'], true);
    }

    /**
     * @param  callable(): string  $callback
     */
    private function wrap(callable $callback): string
    {
        try {
            return $callback();
        } catch (OpenAiException $exception) {
            throw new OutfitGenerationException(
                $exception->getMessage(),
                $this->name(),
                array_filter([
                    'error_type' => $exception->getErrorType(),
                    'endpoint' => $exception->getEndpoint(),
                    'http_status' => $exception->getHttpStatus(),
                ]),
                $exception
            );
        }
    }
}
