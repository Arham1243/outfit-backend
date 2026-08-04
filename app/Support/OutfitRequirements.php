<?php

namespace App\Support;

class OutfitRequirements
{
    public const FOOTWEAR = ['shoes'];

    public const TOPS = ['shirt', 't-shirt', 'sweater'];

    public const BOTTOMS = ['pants', 'jeans', 'shorts'];

    public const ONE_PIECE = ['dress'];

    /**
     * @param  array<string, int>  $typeCounts
     */
    public static function countForTypes(array $typeCounts, array $types): int
    {
        $total = 0;

        foreach ($types as $type) {
            $total += (int) ($typeCounts[$type] ?? 0);
        }

        return $total;
    }

    /**
     * @param  array<string, int>  $typeCounts
     * @return array<int, string>
     */
    public static function missingGroups(array $typeCounts): array
    {
        $hasFootwear = self::countForTypes($typeCounts, self::FOOTWEAR) > 0;
        $hasDress = self::countForTypes($typeCounts, self::ONE_PIECE) > 0;
        $hasTop = self::countForTypes($typeCounts, self::TOPS) > 0;
        $hasBottom = self::countForTypes($typeCounts, self::BOTTOMS) > 0;

        $missing = [];

        if (! $hasFootwear) {
            $missing[] = 'footwear';
        }

        if ($hasDress && $hasFootwear) {
            return $missing;
        }

        if (! $hasTop) {
            $missing[] = 'top';
        }

        if (! $hasBottom) {
            $missing[] = 'bottom';
        }

        return $missing;
    }

    /**
     * @param  array<string, int>  $typeCounts
     */
    public static function isSatisfied(array $typeCounts): bool
    {
        return self::missingGroups($typeCounts) === [];
    }
}
