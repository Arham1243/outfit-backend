<?php

namespace App\Services;

use App\Contracts\OutfitGeneration\OutfitCombinationProvider;
use App\Models\Core\GeneratedOutfit;
use App\Models\Core\Wardrobe;
use App\Models\User;

class OutfitCombinationService
{
    public function __construct(
        private readonly OutfitCombinationProvider $combinationProvider,
    ) {}

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

        if ($items->isEmpty()) {
            return [];
        }

        $combinations = $this->combinationProvider->rankCombinations($items, [
            'gender' => $user->gender,
            'height' => $user->height,
        ]);

        if ($combinations === []) {
            return [];
        }

        $combinations = $this->excludeRecentDuplicates($user->id, $combinations);

        return array_values($this->capByConfidence($combinations));
    }

    /**
     * @param  list<array{wardrobe_ids: list<int>, items: list<Wardrobe>, confidence: float}>  $combinations
     * @return list<array{wardrobe_ids: list<int>, items: list<Wardrobe>, confidence: float}>
     */
    private function excludeRecentDuplicates(int $userId, array $combinations): array
    {
        $dedupeDays = max(1, (int) config('outfit_generation.dedupe_days', 30));
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
        $max = max(1, (int) config('outfit_generation.max_combinations', 2));

        usort($combinations, static fn (array $a, array $b) => $b['confidence'] <=> $a['confidence']);

        if (count($combinations) <= $max) {
            return $combinations;
        }

        return array_slice($combinations, 0, $max);
    }
}
