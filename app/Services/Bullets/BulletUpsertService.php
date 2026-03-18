<?php

namespace App\Services\Bullets;

use App\Models\Bullet;
use App\Models\BulletSource;

class BulletUpsertService
{
    /**
     * Upsert a bullet from parsed data.
     * Returns the Bullet model.
     */
    public function upsert(array $data): Bullet
    {
        // Ensure both diameter units exist
        if (! empty($data['diameter_in']) && empty($data['diameter_mm'])) {
            $data['diameter_mm'] = Bullet::inToMm((float) $data['diameter_in']);
        } elseif (! empty($data['diameter_mm']) && empty($data['diameter_in'])) {
            $data['diameter_in'] = Bullet::mmToIn((float) $data['diameter_mm']);
        } elseif (empty($data['diameter_in']) && empty($data['diameter_mm'])) {
            // Try caliber lookup
            $dims = Bullet::diameterForCaliber($data['caliber_label'] ?? '');
            if ($dims) {
                $data['diameter_in'] = $dims['in'];
                $data['diameter_mm'] = $dims['mm'];
            }
        }

        // Ensure both length units if one provided
        if (! empty($data['length_in']) && empty($data['length_mm'])) {
            $data['length_mm'] = Bullet::inToMm((float) $data['length_in']);
        } elseif (! empty($data['length_mm']) && empty($data['length_in'])) {
            $data['length_in'] = Bullet::mmToIn((float) $data['length_mm']);
        }

        return Bullet::updateOrCreate(
            [
                'manufacturer' => $data['manufacturer'],
                'brand_line' => $data['brand_line'],
                'caliber_label' => $data['caliber_label'],
                'weight_gr' => (int) $data['weight_gr'],
                'sku_or_part_no' => $data['sku_or_part_no'] ?? null,
                'twist_note' => $data['twist_note'] ?? null,
                'bc_reference' => $data['bc_reference'] ?? null,
            ],
            [
                'bullet_label' => $data['bullet_label'],
                'diameter_in' => (float) $data['diameter_in'],
                'diameter_mm' => (float) $data['diameter_mm'],
                'length_in' => ! empty($data['length_in']) ? (float) $data['length_in'] : null,
                'length_mm' => ! empty($data['length_mm']) ? (float) $data['length_mm'] : null,
                'bc_g1' => ! empty($data['bc_g1']) ? (float) $data['bc_g1'] : null,
                'bc_g7' => ! empty($data['bc_g7']) ? (float) $data['bc_g7'] : null,
                'construction' => $data['construction'] ?? 'other',
                'intended_use' => $data['intended_use'] ?? 'other',
                'source_url' => $data['source_url'],
                'status' => $data['status'] ?? 'active',
                'last_verified_at' => $data['last_verified_at'] ?? now(),
            ]
        );
    }

    /**
     * Attach a source record to a bullet.
     */
    public function attachSource(Bullet $bullet, string $sourceType, string $sourceUrl, ?string $rawExcerpt = null): BulletSource
    {
        return BulletSource::create([
            'bullet_id' => $bullet->id,
            'source_type' => $sourceType,
            'source_url' => $sourceUrl,
            'captured_at' => now(),
            'raw_excerpt' => $rawExcerpt,
        ]);
    }
}
