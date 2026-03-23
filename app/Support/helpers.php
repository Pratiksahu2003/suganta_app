<?php

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

/**
 * Get the full URL for a file in storage.
 * For GCS with private bucket: returns a signed (temporary) URL.
 * For other disks: returns standard public URL.
 *
 * @param  string  $path  The file path
 * @param  string|null  $disk  The storage disk (default: from config)
 * @return string The full URL
 */
function storage_file_url(?string $path = null, ?string $disk = null): string
{

    if (is_null($path)) {
        return Cache::rememberForever('placeholder_no_image', function () {
            return asset('img/no.png');
        });
    }
    $disk = $disk ?? config('filesystems.upload_disk', 'public');

    if ($disk === 'gcs') {
        $minutes = (int) config('filesystems.gcs_signed_url_expiry_minutes', 10080);
        $expiry = now()->addMinutes($minutes);
        $expirySeconds = max(60, ((int) $minutes) * 60);
        $fallbackCacheSeconds = max(60, $expirySeconds - 60);
        $configuredCacheSeconds = (int) config('filesystems.gcs_signed_url_cache_seconds', 0);
        $cacheSeconds = $configuredCacheSeconds > 0
            ? min(max(60, $configuredCacheSeconds), $fallbackCacheSeconds)
            : $fallbackCacheSeconds;
        $cacheKey = 'storage_file_url:gcs:' . sha1($path);

        return Cache::remember($cacheKey, $cacheSeconds, static function () use ($path, $expiry): string {
            return Storage::disk('gcs')->temporaryUrl($path, $expiry);
        });
    }

    return Storage::disk($disk)->url($path);
}

/**
 * Mask a phone number for display (e.g. chat headers, search) — shows only a short tail.
 */
function mask_phone_for_display(?string $phone): ?string
{
    if ($phone === null || trim($phone) === '') {
        return null;
    }

    $trimmed = trim($phone);
    $digits = preg_replace('/\D+/', '', $trimmed) ?? '';

    if ($digits === '') {
        return '••••••••';
    }

    $len = strlen($digits);
    $tailLen = $len >= 10 ? 4 : ($len > 5 ? 2 : 0);

    if ($tailLen === 0) {
        return str_repeat('•', min(8, max(4, $len)));
    }

    $suffix = substr($digits, -$tailLen);
    $maskLen = min(8, max(3, $len - $tailLen));
    $mask = str_repeat('•', $maskLen);

    if (str_starts_with($trimmed, '+') && preg_match('/^\+\d{1,3}/', $trimmed, $m)) {
        return $m[0] . ' ' . $mask . $suffix;
    }

    return $mask . $suffix;
}
