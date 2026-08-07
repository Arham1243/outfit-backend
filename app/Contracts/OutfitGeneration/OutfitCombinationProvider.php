<?php

namespace App\Contracts\OutfitGeneration;

use App\Models\Core\Wardrobe;
use Illuminate\Support\Collection;

interface OutfitCombinationProvider
{
    /**
     * @param  Collection<int, Wardrobe>  $wardrobeItems
     * @param  array<string, mixed>  $preferences
     * @return list<array{wardrobe_ids: list<int>, items: list<Wardrobe>, confidence: float}>
     */
    public function rankCombinations(Collection $wardrobeItems, array $preferences = []): array;
}
