<?php

namespace App\Services\Bullets\Parsers;

/**
 * Parser for Lapua bullet data.
 *
 * Families: Scenar, Scenar-L, MaxRange, Naturalis, Mega, Lock Base, FMJ
 * Bullet codes: GB### / N### => sku_or_part_no
 */
class LapuaParser extends BaseParser
{
    private const LINE_MAP = [
        'Scenar' => ['use' => 'match', 'construction' => 'otm'],
        'Scenar-L' => ['use' => 'match', 'construction' => 'otm'],
        'MaxRange' => ['use' => 'match', 'construction' => 'otm'],
        'Naturalis' => ['use' => 'hunting', 'construction' => 'monolithic_copper'],
        'Mega' => ['use' => 'hunting', 'construction' => 'cup_and_core'],
        'Lock Base' => ['use' => 'match', 'construction' => 'otm'],
        'FMJ' => ['use' => 'fmj', 'construction' => 'fmj'],
    ];

    public function manufacturer(): string
    {
        return 'Lapua';
    }

    public function parse(string $content, string $sourceUrl): array
    {
        // Stub: Real implementation would parse Lapua product pages
        return [];
    }

    public function mapLine(string $brandLine): array
    {
        return self::LINE_MAP[$brandLine] ?? ['use' => 'other', 'construction' => 'other'];
    }
}
