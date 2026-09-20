<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Exception;

class FileManagementService
{
    /**
     * Allowed file types for different categories
     */
    private const ALLOWED_TYPES = [
        'images' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
        'documents' => ['pdf', 'doc', 'docx', 'txt'],
        'certificates' => ['pdf', 'jpg', 'jpeg', 'png'],
        'photos' => ['jpg', 'jpeg', 'png'],
        'all' => ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf', 'doc', 'docx', 'txt']
    ];

    /**
     * Maximum file sizes in bytes
     */
    private const MAX_SIZES = [
        'images' => 2 * 1024 * 1024, // 2MB
        'documents' => 5 * 1024 * 1024, // 5MB
        'certificates' => 5 * 1024 * 1024, // 5MB
        'photos' => 2 * 1024 * 1024, // 2MB
        'all' => 10 * 1024 * 1024 // 10MB
    ];

    /**
     * Storage disk to use
     */
    private const STORAGE_DISK = 'public';

    /**
     * Upload a single file
     *
     * @param UploadedFile $file
     * @param string $category
     * @param string $directory
     * @return array
     * @throws Exception
     */
    public function uploadFile(UploadedFile $file, string $category = 'all', string $directory = 'uploads'): array
    {
        try {
            // Validate file
            $validation = $this->validateFile($file, $category);
            if (!$validation['valid']) {
                throw new Exception($validation['message']);
            }

            // Generate unique filename
            $filename = $this->generateUniqueFilename($file);
            
            // Create directory path
            $path = $this->createDirectoryPath($directory);
            
            // Store file
            $fullPath = $file->storeAs($path, $filename, self::STORAGE_DISK);
            
            // Get file URL
            $url = Storage::disk(self::STORAGE_DISK)->url($fullPath);
            
            Log::info('File uploaded successfully', [
                'original_name' => $file->getClientOriginalName(),
                'filename' => $filename,
                'path' => $fullPath,
                'size' => $file->getSize(),
                'category' => $category,
                'directory' => $directory
            ]);

            return [
                'success' => true,
                'filename' => $filename,
                'original_name' => $file->getClientOriginalName(),
                'path' => $fullPath,
                'url' => $url,
                'size' => $file->getSize(),
                'mime_type' => $file->getMimeType()
            ];

        } catch (Exception $e) {
            Log::error('File upload failed', [
                'original_name' => $file->getClientOriginalName(),
                'error' => $e->getMessage(),
                'category' => $category,
                'directory' => $directory
            ]);

            throw $e;
        }
    }

    /**
     * Upload multiple files
     *
     * @param array $files
     * @param string $category
     * @param string $directory
     * @return array
     */
    public function uploadMultipleFiles(array $files, string $category = 'all', string $directory = 'uploads'): array
    {
        $results = [];
        $errors = [];

        foreach ($files as $index => $file) {
            try {
                $result = $this->uploadFile($file, $category, $directory);
                $results[] = $result;
            } catch (Exception $e) {
                $errors[] = [
                    'index' => $index,
                    'original_name' => $file->getClientOriginalName(),
                    'error' => $e->getMessage()
                ];
            }
        }

        return [
            'success' => empty($errors),
            'files' => $results,
            'errors' => $errors
        ];
    }

    /**
     * Validate file
     *
     * @param UploadedFile $file
     * @param string $category
     * @return array
     */
    public function validateFile(UploadedFile $file, string $category = 'all'): array
    {
        // Check if file is valid
        if (!$file->isValid()) {
            return [
                'valid' => false,
                'message' => 'Invalid file upload.'
            ];
        }

        // Check file size
        $maxSize = self::MAX_SIZES[$category] ?? self::MAX_SIZES['all'];
        if ($file->getSize() > $maxSize) {
            $maxSizeMB = $maxSize / (1024 * 1024);
            return [
                'valid' => false,
                'message' => "File size must not exceed {$maxSizeMB}MB."
            ];
        }

        // Check file extension
        $extension = strtolower($file->getClientOriginalExtension());
        $allowedExtensions = self::ALLOWED_TYPES[$category] ?? self::ALLOWED_TYPES['all'];
        
        if (!in_array($extension, $allowedExtensions)) {
            $allowedTypes = implode(', ', $allowedExtensions);
            return [
                'valid' => false,
                'message' => "File type not allowed. Allowed types: {$allowedTypes}"
            ];
        }

        // Check MIME type for security
        $mimeType = $file->getMimeType();
        $allowedMimeTypes = $this->getAllowedMimeTypes($category);
        
        if (!in_array($mimeType, $allowedMimeTypes)) {
            return [
                'valid' => false,
                'message' => 'File type not allowed for security reasons.'
            ];
        }

        return [
            'valid' => true,
            'message' => 'File is valid.'
        ];
    }

    /**
     * Delete file
     *
     * @param string $filename
     * @param string $directory
     * @return bool
     */
    public function deleteFile(string $filename, string $directory = 'uploads'): bool
    {
        try {
            $path = $directory . '/' . $filename;
            
            if (Storage::disk(self::STORAGE_DISK)->exists($path)) {
                Storage::disk(self::STORAGE_DISK)->delete($path);
                
                Log::info('File deleted successfully', [
                    'filename' => $filename,
                    'path' => $path
                ]);
                
                return true;
            }

            Log::warning('File not found for deletion', [
                'filename' => $filename,
                'path' => $path
            ]);

            return false;

        } catch (Exception $e) {
            Log::error('File deletion failed', [
                'filename' => $filename,
                'path' => $path ?? null,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Resolve a stored public-disk path to a URL only when the file exists.
     *
     * @param  array<int, string>  $searchDirectories
     * @return array{exists: bool, url: ?string, path: ?string}
     */
    public function resolvePublicFile(?string $path, array $searchDirectories = []): array
    {
        $empty = ['exists' => false, 'url' => null, 'path' => null];
        $path = trim((string) $path);
        if ($path === '' || in_array(strtolower($path), ['null', 'n/a', 'na', '-'], true)) {
            return $empty;
        }

        if (preg_match('#^https?://#i', $path)) {
            return ['exists' => true, 'url' => $path, 'path' => $path];
        }

        $normalized = ltrim((string) preg_replace('#^(public/|storage/)#', '', $path), '/');
        $basename = basename($normalized);
        $candidates = [$normalized];

        foreach ($searchDirectories as $directory) {
            $directory = trim((string) $directory, '/');
            if ($directory === '') {
                continue;
            }
            $candidates[] = $directory . '/' . $basename;
        }

        foreach (array_values(array_unique(array_filter($candidates))) as $candidate) {
            if (Storage::disk(self::STORAGE_DISK)->exists($candidate)) {
                return [
                    'exists' => true,
                    'url' => Storage::disk(self::STORAGE_DISK)->url($candidate),
                    'path' => $candidate,
                ];
            }
        }

        return ['exists' => false, 'url' => null, 'path' => $normalized];
    }

    /**
     * Delete multiple files
     *
     * @param array $filenames
     * @param string $directory
     * @return array
     */
    public function deleteMultipleFiles(array $filenames, string $directory = 'uploads'): array
    {
        $results = [];
        $successCount = 0;
        $failureCount = 0;

        foreach ($filenames as $filename) {
            $deleted = $this->deleteFile($filename, $directory);
            $results[] = [
                'filename' => $filename,
                'deleted' => $deleted
            ];

            if ($deleted) {
                $successCount++;
            } else {
                $failureCount++;
            }
        }

        return [
            'success' => $failureCount === 0,
            'total' => count($filenames),
            'deleted' => $successCount,
            'failed' => $failureCount,
            'results' => $results
        ];
    }

    /**
     * Get file information
     *
     * @param string $filename
     * @param string $directory
     * @return array|null
     */
    public function getFileInfo(string $filename, string $directory = 'uploads'): ?array
    {
        try {
            $path = $directory . '/' . $filename;
            
            if (!Storage::disk(self::STORAGE_DISK)->exists($path)) {
                return null;
            }

            $url = Storage::disk(self::STORAGE_DISK)->url($path);
            $size = Storage::disk(self::STORAGE_DISK)->size($path);
            $lastModified = Storage::disk(self::STORAGE_DISK)->lastModified($path);

            return [
                'filename' => $filename,
                'path' => $path,
                'url' => $url,
                'size' => $size,
                'size_formatted' => $this->formatFileSize($size),
                'last_modified' => $lastModified,
                'last_modified_formatted' => date('Y-m-d H:i:s', $lastModified)
            ];

        } catch (Exception $e) {
            Log::error('Error getting file info', [
                'filename' => $filename,
                'error' => $e->getMessage()
            ]);

            return null;
        }
    }

    /**
     * Clean up orphaned files
     *
     * @param string $directory
     * @param int $daysOld
     * @return array
     */
    public function cleanupOrphanedFiles(string $directory = 'uploads', int $daysOld = 30): array
    {
        try {
            $files = Storage::disk(self::STORAGE_DISK)->files($directory);
            $deletedFiles = [];
            $cutoffTime = time() - ($daysOld * 24 * 60 * 60);

            foreach ($files as $file) {
                $lastModified = Storage::disk(self::STORAGE_DISK)->lastModified($file);
                
                if ($lastModified < $cutoffTime) {
                    if ($this->deleteFile(basename($file), $directory)) {
                        $deletedFiles[] = $file;
                    }
                }
            }

            Log::info('Cleanup completed', [
                'directory' => $directory,
                'days_old' => $daysOld,
                'deleted_count' => count($deletedFiles)
            ]);

            return [
                'success' => true,
                'deleted_files' => $deletedFiles,
                'deleted_count' => count($deletedFiles)
            ];

        } catch (Exception $e) {
            Log::error('Cleanup failed', [
                'directory' => $directory,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Generate unique filename
     *
     * @param UploadedFile $file
     * @return string
     */
    private function generateUniqueFilename(UploadedFile $file): string
    {
        $extension = $file->getClientOriginalExtension();
        $timestamp = time();
        $random = Str::random(10);
        
        return "{$timestamp}_{$random}.{$extension}";
    }

    /**
     * Create directory path
     *
     * @param string $directory
     * @return string
     */
    private function createDirectoryPath(string $directory): string
    {
        $path = trim($directory, '/');
        
        // Ensure directory exists
        if (!Storage::disk(self::STORAGE_DISK)->exists($path)) {
            Storage::disk(self::STORAGE_DISK)->makeDirectory($path);
        }
        
        return $path;
    }

    /**
     * Get allowed MIME types for category
     *
     * @param string $category
     * @return array
     */
    private function getAllowedMimeTypes(string $category): array
    {
        $mimeTypes = [
            'images' => ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],
            'documents' => ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'text/plain'],
            'certificates' => ['application/pdf', 'image/jpeg', 'image/png'],
            'photos' => ['image/jpeg', 'image/png'],
            'all' => ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'text/plain']
        ];

        return $mimeTypes[$category] ?? $mimeTypes['all'];
    }

    /**
     * Format file size
     *
     * @param int $bytes
     * @return string
     */
    private function formatFileSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= pow(1024, $pow);

        return round($bytes, 2) . ' ' . $units[$pow];
    }

    /**
     * Get storage statistics
     *
     * @param string $directory
     * @return array
     */
    public function getStorageStats(string $directory = 'uploads'): array
    {
        try {
            $files = Storage::disk(self::STORAGE_DISK)->files($directory);
            $totalSize = 0;
            $fileCount = count($files);

            foreach ($files as $file) {
                $totalSize += Storage::disk(self::STORAGE_DISK)->size($file);
            }

            return [
                'success' => true,
                'file_count' => $fileCount,
                'total_size' => $totalSize,
                'total_size_formatted' => $this->formatFileSize($totalSize),
                'directory' => $directory
            ];

        } catch (Exception $e) {
            Log::error('Error getting storage stats', [
                'directory' => $directory,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
} 