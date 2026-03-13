<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StorageController extends Controller
{
    /**
     * Serve files from storage (GCS, S3, local) through the app domain.
     * Enables https://yoursite.com/storage/profile-images/xxx.jpg style URLs.
     */
    public function serve(string $path): StreamedResponse
    {
        $disk = config('filesystems.upload_disk', 'public');

        if (! Storage::disk($disk)->exists($path)) {
            abort(404, 'File not found');
        }

        try {
            $storage = Storage::disk($disk);
            $mimeType = $storage->mimeType($path) ?? $this->guessMimeType($path);

            $headers = [
                'Content-Type'        => $mimeType,
                'Cache-Control'       => 'public, max-age=86400',
                'Content-Disposition' => 'inline; filename="' . basename($path) . '"',
            ];

            $size = $storage->size($path);
            if (false !== $size) {
                $headers['Content-Length'] = (string) $size;
            }

            return new StreamedResponse(function () use ($storage, $path): void {
                $stream = $storage->readStream($path);
                if ($stream) {
                    fpassthru($stream);
                    fclose($stream);
                }
            }, 200, $headers);
        } catch (\Throwable $e) {
            Log::error('Storage serve error', [
                'path'  => $path,
                'disk'  => $disk,
                'error' => $e->getMessage(),
            ]);
            abort(500, 'Failed to serve file');
        }
    }

    protected function guessMimeType(string $path): string
    {
        return match (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png'         => 'image/png',
            'gif'         => 'image/gif',
            'webp'        => 'image/webp',
            'svg'         => 'image/svg+xml',
            'pdf'         => 'application/pdf',
            default      => 'application/octet-stream',
        };
    }
}
