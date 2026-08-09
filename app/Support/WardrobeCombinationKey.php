<?php

namespace App\Support;

class WardrobeCombinationKey
{
    /**
     * @param  list<int>|array<int, int>  $ids
     */
    public static function fromIds(array $ids): string
    {
        $normalized = array_values($ids);
        sort($normalized);

        return implode(',', $normalized);
    }
}
