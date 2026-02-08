<?php

namespace App\Services\Bullets\Parsers;

/**
 * Parser for Hornady bullet data.
 *
 * Primary sources:
 * - BC table: https://www.hornady.com/bc
 * - Catalog: https://www.hornady.com/bullets
 *
 * BC table rows format: caliber label, weight, product line, bc_g1, bc_g7
 * BC reference: "Mach 2.25" unless otherwise specified
 */
class HornadyParser extends BaseParser
{
    /**
     * Brand line to intended_use + construction mapping.
     */
    private const LINE_MAP = [
        'ELD Match' => ['use' => 'match', 'construction' => 'cup_and_core'],
        'ELD-M' => ['use' => 'match', 'construction' => 'cup_and_core'],
        'A-Tip Match' => ['use' => 'match', 'construction' => 'cup_and_core'],
        'A-TIP' => ['use' => 'match', 'construction' => 'cup_and_core'],
        'ELD-X' => ['use' => 'hunting', 'construction' => 'cup_and_core'],
        'CX' => ['use' => 'hunting', 'construction' => 'monolithic_copper'],
        'ECX' => ['use' => 'hunting', 'construction' => 'monolithic_copper'],
        'InterBond' => ['use' => 'hunting', 'construction' => 'bonded'],
        'InterLock' => ['use' => 'hunting', 'construction' => 'cup_and_core'],
        'FMJ' => ['use' => 'fmj', 'construction' => 'fmj'],
        'BTHP Match' => ['use' => 'match', 'construction' => 'otm'],
        'HAP' => ['use' => 'tactical', 'construction' => 'cup_and_core'],
        'XTP' => ['use' => 'hunting', 'construction' => 'cup_and_core'],
        'V-Max' => ['use' => 'varmint', 'construction' => 'cup_and_core'],
        'NTX' => ['use' => 'varmint', 'construction' => 'monolithic_copper'],
        'GMX' => ['use' => 'hunting', 'construction' => 'monolithic_copper'],
        'SST' => ['use' => 'hunting', 'construction' => 'cup_and_core'],
        'SP' => ['use' => 'hunting', 'construction' => 'cup_and_core'],
        'RN' => ['use' => 'hunting', 'construction' => 'cup_and_core'],
    ];

    public function manufacturer(): string
    {
        return 'Hornady';
    }

    public function parse(string $content, string $sourceUrl): array
    {
        // This is a stub - actual HTML parsing would use DomCrawler
        // For now, return empty array; real implementation parses BC table HTML
        return [];
    }

    /**
     * Parse a BC table page (e.g. from hornady.com/bc).
     * Each row: caliber, weight, line, G1, G7
     */
    public function parseBcTable(string $html): array
    {
        $bullets = [];

        // Use regex or DomCrawler to extract table rows
        // Stub: real implementation would parse the actual HTML structure
        // Expected format per row: caliber_label, weight_gr, brand_line, bc_g1, bc_g7

        return $bullets;
    }

    /**
     * Map a brand line string to intended_use and construction.
     */
    public function mapLine(string $brandLine): array
    {
        return self::LINE_MAP[$brandLine] ?? ['use' => 'other', 'construction' => 'other'];
    }

    /**
     * Build a bullet data array from BC table row.
     */
    public function buildFromBcRow(string $caliberLabel, int $weightGr, string $brandLine, ?float $bcG1, ?float $bcG7, ?string $twistNote = null): array
    {
        $lineMap = $this->mapLine($brandLine);
        $dims = $this->resolveDiameter($caliberLabel);

        return [
            'manufacturer' => 'Hornady',
            'brand_line' => $brandLine,
            'bullet_label' => "{$caliberLabel} {$weightGr} gr {$brandLine}",
            'caliber_label' => $caliberLabel,
            'weight_gr' => $weightGr,
            'diameter_in' => $dims['in'] ?? null,
            'diameter_mm' => $dims['mm'] ?? null,
            'bc_g1' => $bcG1,
            'bc_g7' => $bcG7,
            'bc_reference' => 'Mach 2.25',
            'construction' => $lineMap['construction'],
            'intended_use' => $lineMap['use'],
            'twist_note' => $twistNote,
            'source_url' => 'https://www.hornady.com/bc',
            'status' => 'active',
        ];
    }
}
