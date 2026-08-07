<?php

namespace App\Services\OutfitGeneration\Providers;

use App\Contracts\OutfitGeneration\OutfitCombinationProvider;
use App\Models\Core\Wardrobe;
use App\Support\OutfitRequirements;
use Illuminate\Support\Collection;

class FashnCombinationProvider implements OutfitCombinationProvider
{
    /**
     * @param  Collection<int, Wardrobe>  $wardrobeItems
     * @param  array<string, mixed>  $preferences
     * @return list<array{wardrobe_ids: list<int>, items: list<Wardrobe>, confidence: float}>
     */
    public function rankCombinations(Collection $wardrobeItems, array $preferences = []): array
    {
        unset($preferences);

        $pools = $this->buildSlotPools($wardrobeItems);

        if ($pools === []) {
            return [];
        }

        return $this->cartesianCombinations($pools);
    }

    /**
     * @param  Collection<int, Wardrobe>  $items
     * @return list<list<Wardrobe>>
     */
    private function buildSlotPools(Collection $items): array
    {
        $byType = $items->groupBy('type');
        $shoes = $this->itemsForTypes($byType, OutfitRequirements::FOOTWEAR);

        if ($shoes === []) {
            return [];
        }

        $dresses = $this->itemsForTypes($byType, OutfitRequirements::ONE_PIECE);
        $tops = $this->itemsForTypes($byType, OutfitRequirements::TOPS);
        $bottoms = $this->itemsForTypes($byType, OutfitRequirements::BOTTOMS);

        $pools = [];

        if ($dresses !== []) {
            foreach ($dresses as $dress) {
                foreach ($shoes as $shoe) {
                    $pools[] = [$dress, $shoe];
                }
            }
        }

        if ($tops !== [] && $bottoms !== []) {
            foreach ($tops as $top) {
                foreach ($bottoms as $bottom) {
                    foreach ($shoes as $shoe) {
                        $pools[] = [$top, $bottom, $shoe];
                    }
                }
            }
        }

        return $pools;
    }

    /**
     * @param  Collection<string, Collection<int, Wardrobe>>  $byType
     * @param  list<string>  $types
     * @return list<Wardrobe>
     */
    private function itemsForTypes(Collection $byType, array $types): array
    {
        $result = [];

        foreach ($types as $type) {
            foreach ($byType->get($type, collect()) as $item) {
                $result[] = $item;
            }
        }

        return $result;
    }

    /**
     * @param  list<list<Wardrobe>>  $pools
     * @return list<array{wardrobe_ids: list<int>, items: list<Wardrobe>, confidence: float}>
     */
    private function cartesianCombinations(array $pools): array
    {
        $combinations = [];

        foreach ($pools as $items) {
            $wardrobeIds = array_map(static fn (Wardrobe $item) => $item->id, $items);
            sort($wardrobeIds);

            $combinations[] = [
                'wardrobe_ids' => $wardrobeIds,
                'items' => $items,
                'confidence' => $this->averageConfidence($items),
            ];
        }

        return $combinations;
    }

    /**
     * @param  list<Wardrobe>  $items
     */
    private function averageConfidence(array $items): float
    {
        if ($items === []) {
            return 0.0;
        }

        $total = 0.0;

        foreach ($items as $item) {
            $total += (float) ($item->metadata['confidence'] ?? 0);
        }

        return round($total / count($items), 4);
    }
}
