<?php

use App\Models\FirearmCalibre;
use App\Models\FirearmCalibreAlias;
use App\Models\FirearmMake;
use App\Models\FirearmModel;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Backfills existing firearm data to use the new reference system.
     * Attempts to resolve calibre/make/model strings to IDs.
     */
    public function up(): void
    {
        // Import reference data first if not already imported
        if (FirearmCalibre::count() === 0) {
            // Skip backfill if reference data doesn't exist yet
            // User should run: php artisan nrapa:import-firearm-reference first
            return;
        }

        // Backfill user_firearms
        $this->backfillUserFirearms();

        // Backfill endorsement_firearms
        $this->backfillEndorsementFirearms();
    }

    protected function backfillUserFirearms(): void
    {
        $firearms = DB::table('user_firearms')
            ->whereNull('firearm_calibre_id')
            ->whereNotNull('calibre_id')
            ->get();

        foreach ($firearms as $firearm) {
            // Try to resolve calibre
            $calibre = $this->resolveCalibre($firearm->calibre_id);

            if ($calibre) {
                DB::table('user_firearms')
                    ->where('id', $firearm->id)
                    ->update(['firearm_calibre_id' => $calibre->id]);
            } else {
                // Store in override
                $legacyCalibre = DB::table('calibres')->find($firearm->calibre_id);
                if ($legacyCalibre) {
                    DB::table('user_firearms')
                        ->where('id', $firearm->id)
                        ->update(['calibre_text_override' => $legacyCalibre->name]);
                }
            }

            // Try to resolve make/model
            if (! empty($firearm->make)) {
                $make = $this->resolveMake($firearm->make);
                if ($make) {
                    DB::table('user_firearms')
                        ->where('id', $firearm->id)
                        ->update(['firearm_make_id' => $make->id]);

                    if (! empty($firearm->model)) {
                        $model = $this->resolveModel($make->id, $firearm->model);
                        if ($model) {
                            DB::table('user_firearms')
                                ->where('id', $firearm->id)
                                ->update(['firearm_model_id' => $model->id]);
                        } else {
                            DB::table('user_firearms')
                                ->where('id', $firearm->id)
                                ->update(['model_text_override' => $firearm->model]);
                        }
                    }
                } else {
                    DB::table('user_firearms')
                        ->where('id', $firearm->id)
                        ->update([
                            'make_text_override' => $firearm->make,
                            'model_text_override' => $firearm->model ?? null,
                        ]);
                }
            }
        }
    }

    protected function backfillEndorsementFirearms(): void
    {
        $firearms = DB::table('endorsement_firearms')
            ->whereNull('firearm_calibre_id')
            ->where(function ($q) {
                $q->whereNotNull('calibre_id')
                    ->orWhereNotNull('calibre_manual');
            })
            ->get();

        foreach ($firearms as $firearm) {
            // Try to resolve calibre
            if ($firearm->calibre_id) {
                $calibre = $this->resolveCalibre($firearm->calibre_id);
                if ($calibre) {
                    DB::table('endorsement_firearms')
                        ->where('id', $firearm->id)
                        ->update(['firearm_calibre_id' => $calibre->id]);
                }
            }

            if ($firearm->calibre_manual && ! $firearm->firearm_calibre_id) {
                $calibre = $this->resolveCalibreByName($firearm->calibre_manual);
                if ($calibre) {
                    DB::table('endorsement_firearms')
                        ->where('id', $firearm->id)
                        ->update(['firearm_calibre_id' => $calibre->id]);
                } else {
                    DB::table('endorsement_firearms')
                        ->where('id', $firearm->id)
                        ->update(['calibre_text_override' => $firearm->calibre_manual]);
                }
            }

            // Try to resolve make/model
            if (! empty($firearm->make)) {
                $make = $this->resolveMake($firearm->make);
                if ($make) {
                    DB::table('endorsement_firearms')
                        ->where('id', $firearm->id)
                        ->update(['firearm_make_id' => $make->id]);

                    if (! empty($firearm->model)) {
                        $model = $this->resolveModel($make->id, $firearm->model);
                        if ($model) {
                            DB::table('endorsement_firearms')
                                ->where('id', $firearm->id)
                                ->update(['firearm_model_id' => $model->id]);
                        } else {
                            DB::table('endorsement_firearms')
                                ->where('id', $firearm->id)
                                ->update(['model_text_override' => $firearm->model]);
                        }
                    }
                } else {
                    DB::table('endorsement_firearms')
                        ->where('id', $firearm->id)
                        ->update([
                            'make_text_override' => $firearm->make,
                            'model_text_override' => $firearm->model ?? null,
                        ]);
                }
            }
        }
    }

    protected function resolveCalibre(int $legacyCalibreId): ?FirearmCalibre
    {
        $legacyCalibre = DB::table('calibres')->find($legacyCalibreId);
        if (! $legacyCalibre) {
            return null;
        }

        return $this->resolveCalibreByName($legacyCalibre->name);
    }

    protected function resolveCalibreByName(string $name): ?FirearmCalibre
    {
        $normalized = FirearmCalibre::normalize($name);

        // Try exact match
        $calibre = FirearmCalibre::where('normalized_name', $normalized)->first();
        if ($calibre) {
            return $calibre;
        }

        // Try alias match
        $alias = FirearmCalibreAlias::where('normalized_alias', $normalized)->first();
        if ($alias) {
            return $alias->calibre;
        }

        // Try partial match
        $calibre = FirearmCalibre::where('normalized_name', 'LIKE', "%{$normalized}%")
            ->orWhere('name', 'LIKE', "%{$name}%")
            ->first();

        return $calibre;
    }

    protected function resolveMake(string $name): ?FirearmMake
    {
        $normalized = FirearmMake::normalize($name);

        return FirearmMake::where('normalized_name', $normalized)
            ->orWhere('name', 'LIKE', "%{$name}%")
            ->first();
    }

    protected function resolveModel(int $makeId, string $name): ?FirearmModel
    {
        $normalized = FirearmModel::normalize($name);

        return FirearmModel::where('firearm_make_id', $makeId)
            ->where('normalized_name', $normalized)
            ->orWhere(function ($q) use ($makeId, $name) {
                $q->where('firearm_make_id', $makeId)
                    ->where('name', 'LIKE', "%{$name}%");
            })
            ->first();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Don't remove data, just clear the FK references
        DB::table('user_firearms')->update([
            'firearm_calibre_id' => null,
            'firearm_make_id' => null,
            'firearm_model_id' => null,
            'calibre_text_override' => null,
            'make_text_override' => null,
            'model_text_override' => null,
        ]);

        DB::table('endorsement_firearms')->update([
            'firearm_calibre_id' => null,
            'firearm_make_id' => null,
            'firearm_model_id' => null,
            'calibre_text_override' => null,
            'make_text_override' => null,
            'model_text_override' => null,
        ]);
    }
};
