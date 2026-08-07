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
        $references = [
            $this->openAiClient->resolveStudioReferenceAbsolutePath(),
            $modelImage,
            $productImage,
        ];

        $prompt = 'Image 1 is the studio pose, lighting, framing, and white background reference only. '
            .'Image 2 is the current full-body model photo. Preserve the exact face, body proportions, pose, and identity from Image 2. '
            .'Image 3 is the garment product photo. '
            .'Apply the garment shown in Image 3 onto the person in Image 2. '
            .'Match Image 1\'s studio lighting and background. Do not change the person\'s face. '
            .'Full body, standing, relaxed pose.';

        return $this->wrap(fn () => $this->openAiClient->editImage($references, $prompt));
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
