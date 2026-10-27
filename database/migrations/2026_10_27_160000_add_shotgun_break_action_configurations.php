<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Shotgun break-action configurations requested for the action list
     * and the activity firearm-type list.
     */
    public function up(): void
    {
        $this->expandActionTypeColumn('endorsement_firearms');
        $this->expandActionTypeColumn('firearm_types');

        if (! Schema::hasTable('firearm_types')) {
            return;
        }

        $this->addBreakActionShotgunTypes();
    }

    public function down(): void
    {
        if (Schema::hasTable('firearm_types')) {
            DB::table('firearm_types')
                ->whereIn('slug', [
                    'break-action-shotgun-over-under',
                    'break-action-shotgun-side-by-side',
                ])
                ->delete();
        }

        if (! in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }

        if (Schema::hasTable('endorsement_firearms') && Schema::hasColumn('endorsement_firearms', 'action_type')) {
            DB::table('endorsement_firearms')
                ->whereIn('action_type', ['over_under', 'side_by_side'])
                ->update(['action_type' => 'break_action']);

            DB::statement("ALTER TABLE endorsement_firearms MODIFY COLUMN action_type ENUM('single_shot','revolver','semi_auto','bolt_action','lever_action','pump_action','break_action','other') NULL");
        }

        if (Schema::hasTable('firearm_types') && Schema::hasColumn('firearm_types', 'action_type')) {
            DB::table('firearm_types')
                ->whereIn('action_type', ['over_under', 'side_by_side'])
                ->update(['action_type' => 'break_action']);

            DB::statement("ALTER TABLE firearm_types MODIFY COLUMN action_type ENUM('single_shot','revolver','semi_auto','bolt_action','lever_action','pump_action','break_action','other') NULL");
        }
    }

    private function expandActionTypeColumn(string $table): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'action_type')) {
            return;
        }

        $values = [
            'single_shot',
            'revolver',
            'semi_auto',
            'bolt_action',
            'lever_action',
            'pump_action',
            'break_action',
            'over_under',
            'side_by_side',
            'other',
        ];

        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            $enum = "ENUM('".implode("','", $values)."')";
            DB::statement("ALTER TABLE {$table} MODIFY COLUMN action_type {$enum} NULL");

            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($values) {
            $blueprint->enum('action_type', $values)->nullable()->change();
        });
    }

    private function addBreakActionShotgunTypes(): void
    {
        $types = [
            [
                'slug' => 'break-action-shotgun-over-under',
                'name' => 'Break Neck - Under Over',
                'action_type' => 'over_under',
                'description' => 'Break-action shotgun with the barrels stacked one above the other',
            ],
            [
                'slug' => 'break-action-shotgun-side-by-side',
                'name' => 'Break Neck - Side by Side',
                'action_type' => 'side_by_side',
                'description' => 'Break-action shotgun with the barrels beside each other',
            ],
        ];

        foreach ($types as $type) {
            DB::table('firearm_types')->where('slug', $type['slug'])->update([
                'name' => $type['name'],
                'action_type' => $type['action_type'],
                'description' => $type['description'],
                'updated_at' => now(),
            ]);
        }

        $missing = array_values(array_filter(
            $types,
            fn (array $type) => ! DB::table('firearm_types')->where('slug', $type['slug'])->exists()
        ));

        if ($missing === []) {
            return;
        }

        $parent = DB::table('firearm_types')->where('slug', 'break-action-shotgun')->first();
        if ($parent) {
            DB::table('firearm_types')
                ->where('sort_order', '>', $parent->sort_order)
                ->increment('sort_order', count($missing));
            $sort = (int) $parent->sort_order;
        } else {
            $sort = (int) DB::table('firearm_types')->max('sort_order');
        }

        $now = now();
        $hasSapsCode = Schema::hasColumn('firearm_types', 'saps_code');
        $hasDescription = Schema::hasColumn('firearm_types', 'description');
        $hasCategory = Schema::hasColumn('firearm_types', 'category');

        foreach ($missing as $type) {
            $sort++;
            $row = [
                'slug' => $type['slug'],
                'name' => $type['name'],
                'dedicated_type' => 'both',
                'is_active' => true,
                'sort_order' => $sort,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            if ($hasSapsCode) {
                $row['saps_code'] = '22';
            }
            if ($hasCategory) {
                $row['category'] = 'shotgun';
                $row['ignition_type'] = 'centerfire';
                $row['action_type'] = $type['action_type'];
            }
            if ($hasDescription) {
                $row['description'] = $type['description'];
            }

            DB::table('firearm_types')->insert($row);
        }
    }
};
