<?php

namespace App\Services\Bullets\Parsers;

/**
 * Parser for Nosler bullet data.
 *
 * Families: AccuBond, ABLR, Partition, Ballistic Tip, E-Tip, RDF, Custom Competition
 */
class NoslerParser extends BaseParser
{
    private const LINE_MAP = [
        'AccuBond' => ['use' => 'hunting', 'construction' => 'bonded'],
        'AccuBond Long Range' => ['use' => 'hunting', 'construction' => 'bonded'],
        'ABLR' => ['use' => 'hunting', 'construction' => 'bonded'],
        'Partition' => ['use' => 'hunting', 'construction' => 'bonded'],
        'Ballistic Tip' => ['use' => 'hunting', 'construction' => 'cup_and_core'],
        'Ballistic Tip Varmint' => ['use' => 'varmint', 'construction' => 'cup_and_core'],
        'E-Tip' => ['use' => 'hunting', 'construction' => 'monolithic_copper'],
        'RDF' => ['use' => 'match', 'construction' => 'otm'],
        'Custom Competition' => ['use' => 'match', 'construction' => 'otm'],
    ];

    public function manufacturer(): string
    {
        return 'Nosler';
    }

    public function parse(string $content, string $sourceUrl): array
    {
        // Stub: Real implementation would parse Nosler catalog pages
        return [];
    }

    public function mapLine(string $brandLine): array
    {
        return self::LINE_MAP[$brandLine] ?? ['use' => 'other', 'construction' => 'other'];
    }
}
