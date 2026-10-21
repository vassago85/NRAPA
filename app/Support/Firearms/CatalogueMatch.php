<?php

namespace App\Support\Firearms;

use App\Models\FirearmCalibre;
use App\Models\FirearmCalibreAlias;
use App\Models\FirearmMake;

class CatalogueMatch
{
    /**
     * Keep a chosen catalogue id, or resolve a typed name / alias such as ".375 H&H".
     */
    public static function calibreId(?int $selectedId, ?string $text): ?int
    {
        if ($selectedId) {
            return $selectedId;
        }

        $text = trim((string) $text);
        $keys = self::calibreKeys($text);
        if ($text === '' || $keys === []) {
            return null;
        }

        $byName = FirearmCalibre::query()
            ->active()
            ->where(function ($query) use ($keys, $text) {
                $query->whereIn('normalized_name', $keys)
                    ->orWhereRaw('LOWER(name) = ?', [mb_strtolower($text)]);
            })
            ->value('id');

        if ($byName) {
            return (int) $byName;
        }

        $aliasId = FirearmCalibreAlias::query()
            ->whereIn('normalized_alias', $keys)
            ->whereHas('calibre', fn ($query) => $query->active())
            ->value('firearm_calibre_id');

        return $aliasId ? (int) $aliasId : null;
    }

    /**
     * Keep a chosen catalogue id, or resolve a typed make such as "Sauer & Sohn".
     */
    public static function makeId(?int $selectedId, ?string $text): ?int
    {
        if ($selectedId) {
            return $selectedId;
        }

        $text = trim((string) $text);
        $keys = self::makeKeys($text);
        if ($text === '' || $keys === []) {
            return null;
        }

        $id = FirearmMake::query()
            ->active()
            ->where(function ($query) use ($keys, $text) {
                $query->whereIn('normalized_name', $keys)
                    ->orWhereRaw('LOWER(name) = ?', [mb_strtolower($text)]);
            })
            ->value('id');

        return $id ? (int) $id : null;
    }

    /**
     * @return list<string>
     */
    private static function calibreKeys(string $text): array
    {
        $keys = [];
        foreach (self::ampersandVariants($text) as $variant) {
            $keys[] = FirearmCalibre::normalize($variant);
            $collapsed = preg_replace('/\s*&\s*/', '&', $variant) ?? $variant;
            $keys[] = FirearmCalibre::normalize($collapsed);
        }

        return array_values(array_unique(array_filter($keys)));
    }

    /**
     * @return list<string>
     */
    private static function makeKeys(string $text): array
    {
        return array_values(array_unique(array_filter(array_map(
            fn (string $variant) => FirearmMake::normalize($variant),
            self::ampersandVariants($text),
        ))));
    }

    /**
     * Spellings members actually type: "H&H", "H & H", and "H and H".
     *
     * @return list<string>
     */
    private static function ampersandVariants(string $text): array
    {
        $text = trim($text);
        $andToAmp = preg_replace('/\band\b/i', '&', $text) ?? $text;
        $spaced = trim((string) preg_replace('/\s+/', ' ', (string) preg_replace('/\s*&\s*/', ' & ', $andToAmp)));

        return array_values(array_unique(array_filter([$text, $spaced, trim($andToAmp)])));
    }
}
