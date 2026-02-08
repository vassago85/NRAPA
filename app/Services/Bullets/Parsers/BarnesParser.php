<?php

namespace App\Services\Bullets\Parsers;

/**
 * Parser for Barnes bullet data.
 *
 * Source: https://barnesbullets.com/bullets/
 *
 * Title format: 0.xxx" CALIBER LINE WEIGHT GR TYPE
 * e.g. '0.264" 6.5MM LRX 127 GR BT'
 */
class BarnesParser extends BaseParser
{
    /**
     * Brand line mapping.
     */
    private const LINE_MAP = [
        'LRX' => ['use' => 'hunting', 'construction' => 'monolithic_copper'],
        'TTSX' => ['use' => 'hunting', 'construction' => 'monolithic_copper'],
        'TSX' => ['use' => 'hunting', 'construction' => 'monolithic_copper'],
        'TAC-TX' => ['use' => 'tactical', 'construction' => 'monolithic_copper'],
        'MPG' => ['use' => 'tactical', 'construction' => 'frangible'],
        'Match Burner' => ['use' => 'match', 'construction' => 'cup_and_core'],
        'Varmint Grenade' => ['use' => 'varmint', 'construction' => 'frangible'],
        'M/LE TAC-X' => ['use' => 'tactical', 'construction' => 'monolithic_copper'],
    ];

    public function manufacturer(): string
    {
        return 'Barnes';
    }

    public function parse(string $content, string $sourceUrl): array
    {
        // Stub: Real implementation would crawl Barnes product pages
        return [];
    }

    /**
     * Parse a bullet from a Barnes product page title.
     * e.g. '0.264" 6.5MM LRX 127 GR BT'
     */
    public function parseFromTitle(string $title, string $sourceUrl): ?array
    {
        $diameterIn = $this->parseDiameterIn($title);
        $weight = $this->parseWeight($title);
        $caliberLabel = $this->detectCaliberLabel($title);

        if (!$weight || !$caliberLabel) {
            return null;
        }

        // Detect brand line
        $brandLine = 'Other';
        foreach (array_keys(self::LINE_MAP) as $line) {
            if (stripos($title, $line) !== false) {
                $brandLine = $line;
                break;
            }
        }

        $lineMap = self::LINE_MAP[$brandLine] ?? ['use' => 'other', 'construction' => 'other'];

        if (!$diameterIn && $caliberLabel) {
            $dims = $this->resolveDiameter($caliberLabel);
            $diameterIn = $dims['in'] ?? null;
        }

        return [
            'manufacturer' => 'Barnes',
            'brand_line' => $brandLine,
            'bullet_label' => trim($title),
            'caliber_label' => $caliberLabel,
            'weight_gr' => $weight,
            'diameter_in' => $diameterIn,
            'diameter_mm' => $diameterIn ? round($diameterIn * 25.4, 3) : null,
            'construction' => $lineMap['construction'],
            'intended_use' => $lineMap['use'],
            'source_url' => $sourceUrl,
            'status' => 'active',
        ];
    }

    /**
     * Detect twist note from page content.
     */
    public function detectTwistNote(string $html): ?string
    {
        if (preg_match('/(?:requires?|recommended?)\s+1[:\s]*(\d+(?:\.\d+)?)["\s]*twist/i', $html, $m)) {
            return "Requires 1:{$m[1]} twist or faster";
        }
        return null;
    }
}
