<?php

use Illuminate\Support\Facades\Storage;

/**
 * Get the full URL for a file in storage.
 * For GCS with private bucket: returns a signed (temporary) URL.
 * For other disks: returns standard public URL.
 *
 * @param string $path The file path
 * @param string|null $disk The storage disk (default: from config)
 * @return string The full URL
 */
function storage_file_url(string $path = null, ?string $disk = null): string
{
    if(is_null($path))  return  asset('img/no.jpg') ;
    $disk = $disk ?? config('filesystems.upload_disk', 'public');

    if ($disk === 'gcs') {
        $minutes = (int) config('filesystems.gcs_signed_url_expiry_minutes', 10080);
        $expiry = now()->addMinutes($minutes);
        return Storage::disk('gcs')->temporaryUrl($path, $expiry);
    }

    return Storage::disk($disk)->url($path);
}
