<?php

namespace App\Services\Bullets\Parsers;

use App\Models\Bullet;

abstract class BaseParser
{
    /**
     * Manufacturer name string.
     */
    abstract public function manufacturer(): string;

    /**
     * Parse bullet data from source content.
     * Returns array of bullet data arrays.
     */
    abstract public function parse(string $content, string $sourceUrl): array;

    /**
     * Resolve diameter from caliber label using the standard lookup.
     */
    protected function resolveDiameter(string $caliberLabel): ?array
    {
        return Bullet::diameterForCaliber($caliberLabel);
    }

    /**
     * Parse weight from a string like "140 gr" or "140gr" or "### GR".
     */
    protected function parseWeight(string $text): ?int
    {
        if (preg_match('/(\d+)\s*(?:gr|grain)/i', $text, $m)) {
            return (int) $m[1];
        }
        return null;
    }

    /**
     * Parse diameter in inches from a string like '0.264"' or '0.308'.
     */
    protected function parseDiameterIn(string $text): ?float
    {
        if (preg_match('/0\.(\d{3})/', $text, $m)) {
            return (float) ('0.' . $m[1]);
        }
        return null;
    }

    /**
     * Common caliber label detection from text.
     */
    protected function detectCaliberLabel(string $text): ?string
    {
        $patterns = [
            '/22\s*Cal/i' => '22 Cal',
            '/6mm/i' => '6mm',
            '/25\s*Cal/i' => '25 Cal',
            '/\b6\.5[\s\-]*(?:mm|grendel|creedmoor|prc|swede)\b/i' => '6.5mm',
            '/270\s*Cal/i' => '270 Cal',
            '/7mm/i' => '7mm',
            '/30\s*Cal/i' => '30 Cal',
            '/338\s*Cal/i' => '338 Cal',
            '/375\s*Cal/i' => '375 Cal',
            '/416\s*Cal/i' => '416 Cal',
        ];

        foreach ($patterns as $pattern => $label) {
            if (preg_match($pattern, $text)) {
                return $label;
            }
        }
        return null;
    }
}
