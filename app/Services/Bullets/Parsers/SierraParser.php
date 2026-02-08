<?php

namespace App\Services\Bullets\Parsers;

/**
 * Parser for Sierra bullet data.
 *
 * Families: MatchKing, TMK, GameKing, Pro-Hunter, Varminter
 */
class SierraParser extends BaseParser
{
    private const LINE_MAP = [
        'MatchKing' => ['use' => 'match', 'construction' => 'otm'],
        'TMK' => ['use' => 'match', 'construction' => 'otm'],
        'Tipped MatchKing' => ['use' => 'match', 'construction' => 'otm'],
        'GameKing' => ['use' => 'hunting', 'construction' => 'cup_and_core'],
        'Pro-Hunter' => ['use' => 'hunting', 'construction' => 'cup_and_core'],
        'Varminter' => ['use' => 'varmint', 'construction' => 'cup_and_core'],
        'BlitzKing' => ['use' => 'varmint', 'construction' => 'cup_and_core'],
        'GameChanger' => ['use' => 'hunting', 'construction' => 'cup_and_core'],
    ];

    public function manufacturer(): string
    {
        return 'Sierra';
    }

    public function parse(string $content, string $sourceUrl): array
    {
        // Stub: Real implementation would parse Sierra product pages
        return [];
    }

    public function mapLine(string $brandLine): array
    {
        return self::LINE_MAP[$brandLine] ?? ['use' => 'other', 'construction' => 'other'];
    }
}
