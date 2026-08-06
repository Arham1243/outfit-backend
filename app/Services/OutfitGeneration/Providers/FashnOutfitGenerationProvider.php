<?php

namespace App\Services\OutfitGeneration\Providers;

use App\Contracts\OutfitGeneration\OutfitGenerationProvider;
use App\Services\Fashn\FashnClient;
use App\Services\Fashn\FashnException;
use App\Services\OutfitGeneration\OutfitGenerationException;

class FashnOutfitGenerationProvider implements OutfitGenerationProvider
{
    public function __construct(
        private readonly FashnClient $fashnClient,
    ) {}

    public function name(): string
    {
        return 'fashn';
    }

    public function createBaseModel(?int $heightCm, ?string $gender): string
    {
        return $this->wrap(fn () => $this->fashnClient->modelCreate($heightCm, $gender));
    }

    public function applyGarment(string $modelImage, string $productImage): string
    {
        return $this->wrap(fn () => $this->fashnClient->tryOnMax($modelImage, $productImage));
    }

    /**
     * @param  callable(): string  $callback
     */
    private function wrap(callable $callback): string
    {
        try {
            return $callback();
        } catch (FashnException $exception) {
            throw new OutfitGenerationException(
                $exception->getMessage(),
                $this->name(),
                array_filter([
                    'error_name' => $exception->getErrorName(),
                    'model_name' => $exception->getModelName(),
                    'http_status' => $exception->getHttpStatus(),
                ]),
                $exception
            );
        }
    }
}
