<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ZipArchive;

class UserImportService
{
    protected array $extractedPhotos = [];
    protected ?string $tempDirectory = null;

    /**
     * Extract photos from ZIP file and prepare them for import.
     *
     * @param UploadedFile $zipFile
     * @return array Map of filename => temporary path
     * @throws \Exception
     */
    public function extractPhotosFromZip(UploadedFile $zipFile): array
    {
        $zip = new ZipArchive();
        $zipPath = $zipFile->getRealPath();

        if ($zip->open($zipPath) !== true) {
            throw new \Exception('No se pudo abrir el archivo ZIP.');
        }

        // Create temp directory for extraction
        $this->tempDirectory = storage_path('app/temp/imports/' . Str::random(20));
        if (!file_exists($this->tempDirectory)) {
            mkdir($this->tempDirectory, 0755, true);
        }

        $extractedPhotos = [];
        $validExtensions = ['jpg', 'jpeg', 'png', 'gif'];

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $filename = $zip->getNameIndex($i);
            $fileInfo = pathinfo($filename);

            // Skip directories and hidden files
            if (str_ends_with($filename, '/') || str_starts_with($fileInfo['basename'], '.')) {
                continue;
            }

            // Only process image files
            if (!isset($fileInfo['extension']) || !in_array(strtolower($fileInfo['extension']), $validExtensions)) {
                continue;
            }

            // Extract file
            $extractPath = $this->tempDirectory . '/' . $fileInfo['basename'];
            if (copy("zip://{$zipPath}#{$filename}", $extractPath)) {
                // Check file size (max 5MB)
                if (filesize($extractPath) > 5 * 1024 * 1024) {
                    unlink($extractPath);
                    continue;
                }

                $extractedPhotos[$fileInfo['basename']] = $extractPath;
            }
        }

        $zip->close();

        $this->extractedPhotos = $extractedPhotos;

        return $extractedPhotos;
    }

    /**
     * Download photo from URL.
     *
     * @param string $url
     * @return string|null Path to downloaded file
     */
    public function downloadPhotoFromUrl(string $url): ?string
    {
        try {
            // Create temp directory if not exists
            if (!$this->tempDirectory) {
                $this->tempDirectory = storage_path('app/temp/imports/' . Str::random(20));
                if (!file_exists($this->tempDirectory)) {
                    mkdir($this->tempDirectory, 0755, true);
                }
            }

            $contents = @file_get_contents($url, false, stream_context_create([
                'http' => [
                    'timeout' => 10,
                    'user_agent' => 'Mozilla/5.0',
                ],
            ]));

            if ($contents === false) {
                return null;
            }

            // Check file size
            if (strlen($contents) > 5 * 1024 * 1024) {
                return null;
            }

            // Determine extension from content
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->buffer($contents);

            $extension = match ($mimeType) {
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/gif' => 'gif',
                default => null,
            };

            if (!$extension) {
                return null;
            }

            $filename = Str::random(20) . '.' . $extension;
            $path = $this->tempDirectory . '/' . $filename;

            if (file_put_contents($path, $contents) === false) {
                return null;
            }

            return $path;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Store photo in avatars directory.
     *
     * @param string $sourcePath
     * @param string $email
     * @return string|null Stored path relative to storage/app/public
     */
    public function storeAvatar(string $sourcePath, string $email): ?string
    {
        try {
            $extension = pathinfo($sourcePath, PATHINFO_EXTENSION);
            $filename = Str::slug(explode('@', $email)[0]) . '_' . Str::random(8) . '.' . $extension;

            // Store in public disk under avatars directory
            $destinationPath = 'avatars/' . $filename;

            if (!Storage::disk('public')->put($destinationPath, file_get_contents($sourcePath))) {
                return null;
            }

            return $destinationPath;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Find photo for user by different matching strategies.
     *
     * @param string $photoReference Photo filename or URL from Excel
     * @param string $email User email
     * @param string|null $name User name
     * @return string|null Path to photo file
     */
    public function findPhotoForUser(string $photoReference, string $email, ?string $name = null): ?string
    {
        // If it's a URL, download it
        if (filter_var($photoReference, FILTER_VALIDATE_URL)) {
            return $this->downloadPhotoFromUrl($photoReference);
        }

        // If it's a filename, find it in extracted photos
        if (isset($this->extractedPhotos[$photoReference])) {
            return $this->extractedPhotos[$photoReference];
        }

        // Try different matching strategies
        $strategies = [
            $photoReference, // Exact match
            strtolower($photoReference), // Lowercase
            basename($photoReference), // Just filename without path
        ];

        // Try matching by email username
        $emailUsername = explode('@', $email)[0];
        foreach (['jpg', 'jpeg', 'png', 'gif'] as $ext) {
            $strategies[] = $emailUsername . '.' . $ext;
            $strategies[] = strtolower($emailUsername) . '.' . $ext;
        }

        // Try matching by name
        if ($name) {
            $nameSlug = Str::slug($name);
            foreach (['jpg', 'jpeg', 'png', 'gif'] as $ext) {
                $strategies[] = $nameSlug . '.' . $ext;
            }
        }

        foreach ($strategies as $strategy) {
            foreach ($this->extractedPhotos as $filename => $path) {
                if (strcasecmp($filename, $strategy) === 0) {
                    return $path;
                }
            }
        }

        return null;
    }

    /**
     * Clean up temporary files and directories.
     */
    public function cleanup(): void
    {
        if ($this->tempDirectory && file_exists($this->tempDirectory)) {
            $files = glob($this->tempDirectory . '/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            rmdir($this->tempDirectory);
        }

        $this->extractedPhotos = [];
        $this->tempDirectory = null;
    }

    /**
     * Get list of extracted photo filenames.
     *
     * @return array
     */
    public function getExtractedPhotoNames(): array
    {
        return array_keys($this->extractedPhotos);
    }
}
