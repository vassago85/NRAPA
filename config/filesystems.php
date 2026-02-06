<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. The "local" disk, as well as a variety of cloud
    | based disks are available to your application for file storage.
    |
    */

    'default' => env('FILESYSTEM_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Below you may configure as many filesystem disks as necessary, and you
    | may even configure multiple disks for the same driver. Examples for
    | most supported storage drivers are configured here for reference.
    |
    | Supported drivers: "local", "ftp", "sftp", "s3"
    |
    */

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app/private'),
            'serve' => true,
            'throw' => false,
            'report' => false,
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => rtrim(env('APP_URL'), '/').'/storage',
            'visibility' => 'public',
            'throw' => false,
            'report' => false,
        ],

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'throw' => false,
            'report' => false,
        ],

        /*
        |--------------------------------------------------------------------------
        | Cloudflare R2 Storage (Private - for sensitive documents)
        |--------------------------------------------------------------------------
        |
        | Cloudflare R2 is S3-compatible object storage. This bucket is for
        | sensitive member documents (ID copies, certificates, etc.).
        | Access is via signed URLs only.
        |
        */
        'r2' => [
            'driver' => 's3',
            'key' => env('CLOUDFLARE_R2_ACCESS_KEY_ID'),
            'secret' => env('CLOUDFLARE_R2_SECRET_ACCESS_KEY'),
            'region' => env('CLOUDFLARE_R2_REGION', 'auto'),
            'bucket' => env('CLOUDFLARE_R2_BUCKET'),
            'url' => env('CLOUDFLARE_R2_URL'),
            'endpoint' => env('CLOUDFLARE_R2_ENDPOINT'),
            'use_path_style_endpoint' => env('CLOUDFLARE_R2_USE_PATH_STYLE_ENDPOINT', true),
            'throw' => false,
            'report' => false,
        ],

        /*
        |--------------------------------------------------------------------------
        | Cloudflare R2 Backup Storage (separate bucket for offsite backups)
        |--------------------------------------------------------------------------
        |
        | Dedicated R2 bucket for database and system backups.
        | Uses same API credentials as the main R2 disk but a different bucket.
        | Can be overridden at runtime via SystemSetting (backup_r2_bucket).
        |
        */
        'r2_backup' => [
            'driver' => 's3',
            'key' => env('CLOUDFLARE_R2_ACCESS_KEY_ID'),
            'secret' => env('CLOUDFLARE_R2_SECRET_ACCESS_KEY'),
            'region' => env('CLOUDFLARE_R2_REGION', 'auto'),
            'bucket' => env('CLOUDFLARE_R2_BACKUP_BUCKET'),
            'endpoint' => env('CLOUDFLARE_R2_ENDPOINT'),
            'use_path_style_endpoint' => env('CLOUDFLARE_R2_USE_PATH_STYLE_ENDPOINT', true),
            'throw' => false,
            'report' => false,
        ],

        /*
        |--------------------------------------------------------------------------
        | Cloudflare R2 Public Storage (for learning images, etc.)
        |--------------------------------------------------------------------------
        |
        | A separate R2 bucket with public access enabled for non-sensitive
        | content like learning article images. Uses R2.dev public URL.
        |
        */
        'r2_public' => [
            'driver' => 's3',
            'key' => env('CLOUDFLARE_R2_ACCESS_KEY_ID'),
            'secret' => env('CLOUDFLARE_R2_SECRET_ACCESS_KEY'),
            'region' => env('CLOUDFLARE_R2_REGION', 'auto'),
            'bucket' => env('CLOUDFLARE_R2_PUBLIC_BUCKET'),
            'url' => env('CLOUDFLARE_R2_PUBLIC_URL'),
            'endpoint' => env('CLOUDFLARE_R2_ENDPOINT'),
            'use_path_style_endpoint' => env('CLOUDFLARE_R2_USE_PATH_STYLE_ENDPOINT', false),
            'throw' => false,
            'report' => false,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Symbolic Links
    |--------------------------------------------------------------------------
    |
    | Here you may configure the symbolic links that will be created when the
    | `storage:link` Artisan command is executed. The array keys should be
    | the locations of the links and the values should be their targets.
    |
    */

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

];
