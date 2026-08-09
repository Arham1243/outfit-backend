<?php

namespace App\Services\OutfitGeneration\Providers;

use App\Contracts\OutfitGeneration\OutfitCombinationProvider;
use App\Models\Core\Wardrobe;
use App\Support\OutfitCombinationEnumerator;
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
        $enumerated = OutfitCombinationEnumerator::enumerate($items);

        if ($enumerated === []) {
            return [];
        }

        $itemsById = $items->keyBy('id');
        $pools = [];

        foreach ($enumerated as $entry) {
            $poolItems = [];

            foreach ($entry['wardrobe_ids'] as $id) {
                $item = $itemsById->get($id);

                if ($item instanceof Wardrobe) {
                    $poolItems[] = $item;
                }
            }

            if ($poolItems !== []) {
                $pools[] = $poolItems;
            }
        }

        return $pools;
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
                'confidence' => 0.0,
            ];
        }

        return $combinations;
    }
}
