<?php

namespace App\Services;

use App\Contracts\OutfitGeneration\OutfitCombinationProvider;
use App\Models\Core\GeneratedOutfit;
use App\Models\Core\Wardrobe;
use App\Models\User;
use App\Support\OutfitCombinationEnumerator;
use App\Support\OutfitRequirements;
use App\Support\WardrobeCombinationKey;
use Illuminate\Support\Collection;

class OutfitCombinationService
{
    public function __construct(
        private readonly OutfitCombinationProvider $combinationProvider,
    ) {}

    /**
     * @return array{
     *     total_possible: int,
     *     generated_count: int,
     *     remaining: int,
     *     per_batch_limit: int,
     *     all_exhausted: bool,
     *     wardrobe_ready: bool
     * }
     */
    public function statsForUser(User $user): array
    {
        $perBatchLimit = max(1, (int) config('outfit_generation.max_combinations', 2));
        $typeCounts = $this->wardrobeTypeCountsForUser($user->id);
        $wardrobeReady = OutfitRequirements::isSatisfied($typeCounts);

        if (! $wardrobeReady) {
            return [
                'total_possible' => 0,
                'generated_count' => 0,
                'remaining' => 0,
                'per_batch_limit' => $perBatchLimit,
                'all_exhausted' => true,
                'wardrobe_ready' => false,
            ];
        }

        $items = $this->eligibleWardrobeItems($user->id);
        $totalPossible = OutfitCombinationEnumerator::count($items);
        $generatedCount = $this->countGeneratedCombinations($user->id);
        $remaining = max(0, $totalPossible - $generatedCount);

        return [
            'total_possible' => $totalPossible,
            'generated_count' => $generatedCount,
            'remaining' => $remaining,
            'per_batch_limit' => $perBatchLimit,
            'all_exhausted' => $remaining === 0,
            'wardrobe_ready' => true,
        ];
    }

    /**
     * @return list<array{wardrobe_ids: list<int>, items: list<Wardrobe>, confidence: float}>
     */
    public function generateForUser(User $user, ?int $limit = null): array
    {
        $items = $this->eligibleWardrobeItems($user->id);

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

        return array_values($this->capByConfidence($combinations, $limit));
    }

    /**
     * @return Collection<int, Wardrobe>
     */
    private function eligibleWardrobeItems(int $userId): Collection
    {
        return Wardrobe::query()
            ->where('user_id', $userId)
            ->whereNotNull('type')
            ->whereNotNull('image')
            ->where('image', '!=', '')
            ->get();
    }

    private function countGeneratedCombinations(int $userId): int
    {
        return count($this->existingCombinationKeys($userId));
    }

    /**
     * @return array<string, int>
     */
    private function existingCombinationKeys(int $userId): array
    {
        $dedupeDays = max(1, (int) config('outfit_generation.dedupe_days', 30));
        $since = now()->subDays($dedupeDays);

        $keys = [];

        GeneratedOutfit::query()
            ->where('user_id', $userId)
            ->where('created_at', '>=', $since)
            ->where('status', '!=', GeneratedOutfit::STATUS_FAILED)
            ->pluck('wardrobe_ids')
            ->each(function ($ids) use (&$keys) {
                if (! is_array($ids) || $ids === []) {
                    return;
                }

                $keys[WardrobeCombinationKey::fromIds($ids)] = 1;
            });

        return $keys;
    }

    /**
     * @param  list<array{wardrobe_ids: list<int>, items: list<Wardrobe>, confidence: float}>  $combinations
     * @return list<array{wardrobe_ids: list<int>, items: list<Wardrobe>, confidence: float}>
     */
    private function excludeRecentDuplicates(int $userId, array $combinations): array
    {
        $existingSets = $this->existingCombinationKeys($userId);

        return array_values(array_filter(
            $combinations,
            function (array $combination) use ($existingSets) {
                $key = WardrobeCombinationKey::fromIds($combination['wardrobe_ids']);

                return ! isset($existingSets[$key]);
            }
        ));
    }

    /**
     * @param  list<array{wardrobe_ids: list<int>, items: list<Wardrobe>, confidence: float}>  $combinations
     * @return list<array{wardrobe_ids: list<int>, items: list<Wardrobe>, confidence: float}>
     */
    private function capByConfidence(array $combinations, ?int $limit = null): array
    {
        $max = max(1, $limit ?? (int) config('outfit_generation.max_combinations', 2));

        usort($combinations, static fn (array $a, array $b) => $b['confidence'] <=> $a['confidence']);

        if (count($combinations) <= $max) {
            return $combinations;
        }

        return array_slice($combinations, 0, $max);
    }

    /**
     * @return array<string, int>
     */
    private function wardrobeTypeCountsForUser(int $userId): array
    {
        $rows = Wardrobe::query()
            ->where('user_id', $userId)
            ->selectRaw('type, count(*) as count')
            ->groupBy('type')
            ->get();

        $data = [];

        foreach ($rows as $row) {
            if ($row->type) {
                $data[$row->type] = (int) $row->count;
            }
        }

        return $data;
    }
}
