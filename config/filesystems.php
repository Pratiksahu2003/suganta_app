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
    | Upload Disk (Portfolios, Support Tickets, etc.)
    |--------------------------------------------------------------------------
    |
    | Change this to switch storage backend. No code changes needed.
    | - 'public' = Local (storage/app/public)
    | - 's3' = AWS S3
    | - 'gcs' = Google Cloud Storage
    |
    */

    'upload_disk' => env('FILESYSTEM_UPLOAD_DISK', 'public'),

    /*
    |--------------------------------------------------------------------------
    | GCS Signed URL Expiry (minutes)
    |--------------------------------------------------------------------------
    |
kets, signed URLs are used. This sets    | When using GCS with non-public buc
    | how long each signed URL remains valid. Default: 10080 (7 days).
    |
    */

    'gcs_signed_url_expiry_minutes' => env('GCP_SIGNED_URL_EXPIRY_MINUTES', 10080),

    /*
    |--------------------------------------------------------------------------
    | GCS Signed URL Cache TTL (seconds)
    |--------------------------------------------------------------------------
    |
    | Optional override for caching generated GCS signed URLs. Leave null/0
    | to auto-calculate from expiry minutes (expiry - 60 seconds).
    |
    */

    'gcs_signed_url_cache_seconds' => env('GCP_SIGNED_URL_CACHE_SECONDS', 0),

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
            'url' => rtrim(env('APP_URL', 'http://localhost'), '/').'/storage',
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

        'gcs' => [
            'driver' => 'gcs',
            'key_file_path' => env('GCP_KEY_FILE')
                ? (preg_match('#^([A-Za-z]:|/)#', trim(env('GCP_KEY_FILE')))
                    ? env('GCP_KEY_FILE')
                    : base_path(env('GCP_KEY_FILE')))
                : storage_path('keys/sugnata-tutors-2846153fe5ca.json'),
            'project_id' => env('GCP_PROJECT_ID', env('GOOGLE_CLOUD_PROJECT_ID')),
            'bucket' => env('GCP_BUCKET', env('GOOGLE_CLOUD_STORAGE_BUCKET')),
            'path_prefix' => env('GCP_PATH_PREFIX', env('GOOGLE_CLOUD_STORAGE_PATH_PREFIX', '')),
            'storage_api_uri' => env('GCP_STORAGE_API_URI', env('GOOGLE_CLOUD_STORAGE_API_URI')),
            'api_endpoint' => env('GCP_API_ENDPOINT', env('GOOGLE_CLOUD_STORAGE_API_ENDPOINT')),
            'visibility' => 'public',
            'visibility_handler' => filter_var(env('GCP_UNIFORM_BUCKET_ACCESS'), FILTER_VALIDATE_BOOLEAN)
                ? \League\Flysystem\GoogleCloudStorage\UniformBucketLevelAccessVisibility::class
                : null,
            'metadata' => ['cacheControl' => 'public,max-age=86400'],
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
