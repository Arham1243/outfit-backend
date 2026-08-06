<?php

namespace App\Services\OutfitGeneration;

use App\Contracts\OutfitGeneration\OutfitGenerationProvider;
use Illuminate\Contracts\Container\Container;

class OutfitGenerationManager
{
    public function __construct(
        private readonly Container $container,
    ) {}

    public function driver(?string $name = null): OutfitGenerationProvider
    {
        $name = $name ?? (string) config('outfit_generation.default', 'fashn');
        $providers = config('outfit_generation.providers', []);

        if (! is_array($providers) || ! isset($providers[$name])) {
            throw new OutfitGenerationException(
                sprintf('Outfit generation provider [%s] is not configured.', $name)
            );
        }

        $config = $providers[$name];

        if (! is_array($config)) {
            throw new OutfitGenerationException(
                sprintf('Outfit generation provider [%s] has invalid configuration.', $name)
            );
        }

        if (! ($config['enabled'] ?? false)) {
            throw new OutfitGenerationException(
                sprintf('Outfit generation provider [%s] is disabled.', $name)
            );
        }

        $driverClass = $config['driver'] ?? null;

        if (! is_string($driverClass) || $driverClass === '') {
            throw new OutfitGenerationException(
                sprintf('Outfit generation provider [%s] is missing a driver class.', $name)
            );
        }

        $provider = $this->container->make($driverClass);

        if (! $provider instanceof OutfitGenerationProvider) {
            throw new OutfitGenerationException(
                sprintf(
                    'Outfit generation provider [%s] must implement %s.',
                    $name,
                    OutfitGenerationProvider::class
                )
            );
        }

        return $provider;
    }

    public function defaultDriverName(): string
    {
        return (string) config('outfit_generation.default', 'fashn');
    }

    public function baseModelCacheVersion(?string $providerName = null): string
    {
        $providerName = $providerName ?? $this->defaultDriverName();
        $providers = config('outfit_generation.providers', []);
        $config = is_array($providers[$providerName] ?? null) ? $providers[$providerName] : [];

        return (string) ($config['base_model_cache_version'] ?? 'v1');
    }
}
