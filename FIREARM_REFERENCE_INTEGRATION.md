# Firearm Reference System Integration Guide

## Overview

The new Firearm Reference System provides a comprehensive, searchable database of calibres, makes, and models with a reusable `FirearmSearchPanel` Livewire component that auto-populates SAPS 271 fields.

## Quick Start

1. **Run Migrations:**
   ```bash
   php artisan migrate
   ```

2. **Import Reference Data:**
   ```bash
   php artisan nrapa:import-firearm-reference
   ```

3. **Backfill Existing Data:**
   ```bash
   php artisan migrate
   # The backfill migration will attempt to resolve existing calibre/make/model strings to IDs
   ```

## Using FirearmSearchPanel Component

### Basic Usage

In any Livewire component where you need to capture firearm data:

```blade
<livewire:firearm-search-panel 
    wire:key="firearm-panel-{{ $uniqueId }}"
/>
```

### With Initial Data

```php
// In your Livewire component
public function mount(?EndorsementFirearm $firearm = null): void
{
    if ($firearm) {
        $this->firearmPanelData = [
            'firearm_calibre_id' => $firearm->firearm_calibre_id,
            'calibre_text_override' => $firearm->calibre_text_override,
            'firearm_make_id' => $firearm->firearm_make_id,
            'make_text_override' => $firearm->make_text_override,
            'firearm_model_id' => $firearm->firearm_model_id,
            'model_text_override' => $firearm->model_text_override,
            'firearm_type' => $firearm->firearm_category,
            'action_type' => $firearm->action_type,
            'engraved_text' => $firearm->metal_engraving,
            'calibre_code' => $firearm->calibre_code,
            'barrel_serial_number' => $firearm->barrel_serial_number,
            'barrel_make_text' => $firearm->barrel_make_text,
            'frame_serial_number' => $firearm->frame_serial_number,
            'frame_make_text' => $firearm->frame_make_text,
            'receiver_serial_number' => $firearm->receiver_serial_number,
            'receiver_make_text' => $firearm->receiver_make_text,
        ];
    }
}
```

```blade
<livewire:firearm-search-panel 
    :initial-data="$firearmPanelData"
    wire:key="firearm-panel"
/>
```

### Getting Data from Component

```php
// In your save method
$firearmData = \Livewire\Livewire::mount('firearm-search-panel', [
    'initialData' => $this->firearmPanelData ?? []
])->getData();

// Or if using as nested component with wire:model
$firearmData = $this->firearmPanelData;
```

### Saving Data

```php
protected function saveRequest(): EndorsementRequest
{
    // Get data from FirearmSearchPanel
    $firearmData = $this->getFirearmPanelData(); // Implement this method
    
    $firearm = EndorsementFirearm::create([
        'endorsement_request_id' => $request->id,
        'firearm_category' => $firearmData['firearm_type'],
        'action_type' => $firearmData['action_type'],
        'metal_engraving' => $firearmData['engraved_text'],
        'firearm_calibre_id' => $firearmData['firearm_calibre_id'],
        'calibre_text_override' => $firearmData['calibre_text_override'],
        'calibre_code' => $firearmData['calibre_code'],
        'firearm_make_id' => $firearmData['firearm_make_id'],
        'make_text_override' => $firearmData['make_text_override'],
        'firearm_model_id' => $firearmData['firearm_model_id'],
        'model_text_override' => $firearmData['model_text_override'],
        'barrel_serial_number' => $firearmData['barrel_serial_number'],
        'barrel_make_text' => $firearmData['barrel_make_text'],
        'frame_serial_number' => $firearmData['frame_serial_number'],
        'frame_make_text' => $firearmData['frame_make_text'],
        'receiver_serial_number' => $firearmData['receiver_serial_number'],
        'receiver_make_text' => $firearmData['receiver_make_text'],
    ]);
    
    return $request;
}
```

## Refactoring Existing Forms

### Example: Endorsement Creation Form

Replace the existing firearm capture section with:

```blade
{{-- Step 2: Firearm Details --}}
@if($currentStep === 2)
    <livewire:firearm-search-panel 
        wire:key="endorsement-firearm-{{ $editingRequest?->id ?? 'new' }}"
        :initial-data="$this->getFirearmPanelInitialData()"
    />
@endif
```

In the component:

```php
protected function getFirearmPanelInitialData(): ?array
{
    if ($this->editingRequest && $this->editingRequest->firearm) {
        $firearm = $this->editingRequest->firearm;
        return [
            'firearm_calibre_id' => $firearm->firearm_calibre_id,
            'calibre_text_override' => $firearm->calibre_text_override,
            'firearm_make_id' => $firearm->firearm_make_id,
            'make_text_override' => $firearm->make_text_override,
            'firearm_model_id' => $firearm->firearm_model_id,
            'model_text_override' => $firearm->model_text_override,
            'firearm_type' => $this->mapCategoryToType($firearm->firearm_category),
            'action_type' => $firearm->action_type,
            'engraved_text' => $firearm->metal_engraving,
            'calibre_code' => $firearm->calibre_code,
            'barrel_serial_number' => $firearm->barrel_serial_number,
            'barrel_make_text' => $firearm->barrel_make_text,
            'frame_serial_number' => $firearm->frame_serial_number,
            'frame_make_text' => $firearm->frame_make_text,
            'receiver_serial_number' => $firearm->receiver_serial_number,
            'receiver_make_text' => $firearm->receiver_make_text,
        ];
    }
    
    return null;
}

protected function mapCategoryToType(?string $category): ?string
{
    return match($category) {
        'handgun' => 'handgun',
        'rifle_manual', 'rifle_self_loading' => 'rifle',
        'shotgun' => 'shotgun',
        default => null,
    };
}
```

## API Endpoints

### Suggest Calibres
```
GET /api/calibres/suggest?query=6.5&category=rifle
```

### Get Calibre
```
GET /api/calibres/{id}
```

### Resolve Calibre
```
GET /api/calibres/resolve?query=6.5%20Creedmoor
```

### Suggest Makes
```
GET /api/makes/suggest?query=Glock
```

### Get Make Models
```
GET /api/makes/{id}/models?suggest=T3x
```

## SAPS 271 Export View

To render a firearm's SAPS 271 description:

```php
Route::get('/firearms/{firearm}/saps271', function (EndorsementFirearm $firearm) {
    return view('documents.saps271-firearm', [
        'firearm' => $firearm->load(['firearmCalibre', 'firearmMake', 'firearmModel', 'calibre']),
    ]);
})->name('firearms.saps271');
```

## Admin Reference Data Management

Access at: `/admin/firearm-reference`

Features:
- View all calibres and makes
- Upload CSV files to update reference data
- See alias counts and model counts

## Testing

Run the import command to verify data loads:
```bash
php artisan nrapa:import-firearm-reference --force
```

Check counts:
```bash
php artisan tinker
>>> \App\Models\FirearmCalibre::count()
>>> \App\Models\FirearmMake::count()
>>> \App\Models\FirearmModel::count()
```
