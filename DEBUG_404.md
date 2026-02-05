# Debugging 404 Error

## Error Details
- **Error**: "Failed to load resource: the server responded with a status of 404 (Not Found) create:1"
- **Location**: Browser console when loading endorsement create page

## Possible Causes

### 1. Livewire Component Not Found
The component `firearm-search-panel` might not be auto-discovered by Livewire.

**Fix**: Try using the full class name:
```blade
@livewire(\App\Livewire\FirearmSearchPanel::class, [
    'initialData' => $firearmPanelData
], key('endorsement-firearm-panel-' . ($editingRequest?->id ?? 'new')))
```

### 2. Component Name Mismatch
Livewire expects kebab-case component names. Verify:
- Class: `App\Livewire\FirearmSearchPanel`
- Component name: `firearm-search-panel`
- View file: `resources/views/livewire/firearm-search-panel.blade.php`

### 3. Missing Livewire Routes
Livewire needs routes to be registered. Check:
```bash
php artisan route:list | grep livewire
```

Should show `/livewire/update` and `/livewire/message/{name}` routes.

### 4. Cache Issues
Clear all caches:
```bash
php artisan optimize:clear
php artisan view:clear
php artisan config:clear
php artisan route:clear
```

### 5. Component Registration
Check if component is discovered:
```bash
php artisan livewire:list
```

Should show `firearm-search-panel` in the list.

## Temporary Workaround

Comment out the component temporarily to verify the page loads:

```blade
{{-- Temporarily disabled for debugging --}}
{{-- 
<livewire:firearm-search-panel 
    wire:key="endorsement-firearm-panel-{{ $editingRequest?->id ?? 'new' }}"
    :initial-data="$firearmPanelData"
/>
--}}
<div class="p-4 bg-blue-50 border border-blue-200 rounded-lg">
    <p>FirearmSearchPanel component temporarily disabled for debugging</p>
</div>
```

If the page loads without the component, the issue is with the component itself.

## Alternative: Use Blade Component Syntax

Try using the Blade component syntax instead:

```blade
<x-livewire:firearm-search-panel 
    wire:key="endorsement-firearm-panel-{{ $editingRequest?->id ?? 'new' }}"
    :initial-data="$firearmPanelData"
/>
```

## Check Laravel Logs

```bash
tail -f storage/logs/laravel.log
```

Look for:
- Component not found errors
- View not found errors
- Route not found errors
