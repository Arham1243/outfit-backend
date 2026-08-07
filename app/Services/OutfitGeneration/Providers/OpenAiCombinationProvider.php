<?php

namespace App\Services\OutfitGeneration\Providers;

use App\Contracts\OutfitGeneration\OutfitCombinationProvider;
use App\Models\Core\Wardrobe;
use App\Services\OpenAi\OpenAiClient;
use App\Services\OpenAi\OpenAiException;
use App\Services\OutfitGeneration\OutfitGenerationException;
use App\Support\OutfitRequirements;
use Illuminate\Support\Collection;

class OpenAiCombinationProvider implements OutfitCombinationProvider
{
    public function __construct(
        private readonly OpenAiClient $openAiClient,
    ) {}

    /**
     * @param  Collection<int, Wardrobe>  $wardrobeItems
     * @param  array<string, mixed>  $preferences
     * @return list<array{wardrobe_ids: list<int>, items: list<Wardrobe>, confidence: float}>
     */
    public function rankCombinations(Collection $wardrobeItems, array $preferences = []): array
    {
        if ($wardrobeItems->isEmpty()) {
            return [];
        }

        $payloadItems = $wardrobeItems
            ->map(fn (Wardrobe $item) => [
                'id' => $item->id,
                'type' => $item->type,
                'metadata' => $item->metadata ?? [],
            ])
            ->values()
            ->all();

        try {
            $ranked = $this->openAiClient->rankCombinations($payloadItems, $preferences);
        } catch (OpenAiException $exception) {
            throw new OutfitGenerationException(
                $exception->getMessage(),
                'openai',
                array_filter([
                    'error_type' => $exception->getErrorType(),
                    'endpoint' => $exception->getEndpoint(),
                    'http_status' => $exception->getHttpStatus(),
                ]),
                $exception
            );
        }

        $itemsById = $wardrobeItems->keyBy('id');
        $combinations = [];

        foreach ($ranked as $entry) {
            $resolved = $this->resolveCombinationItems($entry, $itemsById);

            if ($resolved === null) {
                continue;
            }

            [$items, $wardrobeIds] = $resolved;

            $combinations[] = [
                'wardrobe_ids' => $wardrobeIds,
                'items' => $items,
                'confidence' => round((float) ($entry['score'] ?? 0), 4),
            ];
        }

        return $combinations;
    }

    /**
     * @param  array<string, mixed>  $entry
     * @param  Collection<int|string, Wardrobe>  $itemsById
     * @return array{0: list<Wardrobe>, 1: list<int>}|null
     */
    private function resolveCombinationItems(array $entry, Collection $itemsById): ?array
    {
        $slotMap = [
            'dress_id' => OutfitRequirements::ONE_PIECE,
            'top_id' => OutfitRequirements::TOPS,
            'bottom_id' => OutfitRequirements::BOTTOMS,
            'shoes_id' => OutfitRequirements::FOOTWEAR,
        ];

        $items = [];

        foreach ($slotMap as $key => $allowedTypes) {
            $id = $entry[$key] ?? null;

            if ($id === null) {
                continue;
            }

            $item = $itemsById->get((int) $id);

            if (! $item instanceof Wardrobe || ! in_array($item->type, $allowedTypes, true)) {
                return null;
            }

            $items[] = $item;
        }

        if ($items === []) {
            return null;
        }

        $hasDress = collect($items)->contains(fn (Wardrobe $item) => in_array($item->type, OutfitRequirements::ONE_PIECE, true));
        $hasTop = collect($items)->contains(fn (Wardrobe $item) => in_array($item->type, OutfitRequirements::TOPS, true));
        $hasBottom = collect($items)->contains(fn (Wardrobe $item) => in_array($item->type, OutfitRequirements::BOTTOMS, true));
        $hasShoes = collect($items)->contains(fn (Wardrobe $item) => in_array($item->type, OutfitRequirements::FOOTWEAR, true));

        if (! $hasShoes) {
            return null;
        }

        if ($hasDress && ($hasTop || $hasBottom)) {
            return null;
        }

        if (! $hasDress && (! $hasTop || ! $hasBottom)) {
            return null;
        }

        $wardrobeIds = array_map(static fn (Wardrobe $item) => $item->id, $items);
        sort($wardrobeIds);

        return [$items, $wardrobeIds];
    }
}
