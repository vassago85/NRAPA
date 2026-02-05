# Troubleshooting 404 Error for FirearmSearchPanel

## Quick Fixes

### 1. Clear All Caches
```bash
cd c:\laragon\www\NRAPA
php artisan cache:clear
php artisan config:clear
php artisan view:clear
php artisan route:clear
php artisan optimize:clear
```

### 2. Check Component Registration

The component should be auto-discovered. Verify:
- File exists: `app/Livewire/FirearmSearchPanel.php`
- View exists: `resources/views/livewire/firearm-search-panel.blade.php`
- Namespace is correct: `App\Livewire\FirearmSearchPanel`

### 3. Check Browser Console

Open browser DevTools (F12) and check:
- Network tab for failed requests
- Console tab for JavaScript errors
- Look for Livewire-related errors

### 4. Verify Livewire Routes

Livewire should auto-register routes. Check if Livewire is working:
- Visit any page with a Livewire component
- Check if `/livewire/update` endpoint exists

### 5. Check Laravel Logs

```bash
tail -f storage/logs/laravel.log
```

Look for:
- Component not found errors
- View not found errors
- Route not found errors

## Common Issues

### Issue: Component Not Found
**Solution**: Ensure the component class extends `Livewire\Component` and is in the `App\Livewire` namespace.

### Issue: View Not Found
**Solution**: The view file must be at `resources/views/livewire/firearm-search-panel.blade.php` (kebab-case).

### Issue: Initial Data Error
**Solution**: The `$firearmPanelData` must be initialized. It's now set to `[]` for new requests.

### Issue: Livewire Not Loading
**Solution**: 
1. Check `composer.json` has `livewire/livewire`
2. Run `composer dump-autoload`
3. Clear all caches

## Test Component Directly

Create a test route to verify the component works:

```php
// In routes/web.php (temporary)
Route::get('/test-firearm-panel', function () {
    return view('test-firearm-panel');
});
```

Create `resources/views/test-firearm-panel.blade.php`:
```blade
<div>
    <h1>Test FirearmSearchPanel</h1>
    <livewire:firearm-search-panel />
</div>
```

Visit `/test-firearm-panel` to see if the component loads.

## Still Getting 404?

1. Check the exact URL that's returning 404
2. Check if it's a Livewire update request (`/livewire/update`)
3. Verify the component name matches: `firearm-search-panel` (kebab-case)
4. Try using the full namespace: `<livewire:App\Livewire\FirearmSearchPanel />`
