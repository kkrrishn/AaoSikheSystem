<?php

declare(strict_types=1);

namespace AaoSikheSystem\helper;
use AaoSikheSystem\helper\PathManager;

use Exception;
use GdImage;

/**
 * Advanced File Upload Handler with Encryption and File Processing
 * 
 * Features:
 * - File encryption/decryption
 * - Image resizing and compression
 * - File type validation
 * - Virus scanning simulation
 * - Multiple upload strategies
 * - Chunked upload support
 */
class FileHelper
{
    /**
     * Default allowed file extensions
     */
    private array $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'pdf', 'txt', 'doc', 'docx'];

    /**
     * Default allowed MIME types
     */
    private array $allowedMimeTypes = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/bmp',
        'image/webp',
        'application/pdf',
        'text/plain',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
    ];

    /**
     * Maximum file size in bytes (10MB)
     */
    private int $maxFileSize = 10 * 1024 * 1024;

    /**
     * Upload directory
     */
    private string $uploadDir=BASE_URI. '/storage/uploads/';

    /**
     * Encryption settings
     */
    private bool $encryptFiles = false;
    private string $encryptionKey = ''; // auto-generated if empty
    private string $encryptionMethod = 'AES-256-CBC';

    /**
     * Image processing defaults
     */
    private bool $compressImages = false;
    private int $imageQuality = 85;
    private int $maxWidth = 1920;
    private int $maxHeight = 1080;
    private bool $preserveOriginal = false;

    /**
     * Thumbnail generation defaults
     */
    private bool $generateThumbnails = false;
    private int $thumbnailWidth = 200;
    private int $thumbnailHeight = 200;

    /**
     * Chunked upload defaults
     */
    private bool $useChunkedUpload = false;
    private int $chunkSize = 1048576; // 1MB

    /**
     * Virus scan default
     */
    private bool $scanForViruses = true;

    /**
     * Error & uploaded files storage
     */
    private array $errors = [];
    private array $uploadedFiles = [];

    // Encryption header/version
    private const ENCRYPTION_HEADER = 'ENCv1';
    private const FILE_VERSION = '1.0';




    /**
     * Constructor
     */
    public function __construct(array $config = [])
    {
         $this->uploadDir = PathManager::get('uploads') ?? BASE_URI. '/storage/uploads/';
        $this->configure($config);
        $this->validateUploadDirectory();
    }

    /**
     * Configure uploader with options
     */
    public function configure(array $config): self
    {
        foreach ($config as $key => $value) {
            if (property_exists($this, $key)) {
                $this->{$key} = $value;
            }
        }

        if ($this->encryptFiles && empty($this->encryptionKey)) {
            $this->encryptionKey = $this->generateEncryptionKey();
        }

        return $this;
    }

    /**
     * Handle file upload
     */
    public function upload(string $fieldName, array $options = []): array
    {
        $this->errors = [];
        $this->uploadedFiles = [];

        if (!isset($_FILES[$fieldName])) {
            $this->errors[] = "No files uploaded for field: {$fieldName}";
            return ['success' => false, 'errors' => $this->errors];
        }

        $files = $this->normalizeFiles($_FILES[$fieldName]);
        $results = [];

        foreach ($files as $file) {
            $result = $this->processSingleFile($file, $options);
            $results[] = $result;

            if ($result['success']) {
                $this->uploadedFiles[] = $result;
            }
        }

        return [
            'success' => empty($this->errors),
            'results' => $results,
            'errors' => $this->errors
        ];
    }
    public static function uploadStatic(string $fieldName, array $options = [], array $config = []): array
    {
        // Create a temporary instance with optional config overrides
        $uploader = new self($config);

        // Call the normal upload method on the instance
        return $uploader->upload($fieldName, $options);
    }

    /**
 * Upload a single file array (already normalized)
 * Example input:
 * [
 *   name, type, tmp_name, error, size
 * ]
 */
public function uploadFileArray(array $file, array $options = []): array
{
    $this->errors = [];
    $this->uploadedFiles = [];

    // Basic structure validation
    $requiredKeys = ['name', 'type', 'tmp_name', 'error', 'size'];
    foreach ($requiredKeys as $key) {
        if (!array_key_exists($key, $file)) {
            return [
                'success' => false,
                'errors' => ["Invalid file array: missing {$key}"]
            ];
        }
    }

    // Process single file
    $result = $this->processSingleFile($file, $options);

    if ($result['success']) {
        $this->uploadedFiles[] = $result;
    }

    return [
        'success' => $result['success'],
        'results' => [$result],
        'errors'  => $this->errors
    ];
}

    /**
     * Process single file
     */
    private function processSingleFile(array $file, array $options): array
    {
        try {
            // Validate file
            $validation = $this->validateFile($file);
            if (!$validation['valid']) {
                $this->errors[] = $validation['error'];
                return ['success' => false, 'error' => $validation['error']];
            }

            // Check for viruses
            if ($this->scanForViruses && !$this->scanFile($file['tmp_name'])) {
                $error = "File failed virus scan";
                $this->errors[] = $error;
                return ['success' => false, 'error' => $error];
            }

            // Generate unique filename
            // Determine file name
            $originalName = pathinfo($file['name'], PATHINFO_FILENAME);
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            if (!empty($options['custom_name'])) {
                // Use custom name provided by user
                $safeName = preg_replace('/[^a-zA-Z0-9_-]/', '_', pathinfo($options['custom_name'], PATHINFO_FILENAME));
                $customExtension = pathinfo($options['custom_name'], PATHINFO_EXTENSION);
                // If custom name does not have extension, append original
                $extensionToUse = $customExtension ?: $extension;
                $finalName = $safeName . '.' . $extensionToUse;
            } else {
                // Generate unique filename
                $finalName = $this->generateUniqueFilename($originalName, $extension);
            }

            $targetPath = $this->uploadDir . $finalName;


            // Handle chunked upload if enabled
            if ($this->useChunkedUpload) {
                return $this->handleChunkedUpload($file, $targetPath, $options);
            }

            // Move uploaded file
            if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
                throw new Exception("Failed to move uploaded file");
            }

            // Process file based on type
            $processedFile = $this->processFileByType($targetPath, $originalName, $extension);

            // Encrypt if enabled
            if ($this->encryptFiles) {
                $processedFile = $this->encryptFile($processedFile['path']);
                $processedFile['encrypted'] = true;
            }

            return [
                'success' => true,
                'original_name' => $file['name'],
                'saved_name' => basename($processedFile['path']),
                'path' => $processedFile['path'],
                'size' => filesize($processedFile['path']),
                'mime_type' => mime_content_type($processedFile['path']),
                'encrypted' => $this->encryptFiles,
                'compressed' => $processedFile['compressed'] ?? false,
                'thumbnail' => $processedFile['thumbnail'] ?? null,
                'hash' => hash_file('sha256', $processedFile['path'])
            ];
        } catch (Exception $e) {
            $this->errors[] = $e->getMessage();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Process file based on its type
     */
    private function processFileByType(string $filePath, string $originalName, string $extension): array
    {
        $result = ['path' => $filePath];
        $mimeType = mime_content_type($filePath);

        // Handle images
        if (strpos($mimeType, 'image/') === 0 && $this->isImageExtension($extension)) {
            return $this->processImage($filePath, $originalName);
        }

        // Handle PDF compression (if needed)
        if ($mimeType === 'application/pdf' && $this->compressImages) {
            // PDF compression logic can be added here
        }

        return $result;
    }

    /**
     * Process image file
     */
    private function processImage(string $filePath, string $originalName): array
    {
        $result = ['path' => $filePath];

        try {
            $imageInfo = getimagesize($filePath);
            if (!$imageInfo) {
                return $result;
            }

            list($width, $height) = $imageInfo;
            $imageType = $imageInfo[2];

            // Check if resizing is needed
            if ($this->compressImages && ($width > $this->maxWidth || $height > $this->maxHeight)) {
                $resizedPath = $this->resizeImage($filePath, $width, $height, $imageType);

                if ($resizedPath && $resizedPath !== $filePath) {
                    if (!$this->preserveOriginal) {
                        unlink($filePath);
                        $filePath = $resizedPath;
                    }
                    $result['path'] = $resizedPath;
                    $result['compressed'] = true;
                }
            }

            // Generate thumbnail if enabled
            if ($this->generateThumbnails) {
                $thumbnailPath = $this->generateThumbnail($filePath, $imageType);
                if ($thumbnailPath) {
                    $result['thumbnail'] = $thumbnailPath;
                }
            }
        } catch (Exception $e) {
            // If image processing fails, keep original file
            $this->errors[] = "Image processing failed: " . $e->getMessage();
        }

        return $result;
    }

    /**
     * Resize image
     */
    private function resizeImage(string $filePath, int $width, int $height, int $imageType): ?string
    {
        $image = $this->createImageFromType($filePath, $imageType);
        if (!$image) {
            return null;
        }

        // Calculate new dimensions
        $ratio = min($this->maxWidth / $width, $this->maxHeight / $height);
        $newWidth = (int)($width * $ratio);
        $newHeight = (int)($height * $ratio);

        // Create new image
        $newImage = imagecreatetruecolor($newWidth, $newHeight);

        // Preserve transparency for PNG and GIF
        if ($imageType === IMAGETYPE_PNG || $imageType === IMAGETYPE_GIF) {
            imagealphablending($newImage, false);
            imagesavealpha($newImage, true);
            $transparent = imagecolorallocatealpha($newImage, 255, 255, 255, 127);
            imagefilledrectangle($newImage, 0, 0, $newWidth, $newHeight, $transparent);
        }

        // Resize image
        imagecopyresampled($newImage, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

        // Generate new filename
        $info = pathinfo($filePath);
        $newPath = $info['dirname'] . '/' . $info['filename'] . '_resized.' . $info['extension'];

        // Save resized image
        $this->saveImageByType($newImage, $newPath, $imageType);

        imagedestroy($image);
        imagedestroy($newImage);

        return $newPath;
    }

    /**
     * Generate thumbnail
     */
    private function generateThumbnail(string $filePath, int $imageType): ?string
    {
        $image = $this->createImageFromType($filePath, $imageType);
        if (!$image) {
            return null;
        }

        $width = imagesx($image);
        $height = imagesy($image);

        // Calculate thumbnail dimensions
        $ratio = min($this->thumbnailWidth / $width, $this->thumbnailHeight / $height);
        $thumbWidth = (int)($width * $ratio);
        $thumbHeight = (int)($height * $ratio);

        // Create thumbnail
        $thumbnail = imagecreatetruecolor($thumbWidth, $thumbHeight);

        if ($imageType === IMAGETYPE_PNG || $imageType === IMAGETYPE_GIF) {
            imagealphablending($thumbnail, false);
            imagesavealpha($thumbnail, true);
            $transparent = imagecolorallocatealpha($thumbnail, 255, 255, 255, 127);
            imagefilledrectangle($thumbnail, 0, 0, $thumbWidth, $thumbHeight, $transparent);
        }

        imagecopyresampled($thumbnail, $image, 0, 0, 0, 0, $thumbWidth, $thumbHeight, $width, $height);

        // Save thumbnail
        $info = pathinfo($filePath);
        $thumbPath = $info['dirname'] . '/' . $info['filename'] . '_thumb.' . $info['extension'];

        $this->saveImageByType($thumbnail, $thumbPath, $imageType);

        imagedestroy($image);
        imagedestroy($thumbnail);

        return $thumbPath;
    }

    /**
     * Create image from file based on type
     */
    private function createImageFromType(string $filePath, int $imageType): ?GdImage
    {
        return match ($imageType) {
            IMAGETYPE_JPEG => imagecreatefromjpeg($filePath),
            IMAGETYPE_PNG => imagecreatefrompng($filePath),
            IMAGETYPE_GIF => imagecreatefromgif($filePath),
            IMAGETYPE_WEBP => imagecreatefromwebp($filePath),
            IMAGETYPE_BMP => imagecreatefrombmp($filePath),
            default => null,
        };
    }

    /**
     * Save image based on type
     */
    private function saveImageByType(GdImage $image, string $path, int $imageType): bool
    {
        return match ($imageType) {
            IMAGETYPE_JPEG => imagejpeg($image, $path, $this->imageQuality),
            IMAGETYPE_PNG => imagepng($image, $path, 9),
            IMAGETYPE_GIF => imagegif($image, $path),
            IMAGETYPE_WEBP => imagewebp($image, $path, $this->imageQuality),
            IMAGETYPE_BMP => imagebmp($image, $path),
            default => false,
        };
    }

    /**
     * Encrypt file
     */
    public function encryptFile(string $filePath): array
    {
        $content = file_get_contents($filePath);
        if ($content === false) {
            throw new Exception("Failed to read file for encryption");
        }

        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($this->encryptionMethod));
        $encrypted = openssl_encrypt(
            $content,
            $this->encryptionMethod,
            $this->encryptionKey,
            OPENSSL_RAW_DATA,
            $iv
        );

        if ($encrypted === false) {
            throw new Exception("Encryption failed");
        }

        // Create encrypted file with header
        $encryptedContent = self::ENCRYPTION_HEADER . $iv . $encrypted;
        $encryptedPath = $filePath . '.enc';

        if (file_put_contents($encryptedPath, $encryptedContent) === false) {
            throw new Exception("Failed to write encrypted file");
        }

        // Remove original file if not preserving
        if (!$this->preserveOriginal) {
            unlink($filePath);
        }

        return [
            'path' => $encryptedPath,
            'encrypted' => true,
            'iv' => base64_encode($iv)
        ];
    }

    /**
     * Decrypt file
     */
    public function decryptFile(string $encryptedPath, string $outputPath = null): array
    {
        $content = file_get_contents($encryptedPath);
        if ($content === false) {
            throw new Exception("Failed to read encrypted file");
        }

        // Check header
        $header = substr($content, 0, strlen(self::ENCRYPTION_HEADER));
        if ($header !== self::ENCRYPTION_HEADER) {
            throw new Exception("Invalid encrypted file format");
        }

        // Extract IV and encrypted data
        $ivLength = openssl_cipher_iv_length($this->encryptionMethod);
        $iv = substr($content, strlen(self::ENCRYPTION_HEADER), $ivLength);
        $encrypted = substr($content, strlen(self::ENCRYPTION_HEADER) + $ivLength);

        $decrypted = openssl_decrypt(
            $encrypted,
            $this->encryptionMethod,
            $this->encryptionKey,
            OPENSSL_RAW_DATA,
            $iv
        );

        if ($decrypted === false) {
            throw new Exception("Decryption failed");
        }

        // Determine output path
        if ($outputPath === null) {
            $outputPath = preg_replace('/\.enc$/', '', $encryptedPath);
        }

        if (file_put_contents($outputPath, $decrypted) === false) {
            throw new Exception("Failed to write decrypted file");
        }

        return [
            'path' => $outputPath,
            'decrypted' => true,
            'size' => filesize($outputPath)
        ];
    }

    /**
     * Handle chunked upload
     */
    private function handleChunkedUpload(array $file, string $targetPath, array $options): array
    {
        $chunkIndex = $options['chunkIndex'] ?? 0;
        $totalChunks = $options['totalChunks'] ?? 1;
        $chunkId = $options['chunkId'] ?? md5($file['name'] . time());

        $chunkPath = $this->uploadDir . 'chunks/' . $chunkId . '_' . $chunkIndex;

        // Move chunk
        if (!move_uploaded_file($file['tmp_name'], $chunkPath)) {
            throw new Exception("Failed to move chunk file");
        }

        // If this is the last chunk, combine all chunks
        if ($chunkIndex == $totalChunks - 1) {
            return $this->combineChunks($chunkId, $totalChunks, $targetPath);
        }

        return [
            'success' => true,
            'chunk_uploaded' => true,
            'chunk_index' => $chunkIndex,
            'total_chunks' => $totalChunks,
            'chunk_id' => $chunkId
        ];
    }

    /**
     * Combine uploaded chunks
     */
    private function combineChunks(string $chunkId, int $totalChunks, string $targetPath): array
    {
        $target = fopen($targetPath, 'wb');
        if (!$target) {
            throw new Exception("Failed to create target file");
        }

        for ($i = 0; $i < $totalChunks; $i++) {
            $chunkPath = $this->uploadDir . 'chunks/' . $chunkId . '_' . $i;

            if (!file_exists($chunkPath)) {
                fclose($target);
                unlink($targetPath);
                throw new Exception("Missing chunk: {$i}");
            }

            $chunk = fopen($chunkPath, 'rb');
            stream_copy_to_stream($chunk, $target);
            fclose($chunk);
            unlink($chunkPath);
        }

        fclose($target);

        // Clean up empty chunk directory
        $chunkDir = $this->uploadDir . 'chunks/';
        if (is_dir($chunkDir) && count(scandir($chunkDir)) == 2) {
            rmdir($chunkDir);
        }

        return [
            'success' => true,
            'chunks_combined' => true,
            'saved_name' => basename($targetPath),
            'path' => $targetPath
        ];
    }

    /**
     * Validate file
     */
    private function validateFile(array $file): array
    {
        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return [
                'valid' => false,
                'error' => $this->getUploadError($file['error'])
            ];
        }

        // Check file size
        if ($file['size'] > $this->maxFileSize) {
            return [
                'valid' => false,
                'error' => "File size exceeds maximum allowed size of " . $this->formatBytes($this->maxFileSize)
            ];
        }

        // Check extension
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!empty($this->allowedExtensions) && !in_array($extension, $this->allowedExtensions)) {
            return [
                'valid' => false,
                'error' => "File extension '{$extension}' is not allowed"
            ];
        }

        // Check MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!empty($this->allowedMimeTypes) && !in_array($mimeType, $this->allowedMimeTypes)) {
            return [
                'valid' => false,
                'error' => "File type '{$mimeType}' is not allowed"
            ];
        }

        return ['valid' => true];
    }

    /**
     * Scan file for viruses (simulated - integrate with actual antivirus API)
     */
    private function scanFile(string $filePath): bool
    {
        // This is a simulation. In production, integrate with:
        // - ClamAV (clamd)
        // - VirusTotal API
        // - Commercial antivirus APIs

        // For now, just check for suspicious extensions and file signatures
        $suspiciousExtensions = ['php', 'exe', 'bat', 'sh', 'js'];
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        if (in_array($extension, $suspiciousExtensions)) {
            // Additional checks could be added here
            $content = file_get_contents($filePath, false, null, 0, 100);
            if (str_contains($content, '<?php') && $extension === 'php') {
                // This is expected for PHP files
                return true;
            }

            // Check for executable signatures
            $signatures = [
                'MZ' => 0, // DOS executable
                "\x7FELF" => 0, // ELF executable
            ];

            foreach ($signatures as $sig => $offset) {
                if (strpos($content, $sig) === $offset) {
                    return false; // Found executable signature
                }
            }
        }

        return true;
    }

    /**
     * Generate unique filename
     */
    private function generateUniqueFilename(string $originalName, string $extension): string
    {
        $safeName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $originalName);
        $timestamp = time();
        $random = bin2hex(random_bytes(8));

        return "{$safeName}_{$timestamp}_{$random}.{$extension}";
    }

    /**
     * Normalize files array
     */
    private function normalizeFiles(array $files): array
    {
        $normalized = [];

        if (is_array($files['name'])) {
            foreach ($files['name'] as $key => $name) {
                $normalized[] = [
                    'name' => $name,
                    'type' => $files['type'][$key],
                    'tmp_name' => $files['tmp_name'][$key],
                    'error' => $files['error'][$key],
                    'size' => $files['size'][$key]
                ];
            }
        } else {
            $normalized[] = $files;
        }

        return $normalized;
    }

    /**
     * Validate upload directory
     */
    private function validateUploadDirectory(): void
    {
        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }

        // Create chunks directory if using chunked upload
        if ($this->useChunkedUpload) {
            $chunkDir = $this->uploadDir . 'chunks/';
            if (!is_dir($chunkDir)) {
                mkdir($chunkDir, 0755, true);
            }
        }

        // Create protected directory for encrypted files
        $protectedDir = $this->uploadDir . 'protected/';
        if (!is_dir($protectedDir)) {
            mkdir($protectedDir, 0755, true);
        }
    }

    /**
     * Generate encryption key
     */
    private function generateEncryptionKey(): string
    {
        return hash('sha256', random_bytes(32));
    }

    /**
     * Get upload error message
     */
    private function getUploadError(int $errorCode): string
    {
        return match ($errorCode) {
            UPLOAD_ERR_INI_SIZE => 'The uploaded file exceeds the upload_max_filesize directive in php.ini',
            UPLOAD_ERR_FORM_SIZE => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form',
            UPLOAD_ERR_PARTIAL => 'The uploaded file was only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload',
            default => 'Unknown upload error',
        };
    }

    /**
     * Format bytes to human readable format
     */
    private function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);

        return round($bytes, $precision) . ' ' . $units[$pow];
    }

    /**
     * Check if extension is an image
     */
    private function isImageExtension(string $extension): bool
    {
        $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'svg'];
        return in_array(strtolower($extension), $imageExtensions);
    }

    /**
     * Get uploaded files
     */
    public function getUploadedFiles(): array
    {
        return $this->uploadedFiles;
    }

    /**
     * Get errors
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Clean up temporary files
     */
    public function cleanup(): void
    {
        // Clean up old chunk files (older than 24 hours)
        if ($this->useChunkedUpload) {
            $chunkDir = $this->uploadDir . 'chunks/';
            if (is_dir($chunkDir)) {
                $files = scandir($chunkDir);
                foreach ($files as $file) {
                    if ($file !== '.' && $file !== '..') {
                        $filePath = $chunkDir . $file;
                        if (filemtime($filePath) < time() - 86400) {
                            unlink($filePath);
                        }
                    }
                }
            }
        }
    }

    /**
     * Set encryption key
     */
    public function setEncryptionKey(string $key): self
    {
        $this->encryptionKey = $key;
        return $this;
    }

    /**
     * Set allowed extensions
     */
    public function setAllowedExtensions(array $extensions): self
    {
        $this->allowedExtensions = array_map('strtolower', $extensions);
        return $this;
    }

    /**
     * Set allowed MIME types
     */
    public function setAllowedMimeTypes(array $mimeTypes): self
    {
        $this->allowedMimeTypes = $mimeTypes;
        return $this;
    }

    /**
     * Set maximum file size
     */
    public function setMaxFileSize(int $bytes): self
    {
        $this->maxFileSize = $bytes;
        return $this;
    }

    /**
     * Enable/disable image compression
     */
    public function setCompressImages(bool $compress, int $quality = 85): self
    {
        $this->compressImages = $compress;
        $this->imageQuality = max(1, min(100, $quality));
        return $this;
    }

    /**
     * Set maximum image dimensions
     */
    public function setMaxImageDimensions(int $width, int $height): self
    {
        $this->maxWidth = $width;
        $this->maxHeight = $height;
        return $this;
    }

    /**
     * Enable/disable encryption
     */
    public function setEncryption(bool $encrypt, string $method = 'AES-256-CBC'): self
    {
        $this->encryptFiles = $encrypt;
        $this->encryptionMethod = $method;
        return $this;
    }

    /**
     * Enable/disable thumbnail generation
     */
    public function setThumbnailGeneration(bool $generate, int $width = 200, int $height = 200): self
    {
        $this->generateThumbnails = $generate;
        $this->thumbnailWidth = $width;
        $this->thumbnailHeight = $height;
        return $this;
    }

    /**
     * Enable/disable chunked upload
     */
    public function setChunkedUpload(bool $enabled, int $chunkSize = 1048576): self
    {
        $this->useChunkedUpload = $enabled;
        $this->chunkSize = $chunkSize;
        return $this;
    }

    /**
     * Set upload directory
     */
    public function setUploadDirectory(string $directory): self
    {
        $this->uploadDir = rtrim($directory, '/') . '/';
        $this->validateUploadDirectory();
        return $this;
    }
}
