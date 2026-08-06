<?php

namespace App\Services;

use App\Models\Core\GeneratedOutfit;
use App\Models\Core\Wardrobe;
use App\Models\User;
use App\Support\OutfitRequirements;
use Illuminate\Support\Collection;

class OutfitCombinationService
{
    /**
     * @return list<array{wardrobe_ids: list<int>, items: list<Wardrobe>, confidence: float}>
     */
    public function generateForUser(User $user): array
    {
        $items = Wardrobe::query()
            ->where('user_id', $user->id)
            ->whereNotNull('type')
            ->whereNotNull('image')
            ->where('image', '!=', '')
            ->get();

        $pools = $this->buildSlotPools($items);

        if ($pools === []) {
            return [];
        }

        $combinations = $this->cartesianCombinations($pools);
        $combinations = $this->excludeRecentDuplicates($user->id, $combinations);
        $combinations = $this->capByConfidence($combinations);

        return array_values($combinations);
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

    /**
     * @param  list<array{wardrobe_ids: list<int>, items: list<Wardrobe>, confidence: float}>  $combinations
     * @return list<array{wardrobe_ids: list<int>, items: list<Wardrobe>, confidence: float}>
     */
    private function excludeRecentDuplicates(int $userId, array $combinations): array
    {
        $dedupeDays = max(1, (int) config('services.fashn.dedupe_days', 30));
        $since = now()->subDays($dedupeDays);

        $existingSets = GeneratedOutfit::query()
            ->where('user_id', $userId)
            ->where('created_at', '>=', $since)
            ->where('status', '!=', GeneratedOutfit::STATUS_FAILED)
            ->pluck('wardrobe_ids')
            ->map(function ($ids) {
                $normalized = is_array($ids) ? $ids : [];
                sort($normalized);

                return implode(',', $normalized);
            })
            ->flip();

        return array_values(array_filter(
            $combinations,
            function (array $combination) use ($existingSets) {
                $key = implode(',', $combination['wardrobe_ids']);

                return ! isset($existingSets[$key]);
            }
        ));
    }

    /**
     * @param  list<array{wardrobe_ids: list<int>, items: list<Wardrobe>, confidence: float}>  $combinations
     * @return list<array{wardrobe_ids: list<int>, items: list<Wardrobe>, confidence: float}>
     */
    private function capByConfidence(array $combinations): array
    {
        $max = max(1, (int) config('services.fashn.max_combinations', 2));

        usort($combinations, static fn (array $a, array $b) => $b['confidence'] <=> $a['confidence']);

        if (count($combinations) <= $max) {
            return $combinations;
        }

        return array_slice($combinations, 0, $max);
    }
}
