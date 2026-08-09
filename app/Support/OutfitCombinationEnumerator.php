<?php

namespace App\Support;

use App\Models\Core\Wardrobe;
use Illuminate\Support\Collection;

class OutfitCombinationEnumerator
{
    /**
     * @param  Collection<int, Wardrobe>  $items
     * @return list<array{wardrobe_ids: list<int>}>
     */
    public static function enumerate(Collection $items): array
    {
        $pools = self::buildSlotPools($items);

        if ($pools === []) {
            return [];
        }

        $combinations = [];

        foreach ($pools as $poolItems) {
            $wardrobeIds = array_map(static fn (Wardrobe $item) => $item->id, $poolItems);
            sort($wardrobeIds);

            $combinations[] = [
                'wardrobe_ids' => $wardrobeIds,
            ];
        }

        return $combinations;
    }

    /**
     * @param  Collection<int, Wardrobe>  $items
     */
    public static function count(Collection $items): int
    {
        return count(self::enumerate($items));
    }

    /**
     * @param  Collection<int, Wardrobe>  $items
     * @return list<list<Wardrobe>>
     */
    private static function buildSlotPools(Collection $items): array
    {
        $byType = $items->groupBy('type');
        $shoes = self::itemsForTypes($byType, OutfitRequirements::FOOTWEAR);

        if ($shoes === []) {
            return [];
        }

        $dresses = self::itemsForTypes($byType, OutfitRequirements::ONE_PIECE);
        $tops = self::itemsForTypes($byType, OutfitRequirements::TOPS);
        $bottoms = self::itemsForTypes($byType, OutfitRequirements::BOTTOMS);

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
    private static function itemsForTypes(Collection $byType, array $types): array
    {
        $result = [];

        foreach ($types as $type) {
            foreach ($byType->get($type, collect()) as $item) {
                $result[] = $item;
            }
        }

        return $result;
    }
}
