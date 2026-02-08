<?php

namespace Database\Seeders;

use App\Models\Bullet;
use Illuminate\Database\Seeder;

class BulletSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $bullets = $this->getBullets();

        foreach ($bullets as $bullet) {
            $bullet['last_verified_at'] = $now;
            $bullet['status'] = $bullet['status'] ?? 'active';
            Bullet::updateOrCreate(
                [
                    'manufacturer' => $bullet['manufacturer'],
                    'brand_line' => $bullet['brand_line'],
                    'caliber_label' => $bullet['caliber_label'],
                    'weight_gr' => $bullet['weight_gr'],
                    'sku_or_part_no' => $bullet['sku_or_part_no'] ?? null,
                    'twist_note' => $bullet['twist_note'],
                    'bc_reference' => $bullet['bc_reference'],
                ],
                $bullet
            );
        }
    }

    private function getBullets(): array
    {
        return array_merge(
            $this->hornadyEldMatch(),
            $this->hornadyEldX(),
            $this->hornadyATip(),
            $this->barnesBullets(),
        );
    }

    private function hornadyEldMatch(): array
    {
        $base = ['manufacturer' => 'Hornady', 'brand_line' => 'ELD Match', 'bc_reference' => 'Mach 2.25', 'construction' => 'cup_and_core', 'intended_use' => 'match', 'source_url' => 'https://www.hornady.com/bc'];
        return [
            $base + ['bullet_label' => '22 Cal 73 gr ELD Match', 'caliber_label' => '22 Cal', 'weight_gr' => 73, 'diameter_in' => 0.224, 'diameter_mm' => 5.690, 'bc_g1' => 0.398, 'bc_g7' => 0.200, 'twist_note' => null],
            $base + ['bullet_label' => '22 Cal 75 gr ELD Match', 'caliber_label' => '22 Cal', 'weight_gr' => 75, 'diameter_in' => 0.224, 'diameter_mm' => 5.690, 'bc_g1' => 0.467, 'bc_g7' => 0.235, 'twist_note' => null],
            $base + ['bullet_label' => '22 Cal 80 gr ELD Match', 'caliber_label' => '22 Cal', 'weight_gr' => 80, 'diameter_in' => 0.224, 'diameter_mm' => 5.690, 'bc_g1' => 0.485, 'bc_g7' => 0.244, 'twist_note' => null],
            $base + ['bullet_label' => '22 Cal 88 gr ELD Match', 'caliber_label' => '22 Cal', 'weight_gr' => 88, 'diameter_in' => 0.224, 'diameter_mm' => 5.690, 'bc_g1' => 0.545, 'bc_g7' => 0.274, 'twist_note' => null],
            $base + ['bullet_label' => '6mm 108 gr ELD Match', 'caliber_label' => '6mm', 'weight_gr' => 108, 'diameter_in' => 0.243, 'diameter_mm' => 6.172, 'bc_g1' => 0.536, 'bc_g7' => 0.270, 'twist_note' => null],
            $base + ['bullet_label' => '6.5mm 100 gr ELD Match', 'caliber_label' => '6.5mm', 'weight_gr' => 100, 'diameter_in' => 0.264, 'diameter_mm' => 6.706, 'bc_g1' => 0.385, 'bc_g7' => 0.194, 'twist_note' => null],
            $base + ['bullet_label' => '6.5mm 120 gr ELD Match', 'caliber_label' => '6.5mm', 'weight_gr' => 120, 'diameter_in' => 0.264, 'diameter_mm' => 6.706, 'bc_g1' => 0.486, 'bc_g7' => 0.245, 'twist_note' => null],
            $base + ['bullet_label' => '6.5mm 123 gr ELD Match', 'caliber_label' => '6.5mm', 'weight_gr' => 123, 'diameter_in' => 0.264, 'diameter_mm' => 6.706, 'bc_g1' => 0.506, 'bc_g7' => 0.255, 'twist_note' => null],
            $base + ['bullet_label' => '6.5mm 130 gr ELD Match', 'caliber_label' => '6.5mm', 'weight_gr' => 130, 'diameter_in' => 0.264, 'diameter_mm' => 6.706, 'bc_g1' => 0.554, 'bc_g7' => 0.279, 'twist_note' => null],
            $base + ['bullet_label' => '6.5mm 140 gr ELD Match', 'caliber_label' => '6.5mm', 'weight_gr' => 140, 'diameter_in' => 0.264, 'diameter_mm' => 6.706, 'bc_g1' => 0.646, 'bc_g7' => 0.326, 'twist_note' => null],
            $base + ['bullet_label' => '6.5mm 147 gr ELD Match', 'caliber_label' => '6.5mm', 'weight_gr' => 147, 'diameter_in' => 0.264, 'diameter_mm' => 6.706, 'bc_g1' => 0.697, 'bc_g7' => 0.351, 'twist_note' => null],
            $base + ['bullet_label' => '7mm 162 gr ELD Match', 'caliber_label' => '7mm', 'weight_gr' => 162, 'diameter_in' => 0.284, 'diameter_mm' => 7.214, 'bc_g1' => 0.670, 'bc_g7' => 0.338, 'twist_note' => null],
            $base + ['bullet_label' => '7mm 180 gr ELD Match', 'caliber_label' => '7mm', 'weight_gr' => 180, 'diameter_in' => 0.284, 'diameter_mm' => 7.214, 'bc_g1' => 0.777, 'bc_g7' => 0.391, 'twist_note' => '1 in 8.75" twist'],
            $base + ['bullet_label' => '7mm 180 gr ELD Match', 'caliber_label' => '7mm', 'weight_gr' => 180, 'diameter_in' => 0.284, 'diameter_mm' => 7.214, 'bc_g1' => 0.816, 'bc_g7' => 0.411, 'twist_note' => '1 in 7.5" twist'],
            $base + ['bullet_label' => '30 Cal 155 gr ELD Match', 'caliber_label' => '30 Cal', 'weight_gr' => 155, 'diameter_in' => 0.308, 'diameter_mm' => 7.823, 'bc_g1' => 0.461, 'bc_g7' => 0.232, 'twist_note' => null],
            $base + ['bullet_label' => '30 Cal 168 gr ELD Match', 'caliber_label' => '30 Cal', 'weight_gr' => 168, 'diameter_in' => 0.308, 'diameter_mm' => 7.823, 'bc_g1' => 0.523, 'bc_g7' => 0.263, 'twist_note' => null],
            $base + ['bullet_label' => '30 Cal 178 gr ELD Match', 'caliber_label' => '30 Cal', 'weight_gr' => 178, 'diameter_in' => 0.308, 'diameter_mm' => 7.823, 'bc_g1' => 0.547, 'bc_g7' => 0.275, 'twist_note' => null],
            $base + ['bullet_label' => '30 Cal 195 gr ELD Match', 'caliber_label' => '30 Cal', 'weight_gr' => 195, 'diameter_in' => 0.308, 'diameter_mm' => 7.823, 'bc_g1' => 0.584, 'bc_g7' => 0.294, 'twist_note' => null],
            $base + ['bullet_label' => '30 Cal 208 gr ELD Match', 'caliber_label' => '30 Cal', 'weight_gr' => 208, 'diameter_in' => 0.308, 'diameter_mm' => 7.823, 'bc_g1' => 0.690, 'bc_g7' => 0.348, 'twist_note' => null],
            $base + ['bullet_label' => '30 Cal 225 gr ELD Match', 'caliber_label' => '30 Cal', 'weight_gr' => 225, 'diameter_in' => 0.308, 'diameter_mm' => 7.823, 'bc_g1' => 0.777, 'bc_g7' => 0.391, 'twist_note' => '1 in 10" twist'],
            $base + ['bullet_label' => '30 Cal 225 gr ELD Match', 'caliber_label' => '30 Cal', 'weight_gr' => 225, 'diameter_in' => 0.308, 'diameter_mm' => 7.823, 'bc_g1' => 0.798, 'bc_g7' => 0.402, 'twist_note' => '1 in 7" twist'],
            $base + ['bullet_label' => '338 Cal 285 gr ELD Match', 'caliber_label' => '338 Cal', 'weight_gr' => 285, 'diameter_in' => 0.338, 'diameter_mm' => 8.585, 'bc_g1' => 0.829, 'bc_g7' => 0.417, 'twist_note' => null],
            $base + ['bullet_label' => '25 Cal 134 gr ELD Match', 'caliber_label' => '25 Cal', 'weight_gr' => 134, 'diameter_in' => 0.257, 'diameter_mm' => 6.528, 'bc_g1' => 0.645, 'bc_g7' => 0.325, 'twist_note' => null],
        ];
    }

    private function hornadyEldX(): array
    {
        $base = ['manufacturer' => 'Hornady', 'brand_line' => 'ELD-X', 'bc_reference' => 'Mach 2.25', 'construction' => 'cup_and_core', 'intended_use' => 'hunting', 'source_url' => 'https://www.hornady.com/bc'];
        return [
            $base + ['bullet_label' => '6mm 90 gr ELD-X', 'caliber_label' => '6mm', 'weight_gr' => 90, 'diameter_in' => 0.243, 'diameter_mm' => 6.172, 'bc_g1' => 0.410, 'bc_g7' => 0.206, 'twist_note' => null],
            $base + ['bullet_label' => '6mm 103 gr ELD-X', 'caliber_label' => '6mm', 'weight_gr' => 103, 'diameter_in' => 0.243, 'diameter_mm' => 6.172, 'bc_g1' => 0.512, 'bc_g7' => 0.258, 'twist_note' => null],
            $base + ['bullet_label' => '25 Cal 110 gr ELD-X', 'caliber_label' => '25 Cal', 'weight_gr' => 110, 'diameter_in' => 0.257, 'diameter_mm' => 6.528, 'bc_g1' => 0.465, 'bc_g7' => 0.234, 'twist_note' => null],
            $base + ['bullet_label' => '6.5mm 143 gr ELD-X', 'caliber_label' => '6.5mm', 'weight_gr' => 143, 'diameter_in' => 0.264, 'diameter_mm' => 6.706, 'bc_g1' => 0.623, 'bc_g7' => 0.314, 'twist_note' => null],
            $base + ['bullet_label' => '270 Cal 145 gr ELD-X', 'caliber_label' => '270 Cal', 'weight_gr' => 145, 'diameter_in' => 0.277, 'diameter_mm' => 7.036, 'bc_g1' => 0.536, 'bc_g7' => 0.270, 'twist_note' => null],
            $base + ['bullet_label' => '7mm 150 gr ELD-X', 'caliber_label' => '7mm', 'weight_gr' => 150, 'diameter_in' => 0.284, 'diameter_mm' => 7.214, 'bc_g1' => 0.574, 'bc_g7' => 0.289, 'twist_note' => null],
            $base + ['bullet_label' => '7mm 162 gr ELD-X', 'caliber_label' => '7mm', 'weight_gr' => 162, 'diameter_in' => 0.284, 'diameter_mm' => 7.214, 'bc_g1' => 0.631, 'bc_g7' => 0.318, 'twist_note' => null],
            $base + ['bullet_label' => '7mm 175 gr ELD-X', 'caliber_label' => '7mm', 'weight_gr' => 175, 'diameter_in' => 0.284, 'diameter_mm' => 7.214, 'bc_g1' => 0.689, 'bc_g7' => 0.347, 'twist_note' => null],
            $base + ['bullet_label' => '30 Cal 178 gr ELD-X', 'caliber_label' => '30 Cal', 'weight_gr' => 178, 'diameter_in' => 0.308, 'diameter_mm' => 7.823, 'bc_g1' => 0.552, 'bc_g7' => 0.278, 'twist_note' => null],
            $base + ['bullet_label' => '30 Cal 200 gr ELD-X', 'caliber_label' => '30 Cal', 'weight_gr' => 200, 'diameter_in' => 0.308, 'diameter_mm' => 7.823, 'bc_g1' => 0.597, 'bc_g7' => 0.301, 'twist_note' => null],
            $base + ['bullet_label' => '30 Cal 212 gr ELD-X', 'caliber_label' => '30 Cal', 'weight_gr' => 212, 'diameter_in' => 0.308, 'diameter_mm' => 7.823, 'bc_g1' => 0.663, 'bc_g7' => 0.334, 'twist_note' => '1 in 10" twist'],
            $base + ['bullet_label' => '30 Cal 212 gr ELD-X', 'caliber_label' => '30 Cal', 'weight_gr' => 212, 'diameter_in' => 0.308, 'diameter_mm' => 7.823, 'bc_g1' => 0.702, 'bc_g7' => 0.354, 'twist_note' => '1 in 7" twist'],
            $base + ['bullet_label' => '30 Cal 220 gr ELD-X', 'caliber_label' => '30 Cal', 'weight_gr' => 220, 'diameter_in' => 0.308, 'diameter_mm' => 7.823, 'bc_g1' => 0.654, 'bc_g7' => 0.329, 'twist_note' => null],
            $base + ['bullet_label' => '338 Cal 230 gr ELD-X', 'caliber_label' => '338 Cal', 'weight_gr' => 230, 'diameter_in' => 0.338, 'diameter_mm' => 8.585, 'bc_g1' => 0.616, 'bc_g7' => 0.310, 'twist_note' => null],
            $base + ['bullet_label' => '338 Cal 270 gr ELD-X', 'caliber_label' => '338 Cal', 'weight_gr' => 270, 'diameter_in' => 0.338, 'diameter_mm' => 8.585, 'bc_g1' => 0.757, 'bc_g7' => 0.381, 'twist_note' => null],
        ];
    }

    private function hornadyATip(): array
    {
        $base = ['manufacturer' => 'Hornady', 'brand_line' => 'A-Tip Match', 'bc_reference' => 'Mach 2.25', 'construction' => 'cup_and_core', 'intended_use' => 'match', 'source_url' => 'https://www.hornady.com/bc'];
        return [
            $base + ['bullet_label' => '22 Cal 76 gr A-Tip Match', 'caliber_label' => '22 Cal', 'weight_gr' => 76, 'diameter_in' => 0.224, 'diameter_mm' => 5.690, 'bc_g1' => 0.413, 'bc_g7' => 0.208, 'twist_note' => null],
            $base + ['bullet_label' => '22 Cal 90 gr A-Tip Match', 'caliber_label' => '22 Cal', 'weight_gr' => 90, 'diameter_in' => 0.224, 'diameter_mm' => 5.690, 'bc_g1' => 0.585, 'bc_g7' => 0.295, 'twist_note' => null],
            $base + ['bullet_label' => '6mm 110 gr A-Tip Match', 'caliber_label' => '6mm', 'weight_gr' => 110, 'diameter_in' => 0.243, 'diameter_mm' => 6.172, 'bc_g1' => 0.604, 'bc_g7' => 0.304, 'twist_note' => null],
            $base + ['bullet_label' => '6.5mm 135 gr A-Tip Match', 'caliber_label' => '6.5mm', 'weight_gr' => 135, 'diameter_in' => 0.264, 'diameter_mm' => 6.706, 'bc_g1' => 0.637, 'bc_g7' => 0.321, 'twist_note' => null],
            $base + ['bullet_label' => '6.5mm 153 gr A-Tip Match', 'caliber_label' => '6.5mm', 'weight_gr' => 153, 'diameter_in' => 0.264, 'diameter_mm' => 6.706, 'bc_g1' => 0.704, 'bc_g7' => 0.355, 'twist_note' => null],
            $base + ['bullet_label' => '7mm 166 gr A-Tip Match', 'caliber_label' => '7mm', 'weight_gr' => 166, 'diameter_in' => 0.284, 'diameter_mm' => 7.214, 'bc_g1' => 0.664, 'bc_g7' => 0.332, 'twist_note' => null],
            $base + ['bullet_label' => '7mm 190 gr A-Tip Match', 'caliber_label' => '7mm', 'weight_gr' => 190, 'diameter_in' => 0.284, 'diameter_mm' => 7.214, 'bc_g1' => 0.838, 'bc_g7' => 0.422, 'twist_note' => null],
            $base + ['bullet_label' => '30 Cal 176 gr A-Tip Match', 'caliber_label' => '30 Cal', 'weight_gr' => 176, 'diameter_in' => 0.308, 'diameter_mm' => 7.823, 'bc_g1' => 0.564, 'bc_g7' => 0.284, 'twist_note' => null],
            $base + ['bullet_label' => '30 Cal 230 gr A-Tip Match', 'caliber_label' => '30 Cal', 'weight_gr' => 230, 'diameter_in' => 0.308, 'diameter_mm' => 7.823, 'bc_g1' => 0.823, 'bc_g7' => 0.414, 'twist_note' => null],
            $base + ['bullet_label' => '30 Cal 250 gr A-Tip Match', 'caliber_label' => '30 Cal', 'weight_gr' => 250, 'diameter_in' => 0.308, 'diameter_mm' => 7.823, 'bc_g1' => 0.878, 'bc_g7' => 0.442, 'twist_note' => null],
            $base + ['bullet_label' => '338 Cal 300 gr A-Tip Match', 'caliber_label' => '338 Cal', 'weight_gr' => 300, 'diameter_in' => 0.338, 'diameter_mm' => 8.585, 'bc_g1' => 0.863, 'bc_g7' => 0.435, 'twist_note' => null],
            $base + ['bullet_label' => '375 Cal 390 gr A-Tip Match', 'caliber_label' => '375 Cal', 'weight_gr' => 390, 'diameter_in' => 0.375, 'diameter_mm' => 9.525, 'bc_g1' => 0.987, 'bc_g7' => 0.497, 'twist_note' => null],
            $base + ['bullet_label' => '416 Cal 500 gr A-Tip Match', 'caliber_label' => '416 Cal', 'weight_gr' => 500, 'diameter_in' => 0.416, 'diameter_mm' => 10.566, 'bc_g1' => 0.976, 'bc_g7' => 0.493, 'twist_note' => null],
        ];
    }

    private function barnesBullets(): array
    {
        return [
            // TAC-TX
            ['manufacturer' => 'Barnes', 'brand_line' => 'TAC-TX', 'bullet_label' => '30 Cal 300 BLK TAC-TX 110 gr FB', 'caliber_label' => '30 Cal', 'weight_gr' => 110, 'diameter_in' => 0.308, 'diameter_mm' => 7.823, 'bc_g1' => null, 'bc_g7' => null, 'bc_reference' => null, 'construction' => 'monolithic_copper', 'intended_use' => 'tactical', 'twist_note' => null, 'source_url' => 'https://barnesbullets.com/0-308-300-acc-blk-tac-tx-110-gr-fb/'],
            ['manufacturer' => 'Barnes', 'brand_line' => 'TAC-TX', 'bullet_label' => '30 Cal 300 BLK TAC-TX 120 gr BT', 'caliber_label' => '30 Cal', 'weight_gr' => 120, 'diameter_in' => 0.308, 'diameter_mm' => 7.823, 'bc_g1' => null, 'bc_g7' => null, 'bc_reference' => null, 'construction' => 'monolithic_copper', 'intended_use' => 'tactical', 'twist_note' => null, 'source_url' => 'https://barnesbullets.com/0-308-300-acc-blk-tac-tx-120-gr-bt/'],
            ['manufacturer' => 'Barnes', 'brand_line' => 'TAC-TX', 'bullet_label' => '6.5mm Grendel TAC-TX 115 gr BT', 'caliber_label' => '6.5mm', 'weight_gr' => 115, 'diameter_in' => 0.264, 'diameter_mm' => 6.706, 'bc_g1' => null, 'bc_g7' => null, 'bc_reference' => null, 'construction' => 'monolithic_copper', 'intended_use' => 'tactical', 'twist_note' => null, 'source_url' => 'https://barnesbullets.com/0-264-6-5-grendel-tac-tx-115-gr-bt/'],
            // LRX
            ['manufacturer' => 'Barnes', 'brand_line' => 'LRX', 'bullet_label' => '6mm LRX 95 gr BT', 'caliber_label' => '6mm', 'weight_gr' => 95, 'diameter_in' => 0.243, 'diameter_mm' => 6.172, 'bc_g1' => null, 'bc_g7' => null, 'bc_reference' => null, 'construction' => 'monolithic_copper', 'intended_use' => 'hunting', 'twist_note' => null, 'source_url' => 'https://barnesbullets.com/0-243-6mm-lrx-95-gr-bt/'],
            ['manufacturer' => 'Barnes', 'brand_line' => 'LRX', 'bullet_label' => '6.5mm LRX 127 gr BT', 'caliber_label' => '6.5mm', 'weight_gr' => 127, 'diameter_in' => 0.264, 'diameter_mm' => 6.706, 'bc_g1' => null, 'bc_g7' => null, 'bc_reference' => null, 'construction' => 'monolithic_copper', 'intended_use' => 'hunting', 'twist_note' => null, 'source_url' => 'https://barnesbullets.com/0-264-6-5mm-lrx-127-gr-bt/'],
            ['manufacturer' => 'Barnes', 'brand_line' => 'LRX', 'bullet_label' => '30 Cal LRX 212 gr BT Bore Rider', 'caliber_label' => '30 Cal', 'weight_gr' => 212, 'diameter_in' => 0.308, 'diameter_mm' => 7.823, 'bc_g1' => null, 'bc_g7' => null, 'bc_reference' => null, 'construction' => 'monolithic_copper', 'intended_use' => 'hunting', 'twist_note' => 'Requires 1:8 twist or faster', 'source_url' => 'https://barnesbullets.com/0-308-30-cal-lrx-212-gr-bt-bore-rider/'],
            // TTSX
            ['manufacturer' => 'Barnes', 'brand_line' => 'TTSX', 'bullet_label' => '30 Cal TTSX 110 gr FB', 'caliber_label' => '30 Cal', 'weight_gr' => 110, 'diameter_in' => 0.308, 'diameter_mm' => 7.823, 'bc_g1' => null, 'bc_g7' => null, 'bc_reference' => null, 'construction' => 'monolithic_copper', 'intended_use' => 'hunting', 'twist_note' => null, 'source_url' => 'https://barnesbullets.com/0-308-30-cal-ttsx-110-gr-fb/'],
            // MPG
            ['manufacturer' => 'Barnes', 'brand_line' => 'MPG', 'bullet_label' => '30 Cal MPG 150 gr FB', 'caliber_label' => '30 Cal', 'weight_gr' => 150, 'diameter_in' => 0.308, 'diameter_mm' => 7.823, 'bc_g1' => null, 'bc_g7' => null, 'bc_reference' => null, 'construction' => 'other', 'intended_use' => 'tactical', 'twist_note' => null, 'source_url' => 'https://barnesbullets.com/0-308-30-cal-mpg-150-gr-fb/'],
            // Match Burner
            ['manufacturer' => 'Barnes', 'brand_line' => 'Match Burner', 'bullet_label' => '6mm Match Burner BT 105 gr', 'caliber_label' => '6mm', 'weight_gr' => 105, 'diameter_in' => 0.243, 'diameter_mm' => 6.172, 'bc_g1' => null, 'bc_g7' => null, 'bc_reference' => null, 'construction' => 'cup_and_core', 'intended_use' => 'match', 'twist_note' => null, 'source_url' => 'https://barnesbullets.com/0-243-6mm-match-burner-bt/'],
            ['manufacturer' => 'Barnes', 'brand_line' => 'Match Burner', 'bullet_label' => '6mm Match Burner BT 112 gr', 'caliber_label' => '6mm', 'weight_gr' => 112, 'diameter_in' => 0.243, 'diameter_mm' => 6.172, 'bc_g1' => null, 'bc_g7' => null, 'bc_reference' => null, 'construction' => 'cup_and_core', 'intended_use' => 'match', 'twist_note' => null, 'source_url' => 'https://barnesbullets.com/0-243-6mm-match-burner-bt/'],
            ['manufacturer' => 'Barnes', 'brand_line' => 'Match Burner', 'bullet_label' => '6.5mm Match Burner BT 120 gr', 'caliber_label' => '6.5mm', 'weight_gr' => 120, 'diameter_in' => 0.264, 'diameter_mm' => 6.706, 'bc_g1' => null, 'bc_g7' => null, 'bc_reference' => null, 'construction' => 'cup_and_core', 'intended_use' => 'match', 'twist_note' => null, 'source_url' => 'https://barnesbullets.com/0-264-6-5mm-match-burner-bt/'],
            ['manufacturer' => 'Barnes', 'brand_line' => 'Match Burner', 'bullet_label' => '6.5mm Match Burner BT 140 gr', 'caliber_label' => '6.5mm', 'weight_gr' => 140, 'diameter_in' => 0.264, 'diameter_mm' => 6.706, 'bc_g1' => null, 'bc_g7' => null, 'bc_reference' => null, 'construction' => 'cup_and_core', 'intended_use' => 'match', 'twist_note' => null, 'source_url' => 'https://barnesbullets.com/0-264-6-5mm-match-burner-bt/'],
            ['manufacturer' => 'Barnes', 'brand_line' => 'Match Burner', 'bullet_label' => '6.5mm Match Burner BT 145 gr', 'caliber_label' => '6.5mm', 'weight_gr' => 145, 'diameter_in' => 0.264, 'diameter_mm' => 6.706, 'bc_g1' => null, 'bc_g7' => null, 'bc_reference' => null, 'construction' => 'cup_and_core', 'intended_use' => 'match', 'twist_note' => null, 'source_url' => 'https://barnesbullets.com/0-264-6-5mm-match-burner-bt/'],
        ];
    }
}
