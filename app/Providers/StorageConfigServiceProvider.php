<?php

namespace App\Providers;

use App\Models\SystemSetting;
use Illuminate\Support\ServiceProvider;

class StorageConfigServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Always load storage config from database
        $this->loadStorageConfigFromDatabase();
    }

    /**
     * Load storage configuration from database.
     */
    protected function loadStorageConfigFromDatabase(): void
    {
        try {
            // Check if the table exists
            if (!\Schema::hasTable('system_settings')) {
                return;
            }

            $storageDriver = SystemSetting::get('storage_driver');

            // Only override if we have settings in database and R2 is selected
            if ($storageDriver === 'r2') {
                $r2AccessKeyId = SystemSetting::get('r2_access_key_id');
                
                if ($r2AccessKeyId) {
                    // Private bucket (for sensitive documents)
                    config([
                        'filesystems.default' => 'r2',
                        'filesystems.disks.r2.key' => $r2AccessKeyId,
                        'filesystems.disks.r2.secret' => SystemSetting::get('r2_secret_access_key'),
                        'filesystems.disks.r2.bucket' => SystemSetting::get('r2_bucket'),
                        'filesystems.disks.r2.endpoint' => SystemSetting::get('r2_endpoint'),
                        'filesystems.disks.r2.url' => SystemSetting::get('r2_url'),
                        'filesystems.disks.r2.region' => SystemSetting::get('r2_region', 'auto'),
                    ]);
                    
                    // Public bucket (for learning images, etc.)
                    $publicBucket = SystemSetting::get('r2_public_bucket');
                    $publicUrl = SystemSetting::get('r2_public_url');
                    
                    if ($publicBucket) {
                        config([
                            'filesystems.disks.r2_public.key' => $r2AccessKeyId,
                            'filesystems.disks.r2_public.secret' => SystemSetting::get('r2_secret_access_key'),
                            'filesystems.disks.r2_public.bucket' => $publicBucket,
                            'filesystems.disks.r2_public.endpoint' => SystemSetting::get('r2_endpoint'),
                            'filesystems.disks.r2_public.url' => $publicUrl,
                            'filesystems.disks.r2_public.region' => SystemSetting::get('r2_region', 'auto'),
                        ]);
                    }
                }
            }
        } catch (\Exception $e) {
            // Silently fail - database might not be available yet
        }
    }
}
