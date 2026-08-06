<?php

namespace App\Livewire;

use App\Models\FirearmCalibre;
use App\Models\FirearmMake;
use App\Models\FirearmModel;
use Illuminate\Support\Facades\Schema;
use Livewire\Attributes\Computed;
use Livewire\Component;

class FirearmSearchPanel extends Component
{
    // Calibre search
    public string $calibreSearch = '';

    public ?int $firearmCalibreId = null;

    public ?string $calibreTextOverride = null;

    // Optional second calibre (for combination firearms)
    public bool $allowSecondCalibre = false;

    public string $calibreSearch2 = '';

    public ?int $firearmCalibreId2 = null;

    public ?string $calibreTextOverride2 = null;

    public bool $showCalibreOverride2 = false;

    // Make/Model search
    public string $makeSearch = '';

    public ?int $firearmMakeId = null;

    public ?string $makeTextOverride = null;

    public string $modelSearch = '';

    public ?int $firearmModelId = null;

    public ?string $modelTextOverride = null;

    // SAPS 271 fields
    public string $firearmType = '';

    public string $firearmTypeOther = '';

    public string $actionType = '';

    public string $actionTypeOther = '';

    public ?string $engravedText = null;

    public ?string $calibreCode = null;

    // Serial numbers
    public ?string $barrelSerialNumber = null;

    public ?string $barrelMakeText = null;

    public ?string $frameSerialNumber = null;

    public ?string $frameMakeText = null;

    public ?string $receiverSerialNumber = null;

    public ?string $receiverMakeText = null;

    // Category filter for calibres
    public ?string $calibreCategory = null;

    // Multi-category filter (used for combination firearms → ['rifle','shotgun']).
    // When set, takes precedence over $calibreCategory.
    public ?array $calibreCategories = null;

    // Show override option
    public bool $showCalibreOverride = false;

    public bool $showMakeOverride = false;

    public bool $showModelOverride = false;

    /**
     * Mount the component with optional initial values.
     */
    public function mount($initialData = [], bool $allowSecondCalibre = false, ?array $calibreCategories = null): void
    {
        $this->allowSecondCalibre = $allowSecondCalibre;
        $this->calibreCategories = $calibreCategories;

        if (is_array($initialData) && ! empty($initialData)) {
            $this->hydrateFromData($initialData);
        }
    }

    /**
     * Hydrate component from existing firearm data.
     */
    public function hydrateFromData(array $data): void
    {
        $this->firearmCalibreId = $data['firearm_calibre_id'] ?? null;
        $this->calibreTextOverride = $data['calibre_text_override'] ?? null;
        $this->firearmMakeId = $data['firearm_make_id'] ?? null;
        $this->makeTextOverride = $data['make_text_override'] ?? null;
        $this->firearmModelId = $data['firearm_model_id'] ?? null;
        $this->modelTextOverride = $data['model_text_override'] ?? null;
        $this->firearmType = $data['firearm_type'] ?? '';
        $this->firearmTypeOther = $data['firearm_type_other'] ?? '';
        $this->actionType = $data['action_type'] ?? '';
        $this->actionTypeOther = $data['action_type_other'] ?? '';
        $this->engravedText = $data['engraved_text'] ?? null;
        $this->calibreCode = $data['calibre_code'] ?? null;
        $this->barrelSerialNumber = $data['barrel_serial_number'] ?? null;
        $this->barrelMakeText = $data['barrel_make_text'] ?? null;
        $this->frameSerialNumber = $data['frame_serial_number'] ?? null;
        $this->frameMakeText = $data['frame_make_text'] ?? null;
        $this->receiverSerialNumber = $data['receiver_serial_number'] ?? null;
        $this->receiverMakeText = $data['receiver_make_text'] ?? null;

        // Derive calibre category from firearm type
        $this->calibreCategory = $this->mapFirearmTypeToCategory($this->firearmType);

        // Second calibre (combination firearms)
        $this->firearmCalibreId2 = $data['firearm_calibre_id_2'] ?? null;
        $this->calibreTextOverride2 = $data['calibre_text_override_2'] ?? null;

        // Set search terms from selected items
        if ($this->firearmCalibreId) {
            $calibre = FirearmCalibre::find($this->firearmCalibreId);
            if ($calibre) {
                $this->calibreSearch = $calibre->name;
            }
        } elseif ($this->calibreTextOverride) {
            $this->calibreSearch = $this->calibreTextOverride;
            $this->showCalibreOverride = true;
        }

        if ($this->firearmCalibreId2) {
            $calibre2 = FirearmCalibre::find($this->firearmCalibreId2);
            if ($calibre2) {
                $this->calibreSearch2 = $calibre2->name;
            }
        } elseif ($this->calibreTextOverride2) {
            $this->calibreSearch2 = $this->calibreTextOverride2;
            $this->showCalibreOverride2 = true;
        }

        if ($this->firearmMakeId) {
            $make = FirearmMake::find($this->firearmMakeId);
            if ($make) {
                $this->makeSearch = $make->name;
            }
        } elseif ($this->makeTextOverride) {
            $this->makeSearch = $this->makeTextOverride;
            $this->showMakeOverride = true;
        }

        if ($this->firearmModelId) {
            $model = FirearmModel::find($this->firearmModelId);
            if ($model) {
                $this->modelSearch = $model->name;
            }
        } elseif ($this->modelTextOverride) {
            $this->modelSearch = $this->modelTextOverride;
            $this->showModelOverride = true;
        }
    }

    /**
     * Get calibre suggestions.
     */
    #[Computed]
    public function calibreSuggestions()
    {
        return $this->buildCalibreSuggestions($this->calibreSearch);
    }

    /**
     * Get second calibre suggestions (combination firearms).
     */
    #[Computed]
    public function calibreSuggestions2()
    {
        return $this->buildCalibreSuggestions($this->calibreSearch2);
    }

    /**
     * Shared query builder for calibre typeaheads.
     * Honours $calibreCategories (multi) or $calibreCategory (single).
     */
    protected function buildCalibreSuggestions(string $term)
    {
        if (strlen($term) < 2) {
            return collect();
        }

        // Check if table exists (migrations may not have run yet)
        if (! Schema::hasTable('firearm_calibres')) {
            return collect();
        }

        try {
            $query = FirearmCalibre::active()
                ->notObsolete()
                ->search($term);

            if (! empty($this->calibreCategories)) {
                $query->whereIn('category', $this->calibreCategories);
            } elseif ($this->calibreCategory) {
                $query->forCategory($this->calibreCategory);
            }

            return $query->limit(20)->get();
        } catch (\Exception $e) {
            // Table doesn't exist or other error - return empty collection
            return collect();
        }
    }

    /**
     * Get make suggestions.
     */
    #[Computed]
    public function makeSuggestions()
    {
        if (strlen($this->makeSearch) < 2) {
            return collect();
        }

        // Check if table exists
        if (! Schema::hasTable('firearm_makes')) {
            return collect();
        }

        try {
            return FirearmMake::active()
                ->search($this->makeSearch)
                ->limit(20)
                ->get();
        } catch (\Exception $e) {
            return collect();
        }
    }

    /**
     * Get model suggestions.
     */
    #[Computed]
    public function modelSuggestions()
    {
        if (strlen($this->modelSearch) < 2) {
            return collect();
        }

        // Check if table exists
        if (! Schema::hasTable('firearm_models')) {
            return collect();
        }

        try {
            $query = FirearmModel::active()
                ->search($this->modelSearch);

            if ($this->firearmMakeId) {
                $query->forMake($this->firearmMakeId);
            }

            if ($this->calibreCategory) {
                // Map firearm type to category hint
                $categoryHint = match ($this->firearmType) {
                    'handgun' => 'handgun',
                    'rifle', 'self_loading_rifle' => 'rifle',
                    'shotgun' => 'shotgun',
                    default => null,
                };
                if ($categoryHint) {
                    $query->forCategory($categoryHint);
                }
            }

            return $query->limit(20)->get();
        } catch (\Exception $e) {
            return collect();
        }
    }

    /**
     * Get selected calibre metadata.
     */
    #[Computed]
    public function selectedCalibre()
    {
        return $this->resolveSelectedCalibre($this->firearmCalibreId);
    }

    /**
     * Get selected second calibre metadata.
     */
    #[Computed]
    public function selectedCalibre2()
    {
        return $this->resolveSelectedCalibre($this->firearmCalibreId2);
    }

    protected function resolveSelectedCalibre(?int $id)
    {
        if (! $id) {
            return null;
        }

        if (! Schema::hasTable('firearm_calibres')) {
            return null;
        }

        try {
            return FirearmCalibre::with('aliases')->find($id);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Select a calibre.
     */
    public function selectCalibre(int $calibreId): void
    {
        $calibre = FirearmCalibre::find($calibreId);
        if ($calibre) {
            $this->firearmCalibreId = $calibreId;
            $this->calibreSearch = $calibre->name;
            $this->calibreTextOverride = null;
            $this->showCalibreOverride = false;
        }
    }

    /**
     * Clear calibre selection.
     */
    public function clearCalibre(): void
    {
        $this->firearmCalibreId = null;
        $this->calibreSearch = '';
        $this->calibreTextOverride = null;
        $this->showCalibreOverride = false;
    }

    /**
     * Use custom calibre text.
     */
    public function useCustomCalibre(): void
    {
        if (! empty($this->calibreSearch)) {
            $this->calibreTextOverride = $this->calibreSearch;
            $this->firearmCalibreId = null;
            $this->showCalibreOverride = true;
        }
    }

    /**
     * Select the second calibre (combination firearms).
     */
    public function selectCalibre2(int $calibreId): void
    {
        $calibre = FirearmCalibre::find($calibreId);
        if ($calibre) {
            $this->firearmCalibreId2 = $calibreId;
            $this->calibreSearch2 = $calibre->name;
            $this->calibreTextOverride2 = null;
            $this->showCalibreOverride2 = false;
        }
    }

    /**
     * Clear the second calibre selection.
     */
    public function clearCalibre2(): void
    {
        $this->firearmCalibreId2 = null;
        $this->calibreSearch2 = '';
        $this->calibreTextOverride2 = null;
        $this->showCalibreOverride2 = false;
    }

    /**
     * Use custom text for the second calibre.
     */
    public function useCustomCalibre2(): void
    {
        if (! empty($this->calibreSearch2)) {
            $this->calibreTextOverride2 = $this->calibreSearch2;
            $this->firearmCalibreId2 = null;
            $this->showCalibreOverride2 = true;
        }
    }

    /**
     * Select a make.
     */
    public function selectMake(int $makeId): void
    {
        $make = FirearmMake::find($makeId);
        if ($make) {
            $this->firearmMakeId = $makeId;
            $this->makeSearch = $make->name;
            $this->makeTextOverride = null;
            $this->showMakeOverride = false;

            // Clear model if make changes
            $this->firearmModelId = null;
            $this->modelSearch = '';
            $this->modelTextOverride = null;
            $this->showModelOverride = false;
        }
    }

    /**
     * Clear make selection.
     */
    public function clearMake(): void
    {
        $this->firearmMakeId = null;
        $this->makeSearch = '';
        $this->makeTextOverride = null;
        $this->showMakeOverride = false;

        // Clear model too
        $this->clearModel();
    }

    /**
     * Use custom make text.
     */
    public function useCustomMake(): void
    {
        if (! empty($this->makeSearch)) {
            $this->makeTextOverride = $this->makeSearch;
            $this->firearmMakeId = null;
            $this->showMakeOverride = true;
        }
    }

    /**
     * Select a model.
     */
    public function selectModel(int $modelId): void
    {
        $model = FirearmModel::find($modelId);
        if ($model) {
            $this->firearmModelId = $modelId;
            $this->modelSearch = $model->name;
            $this->modelTextOverride = null;
            $this->showModelOverride = false;
        }
    }

    /**
     * Clear model selection.
     */
    public function clearModel(): void
    {
        $this->firearmModelId = null;
        $this->modelSearch = '';
        $this->modelTextOverride = null;
        $this->showModelOverride = false;
    }

    /**
     * Use custom model text.
     */
    public function useCustomModel(): void
    {
        if (! empty($this->modelSearch)) {
            $this->modelTextOverride = $this->modelSearch;
            $this->firearmModelId = null;
            $this->showModelOverride = true;
        }
    }

    /**
     * Get all data as array for saving.
     * Auto-commits typed text as override when no dropdown selection was made.
     */
    public function getData(): array
    {
        $makeOverride = $this->makeTextOverride;
        if (! $this->firearmMakeId && ! $makeOverride && trim($this->makeSearch) !== '') {
            $makeOverride = trim($this->makeSearch);
        }

        $modelOverride = $this->modelTextOverride;
        if (! $this->firearmModelId && ! $modelOverride && trim($this->modelSearch) !== '') {
            $modelOverride = trim($this->modelSearch);
        }

        $calibreOverride = $this->calibreTextOverride;
        if (! $this->firearmCalibreId && ! $calibreOverride && trim($this->calibreSearch) !== '') {
            $calibreOverride = trim($this->calibreSearch);
        }

        // Second calibre (only surfaced when the parent enabled it)
        $calibreOverride2 = null;
        $firearmCalibreId2 = null;
        if ($this->allowSecondCalibre) {
            $firearmCalibreId2 = $this->firearmCalibreId2;
            $calibreOverride2 = $this->calibreTextOverride2;
            if (! $firearmCalibreId2 && ! $calibreOverride2 && trim($this->calibreSearch2) !== '') {
                $calibreOverride2 = trim($this->calibreSearch2);
            }
        }

        return [
            'firearm_calibre_id' => $this->firearmCalibreId,
            'calibre_text_override' => $calibreOverride,
            'firearm_calibre_id_2' => $firearmCalibreId2,
            'calibre_text_override_2' => $calibreOverride2,
            'firearm_make_id' => $this->firearmMakeId,
            'make_text_override' => $makeOverride,
            'firearm_model_id' => $this->firearmModelId,
            'model_text_override' => $modelOverride,
            'firearm_type' => $this->firearmType,
            'firearm_type_other' => $this->firearmTypeOther,
            'action_type' => $this->actionType,
            'action_type_other' => $this->actionTypeOther,
            'engraved_text' => $this->engravedText,
            'calibre_code' => $this->calibreCode,
            'barrel_serial_number' => $this->barrelSerialNumber,
            'barrel_make_text' => $this->barrelMakeText,
            'frame_serial_number' => $this->frameSerialNumber,
            'frame_make_text' => $this->frameMakeText,
            'receiver_serial_number' => $this->receiverSerialNumber,
            'receiver_make_text' => $this->receiverMakeText,
        ];
    }

    /**
     * Map firearm type to calibre category for filtering.
     */
    protected function mapFirearmTypeToCategory(?string $firearmType): ?string
    {
        return match ($firearmType) {
            'rifle', 'self_loading_rifle' => 'rifle',
            'shotgun' => 'shotgun',
            'handgun' => 'handgun',
            default => null,
        };
    }

    /**
     * When firearm type changes, update calibre category filter.
     */
    public function updatedFirearmType(): void
    {
        $this->calibreCategory = $this->mapFirearmTypeToCategory($this->firearmType);
        // Clear calibre selection when type changes (it may no longer be valid)
        // Only clear if the category actually changed
        unset($this->calibreSuggestions);
    }

    /**
     * Emit data to parent component when values change.
     */
    public function updated($propertyName): void
    {
        // Emit data whenever any firearm field changes
        if (str_starts_with($propertyName, 'firearm') ||
            str_starts_with($propertyName, 'calibre') ||
            str_starts_with($propertyName, 'make') ||
            str_starts_with($propertyName, 'model') ||
            str_starts_with($propertyName, 'action') ||
            str_starts_with($propertyName, 'barrel') ||
            str_starts_with($propertyName, 'frame') ||
            str_starts_with($propertyName, 'receiver') ||
            str_starts_with($propertyName, 'engraved')) {
            $this->dispatch('firearm-data-updated', data: $this->getData());
        }
    }

    public function render()
    {
        return view('livewire.firearm-search-panel');
    }
}
