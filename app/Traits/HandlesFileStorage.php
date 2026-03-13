<?php

namespace App\Traits;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Exception;

trait HandlesFileStorage
{
    /**
     * Get the configured upload disk (public, s3, gcs, etc.).
     * Change FILESYSTEM_UPLOAD_DISK in .env to switch storage - no code changes needed.
     */
    protected function getUploadDisk(): string
    {
        return config('filesystems.upload_disk', 'public');
    }

    /**
     * Upload a file to storage.
     *
     * @param UploadedFile $file The uploaded file
     * @param int $userId The user ID uploading the file
     * @param string $type The type of file (e.g., 'image', 'file', 'ticket', 'reply')
     * @param string $module The module name (e.g., 'portfolio', 'support-ticket')
     * @return string The stored file path
     */
    protected function uploadFile(
        UploadedFile $file,
        int $userId,
        string $type = 'file',
        string $module = 'general'
    ): string {
        $timestamp = now()->format('YmdHis');
        $randomString = bin2hex(random_bytes(8));
        $extension = $file->getClientOriginalExtension();
        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $sanitizedName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $originalName);
        
        $filename = "{$module}_{$type}_{$userId}_{$timestamp}_{$randomString}_{$sanitizedName}.{$extension}";
        
        $directory = $this->getStorageDirectory($module, $type);
        $path = $file->storeAs($directory, $filename, $this->getUploadDisk());
        
        Log::info('File uploaded', [
            'module' => $module,
            'type' => $type,
            'user_id' => $userId,
            'filename' => $filename,
            'path' => $path,
            'size' => $file->getSize(),
            'mime_type' => $file->getMimeType()
        ]);
        
        return $path;
    }

    /**
     * Upload multiple files to storage.
     *
     * @param array $files Array of UploadedFile objects
     * @param int $userId The user ID uploading the files
     * @param string $type The type of files
     * @param string $module The module name
     * @return array Array of stored file paths
     */
    protected function uploadMultipleFiles(
        array $files,
        int $userId,
        string $type = 'file',
        string $module = 'general'
    ): array {
        $paths = [];
        
        foreach ($files as $file) {
            if ($file instanceof UploadedFile) {
                $paths[] = $this->uploadFile($file, $userId, $type, $module);
            }
        }
        
        return $paths;
    }

    /**
     * Delete a file from storage.
     *
     * @param string $path The file path to delete
     * @param string|null $disk The storage disk (default: from config)
     * @return bool Whether the deletion was successful
     */
    protected function deleteFile(string $path, ?string $disk = null): bool
    {
        $disk = $disk ?? $this->getUploadDisk();
        try {
            if (Storage::disk($disk)->exists($path)) {
                Storage::disk($disk)->delete($path);
                
                Log::info('File deleted', [
                    'path' => $path,
                    'disk' => $disk
                ]);
                
                return true;
            }
            
            Log::warning('File not found for deletion', [
                'path' => $path,
                'disk' => $disk
            ]);
            
            return false;
        } catch (Exception $e) {
            Log::error('Failed to delete file', [
                'path' => $path,
                'disk' => $disk,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }

    /**
     * Delete multiple files from storage.
     *
     * @param array $paths Array of file paths to delete
     * @param string|null $disk The storage disk (default: from config)
     * @return array Array with 'deleted' and 'failed' counts
     */
    protected function deleteMultipleFiles(array $paths, ?string $disk = null): array
    {
        $disk = $disk ?? $this->getUploadDisk();
        $deleted = 0;
        $failed = 0;
        
        foreach ($paths as $path) {
            if ($this->deleteFile($path, $disk)) {
                $deleted++;
            } else {
                $failed++;
            }
        }
        
        return [
            'deleted' => $deleted,
            'failed' => $failed,
            'total' => count($paths)
        ];
    }

    /**
     * Get the full URL for a file.
     *
     * For GCS and other cloud disks, returns the project-domain URL so files are
     * served through the app (e.g. https://yoursite.com/storage/profile-images/xxx.jpg).
     * For local 'public' disk, returns the standard public URL.
     *
     * @param string $path The file path
     * @param string|null $disk The storage disk (default: from config)
     * @return string The full URL (project domain for cloud disks)
     */
    protected function getFileUrl(string $path, ?string $disk = null): string
    {
        $diskName = $disk ?? $this->getUploadDisk();

        // Cloud disks (gcs, s3): serve through app domain for consistent URLs
        if (in_array($diskName, ['gcs', 's3'])) {
            $base = rtrim(config('app.url'), '/');
            return $base . '/storage/' . ltrim($path, '/');
        }

        return Storage::disk($diskName)->url($path);
    }

    /**
     * Check if a file exists in storage.
     *
     * @param string $path The file path
     * @param string|null $disk The storage disk (default: from config)
     * @return bool Whether the file exists
     */
    protected function fileExists(string $path, ?string $disk = null): bool
    {
        return Storage::disk($disk ?? $this->getUploadDisk())->exists($path);
    }

    /**
     * Get file size in bytes.
     *
     * @param string $path The file path
     * @param string|null $disk The storage disk (default: from config)
     * @return int|false File size in bytes or false if file doesn't exist
     */
    protected function getFileSize(string $path, ?string $disk = null): int|false
    {
        $disk = $disk ?? $this->getUploadDisk();
        try {
            if ($this->fileExists($path, $disk)) {
                return Storage::disk($disk)->size($path);
            }
            return false;
        } catch (Exception $e) {
            Log::error('Failed to get file size', [
                'path' => $path,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get the storage directory based on module and type.
     *
     * @param string $module The module name
     * @param string $type The file type
     * @return string The directory path
     */
    protected function getStorageDirectory(string $module, string $type): string
    {
        return match ($module) {
            'portfolio' => $type === 'image' ? 'portfolios/images' : 'portfolios',
            'support-ticket' => 'support-tickets',
            'profile' => $type === 'avatar' ? 'profile-images' : 'profile',
            default => $module,
        };
    }

    /**
     * Move a file to a new location.
     *
     * @param string $oldPath The current file path
     * @param string $newPath The new file path
     * @param string|null $disk The storage disk (default: from config)
     * @return bool Whether the move was successful
     */
    protected function moveFile(string $oldPath, string $newPath, ?string $disk = null): bool
    {
        $disk = $disk ?? $this->getUploadDisk();
        try {
            if (!Storage::disk($disk)->exists($oldPath)) {
                Log::warning('Cannot move file - source does not exist', [
                    'old_path' => $oldPath,
                    'new_path' => $newPath
                ]);
                return false;
            }
            
            $result = Storage::disk($disk)->move($oldPath, $newPath);
            
            if ($result) {
                Log::info('File moved', [
                    'old_path' => $oldPath,
                    'new_path' => $newPath,
                    'disk' => $disk
                ]);
            }
            
            return $result;
        } catch (Exception $e) {
            Log::error('Failed to move file', [
                'old_path' => $oldPath,
                'new_path' => $newPath,
                'disk' => $disk,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }

    /**
     * Copy a file to a new location.
     *
     * @param string $sourcePath The source file path
     * @param string $destinationPath The destination file path
     * @param string|null $disk The storage disk (default: from config)
     * @return bool Whether the copy was successful
     */
    protected function copyFile(string $sourcePath, string $destinationPath, ?string $disk = null): bool
    {
        $disk = $disk ?? $this->getUploadDisk();
        try {
            if (!Storage::disk($disk)->exists($sourcePath)) {
                Log::warning('Cannot copy file - source does not exist', [
                    'source_path' => $sourcePath,
                    'destination_path' => $destinationPath
                ]);
                return false;
            }
            
            $result = Storage::disk($disk)->copy($sourcePath, $destinationPath);
            
            if ($result) {
                Log::info('File copied', [
                    'source_path' => $sourcePath,
                    'destination_path' => $destinationPath,
                    'disk' => $disk
                ]);
            }
            
            return $result;
        } catch (Exception $e) {
            Log::error('Failed to copy file', [
                'source_path' => $sourcePath,
                'destination_path' => $destinationPath,
                'disk' => $disk,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }

    /**
     * Get file metadata.
     *
     * @param string $path The file path
     * @param string|null $disk The storage disk (default: from config)
     * @return array|null File metadata or null if file doesn't exist
     */
    protected function getFileMetadata(string $path, ?string $disk = null): ?array
    {
        $disk = $disk ?? $this->getUploadDisk();
        try {
            if (!$this->fileExists($path, $disk)) {
                return null;
            }
            
            return [
                'path' => $path,
                'url' => $this->getFileUrl($path, $disk),
                'name' => basename($path),
                'size' => $this->getFileSize($path, $disk),
                'mime_type' => Storage::disk($disk)->mimeType($path),
                'last_modified' => Storage::disk($disk)->lastModified($path),
            ];
        } catch (Exception $e) {
            Log::error('Failed to get file metadata', [
                'path' => $path,
                'error' => $e->getMessage()
            ]);
            
            return null;
        }
    }

    /**
     * Format file paths to include URLs.
     *
     * @param array|null $paths Array of file paths
     * @param string|null $disk The storage disk (default: from config)
     * @return array Array of formatted file data
     */
    protected function formatFilePaths(?array $paths, ?string $disk = null): array
    {
        if (!$paths) {
            return [];
        }

        $disk = $disk ?? $this->getUploadDisk();
        return array_map(function ($path) use ($disk) {
            return [
                'path' => $path,
                'url' => $this->getFileUrl($path, $disk),
                'name' => basename($path),
            ];
        }, $paths);
    }

    /**
     * Clean up orphaned files (files in storage but not in database).
     * This should be called periodically via a scheduled command.
     *
     * @param string $directory The directory to clean
     * @param array $validPaths Array of valid file paths from database
     * @param string|null $disk The storage disk (default: from config)
     * @return array Cleanup results
     */
    protected function cleanupOrphanedFiles(
        string $directory,
        array $validPaths,
        ?string $disk = null
    ): array {
        $disk = $disk ?? $this->getUploadDisk();
        try {
            $allFiles = Storage::disk($disk)->files($directory);
            $orphaned = array_diff($allFiles, $validPaths);
            $deleted = 0;
            
            foreach ($orphaned as $file) {
                if ($this->deleteFile($file, $disk)) {
                    $deleted++;
                }
            }
            
            Log::info('Orphaned files cleanup completed', [
                'directory' => $directory,
                'total_files' => count($allFiles),
                'valid_files' => count($validPaths),
                'orphaned_files' => count($orphaned),
                'deleted' => $deleted
            ]);
            
            return [
                'total_files' => count($allFiles),
                'valid_files' => count($validPaths),
                'orphaned_files' => count($orphaned),
                'deleted' => $deleted,
            ];
        } catch (Exception $e) {
            Log::error('Failed to cleanup orphaned files', [
                'directory' => $directory,
                'error' => $e->getMessage()
            ]);
            
            return [
                'error' => $e->getMessage()
            ];
        }
    }
}
