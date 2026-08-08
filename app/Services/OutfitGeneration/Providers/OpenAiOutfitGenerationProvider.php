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

    public function createBaseModel(?int $heightCm, ?string $gender, ?string $faceImage = null, ?string $faceMode = null): string
    {
        $mode = $this->normalizeFaceMode($faceMode);
        $references = [$this->openAiClient->resolveStudioReferenceAbsolutePath()];

        if ($this->faceModeUsesReference($mode) && $faceImage !== null && $faceImage !== '') {
            $references[] = $faceImage;
        }

        $prompt = $this->buildBaseModelPrompt($heightCm, $gender, $mode);

        return $this->wrap(fn () => $this->openAiClient->editImage($references, $prompt));
    }

    public function applyGarment(string $modelImage, string $productImage): string
    {
        $references = [$modelImage, $productImage];

        $prompt = 'Image 1 is the current full-body model photo — preserve this person\'s exact face, body, pose, and identity. '
            .'Image 2 is a garment product photo. Apply the garment from Image 2 onto the person in Image 1. '
            .'Plain white studio background, professional fashion photography, photorealistic, no facial artifacts.';

        return $this->wrap(fn () => $this->openAiClient->editImage($references, $prompt));
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

    private function buildBaseModelPrompt(?int $heightCm, ?string $gender, string $faceMode): string
    {
        $heightText = ($heightCm !== null && $heightCm > 0)
            ? sprintf('approximately %dcm build', $heightCm)
            : 'balanced proportions';

        $genderText = match ($gender) {
            'female' => 'adult woman',
            'male' => 'adult man',
            default => 'fashion model',
        };

        $studioRef = 'Image 1 is the studio pose, lighting, framing, and white background reference only. '
            .'Do not copy the person from Image 1.';

        return match ($faceMode) {
            'user_face' => sprintf(
                '%s Image 2 is the face identity reference. Generate a professional full-body studio fashion photograph with Image 1\'s pose and background. '
                .'The generated person must be a %s with %s. '
                .'The face must exactly match Image 2, including glasses, facial hair, skin tone, and all facial features. '
                .'Plain white studio background, full body, standing, relaxed pose.',
                $studioRef,
                $genderText,
                $heightText
            ),
            'user_body_ai_face' => sprintf(
                '%s Image 2 is a body and build proportion reference only. Generate a professional full-body studio fashion photograph with Image 1\'s pose and background. '
                .'Use Image 2 only to guide %s for a %s. Generate a different realistic face. '
                .'Plain white studio background, full body, standing, relaxed pose.',
                $studioRef,
                $heightText,
                $genderText
            ),
            default => sprintf(
                '%s Generate a professional full-body studio fashion photograph with a realistic generic %s and %s. '
                .'Use a new anonymous human face — do not copy any user face reference. '
                .'Plain white studio background, full body, standing, relaxed pose.',
                $studioRef,
                $genderText,
                $heightText
            ),
        };
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
