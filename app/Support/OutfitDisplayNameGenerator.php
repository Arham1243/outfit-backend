<?php

namespace App\Support;

class OutfitDisplayNameGenerator
{
    private const ADJECTIVES = [
        'Studio',
        'Off-Duty',
        'Weekend',
        'Cognac',
        'Urban',
        'Classic',
        'Modern',
        'Soft',
        'Bold',
        'Evening',
        'Daily',
        'Editorial',
    ];

    private const NOUNS = [
        'Minimal',
        'Layered',
        'Cobalt',
        'Neutral',
        'Contrast',
        'Essentials',
        'Tailored',
        'Relaxed',
        'Refined',
        'Statement',
        'Balance',
        'Form',
    ];

    public static function forUuid(string $uuid): string
    {
        $hash = crc32($uuid);
        $adjective = self::ADJECTIVES[abs($hash) % count(self::ADJECTIVES)];
        $noun = self::NOUNS[abs(intdiv($hash, count(self::ADJECTIVES))) % count(self::NOUNS)];

        return "{$adjective} {$noun}";
    }
}
