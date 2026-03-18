<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FirearmCalibre;
use App\Models\FirearmMake;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class FirearmReferenceController extends Controller
{
    /**
     * Suggest calibres by query.
     * GET /api/calibres/suggest?query=&category=
     */
    public function suggestCalibres(Request $request): JsonResponse
    {
        $query = $request->get('query', '');
        $category = $request->get('category');

        if (strlen($query) < 2) {
            return response()->json([]);
        }

        $cacheKey = "calibre_suggest_{$query}_{$category}";

        $results = Cache::remember($cacheKey, 3600, function () use ($query, $category) {
            $calibres = FirearmCalibre::active()
                ->notObsolete()
                ->search($query)
                ->forCategory($category)
                ->limit(20)
                ->get();

            return $calibres->map(function ($calibre) {
                return [
                    'id' => $calibre->id,
                    'name' => $calibre->name,
                    'category' => $calibre->category,
                    'family' => $calibre->family,
                    'is_wildcat' => $calibre->is_wildcat,
                    'is_obsolete' => $calibre->is_obsolete,
                    'tags' => $calibre->tags ?? [],
                    'display' => $calibre->display_name,
                ];
            });
        });

        return response()->json($results);
    }

    /**
     * Get calibre by ID.
     * GET /api/calibres/{id}
     */
    public function getCalibre(int $id): JsonResponse
    {
        $calibre = FirearmCalibre::with('aliases')->findOrFail($id);

        return response()->json([
            'id' => $calibre->id,
            'name' => $calibre->name,
            'normalized_name' => $calibre->normalized_name,
            'category' => $calibre->category,
            'category_label' => $calibre->category_label,
            'family' => $calibre->family,
            'bullet_diameter_mm' => $calibre->bullet_diameter_mm,
            'case_length_mm' => $calibre->case_length_mm,
            'parent' => $calibre->parent,
            'is_wildcat' => $calibre->is_wildcat,
            'is_obsolete' => $calibre->is_obsolete,
            'is_active' => $calibre->is_active,
            'tags' => $calibre->tags ?? [],
            'aliases' => $calibre->aliases->pluck('alias'),
            'display' => $calibre->display_name,
        ]);
    }

    /**
     * Resolve calibre by query (exact match).
     * GET /api/calibres/resolve?query=
     */
    public function resolveCalibre(Request $request): JsonResponse
    {
        $query = $request->get('query');

        if (empty($query)) {
            return response()->json(['error' => 'Query parameter required'], 400);
        }

        $normalized = FirearmCalibre::normalize($query);

        $calibre = Cache::remember("calibre_resolve_{$normalized}", 3600, function () use ($normalized) {
            // Try exact match on normalized name
            $calibre = FirearmCalibre::where('normalized_name', $normalized)->first();

            if (! $calibre) {
                // Try alias match
                $alias = \App\Models\FirearmCalibreAlias::where('normalized_alias', $normalized)->first();
                if ($alias) {
                    $calibre = $alias->calibre;
                }
            }

            return $calibre;
        });

        if (! $calibre) {
            return response()->json(['error' => 'Calibre not found'], 404);
        }

        return $this->getCalibre($calibre->id);
    }

    /**
     * Suggest makes by query.
     * GET /api/makes/suggest?query=
     */
    public function suggestMakes(Request $request): JsonResponse
    {
        $query = $request->get('query', '');

        if (strlen($query) < 2) {
            return response()->json([]);
        }

        $cacheKey = "make_suggest_{$query}";

        $results = Cache::remember($cacheKey, 3600, function () use ($query) {
            $makes = FirearmMake::active()
                ->search($query)
                ->limit(20)
                ->get();

            return $makes->map(function ($make) {
                return [
                    'id' => $make->id,
                    'name' => $make->name,
                    'country' => $make->country,
                ];
            });
        });

        return response()->json($results);
    }

    /**
     * Get make by ID.
     * GET /api/makes/{id}
     */
    public function getMake(int $id): JsonResponse
    {
        $make = FirearmMake::with('models')->findOrFail($id);

        return response()->json([
            'id' => $make->id,
            'name' => $make->name,
            'normalized_name' => $make->normalized_name,
            'country' => $make->country,
            'is_active' => $make->is_active,
            'models' => $make->models->map(fn ($m) => [
                'id' => $m->id,
                'name' => $m->name,
                'category_hint' => $m->category_hint,
            ]),
        ]);
    }

    /**
     * Get models for a make.
     * GET /api/makes/{id}/models?suggest=
     */
    public function getMakeModels(Request $request, int $id): JsonResponse
    {
        $make = FirearmMake::findOrFail($id);
        $suggest = $request->get('suggest', '');

        $query = $make->models()->active();

        if (strlen($suggest) >= 2) {
            $query->search($suggest);
        }

        $models = $query->limit(50)->get();

        return response()->json($models->map(fn ($model) => [
            'id' => $model->id,
            'name' => $model->name,
            'full_name' => $model->full_name,
            'category_hint' => $model->category_hint,
        ]));
    }
}
