<?php

namespace Rake\Manager;

use Rake\Adapter\Database\DatabaseAdapterInterface;

/**
 * File Integrity Manager
 * Quản lý file integrity và deduplication sử dụng XXH128
 */
class FileIntegrityManager
{
    /**
     * @var FileChecksumManager
     */
    private $checksumManager;

    /**
     * @var DatabaseAdapterInterface
     */
    private $databaseAdapter;

    /**
     * @var string
     */
    private $uploadDir;

    public function __construct(DatabaseAdapterInterface $databaseAdapter, string $uploadDir = null)
    {
        $this->databaseAdapter = $databaseAdapter;
        $this->checksumManager = new FileChecksumManager($databaseAdapter);
        $this->uploadDir = $uploadDir ?: sys_get_temp_dir() . '/rake_uploads/';

        // Tạo upload directory nếu chưa tồn tại
        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }
    }

    /**
     * Download và lưu file với integrity check
     *
     * @param string $url
     * @param int $resourceId
     * @return array
     */
    public function downloadAndStoreFile(string $url, int $resourceId): array
    {
        try {
            // Download file
            $tempFile = $this->downloadFile($url);

            // Tạo checksum
            $checksum = $this->checksumManager->createFileChecksum($tempFile);

            // Kiểm tra duplicate
            $existingResourceId = $this->checksumManager->getResourceIdByChecksum($checksum);

            if ($existingResourceId && $existingResourceId !== $resourceId) {
                // File đã tồn tại, return existing file info
                unlink($tempFile); // Xóa temp file

                return [
                    'success' => true,
                    'duplicate' => true,
                    'existing_resource_id' => $existingResourceId,
                    'checksum' => $checksum,
                    'file_path' => $this->getFilePathByResourceId($existingResourceId)
                ];
            }

            // Lưu file vào permanent location
            $permanentPath = $this->saveFilePermanently($tempFile, $checksum);

            // Lưu checksum vào database
            $this->checksumManager->saveChecksum($resourceId, $checksum);

            // Lưu file path vào metadata
            $this->saveFilePath($resourceId, $permanentPath);

            return [
                'success' => true,
                'duplicate' => false,
                'checksum' => $checksum,
                'file_path' => $permanentPath,
                'file_size' => filesize($permanentPath)
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Download file từ URL
     *
     * @param string $url
     * @return string
     * @throws \RuntimeException
     */
    private function downloadFile(string $url): string
    {
        $tempFile = tempnam($this->uploadDir, 'rake_download_');

        $context = stream_context_create([
            'http' => [
                'timeout' => 30,
                'user_agent' => 'Rake/2.0 File Downloader'
            ]
        ]);

        $content = file_get_contents($url, false, $context);

        if ($content === false) {
            throw new \RuntimeException("Cannot download file from: {$url}");
        }

        if (file_put_contents($tempFile, $content) === false) {
            throw new \RuntimeException("Cannot write to temp file: {$tempFile}");
        }

        return $tempFile;
    }

    /**
     * Lưu file vào permanent location
     *
     * @param string $tempFile
     * @param string $checksum
     * @return string
     */
    private function saveFilePermanently(string $tempFile, string $checksum): string
    {
        $extension = pathinfo($tempFile, PATHINFO_EXTENSION);
        $permanentPath = $this->uploadDir . $checksum . ($extension ? ".{$extension}" : '');

        if (!rename($tempFile, $permanentPath)) {
            throw new \RuntimeException("Cannot move file to permanent location: {$permanentPath}");
        }

        return $permanentPath;
    }

    /**
     * Lưu file path vào metadata
     *
     * @param int $resourceId
     * @param string $filePath
     * @return bool
     */
    private function saveFilePath(int $resourceId, string $filePath): bool
    {
        $table = 'rake_resources';

        $metadata = [
            'file_path' => $filePath,
            'file_size' => filesize($filePath),
            'file_modified' => filemtime($filePath)
        ];

        return $this->databaseAdapter->update(
            $table,
            ['metadata' => json_encode($metadata)],
            ['id' => $resourceId]
        );
    }

    /**
     * Lấy file path theo resource ID
     *
     * @param int $resourceId
     * @return string|null
     */
    private function getFilePathByResourceId(int $resourceId): ?string
    {
        $result = $this->databaseAdapter->select(
            'rake_resources',
            ['metadata'],
            ['id' => $resourceId],
            1
        );

        if (empty($result)) {
            return null;
        }

        $metadata = json_decode($result[0]['metadata'], true);

        return $metadata['file_path'] ?? null;
    }

    /**
     * Kiểm tra integrity của file
     *
     * @param int $resourceId
     * @return bool
     */
    public function verifyFileIntegrity(int $resourceId): bool
    {
        $filePath = $this->getFilePathByResourceId($resourceId);

        if (!$filePath || !file_exists($filePath)) {
            return false;
        }

        return $this->checksumManager->verifyFileIntegrity($resourceId, $filePath);
    }

    /**
     * Lấy thống kê file integrity
     *
     * @return array
     */
    public function getIntegrityStatistics(): array
    {
        $totalFiles = $this->databaseAdapter->count('rake_resources', ['data_type' => 'file']);
        $verifiedFiles = 0;
        $corruptedFiles = 0;

        $files = $this->databaseAdapter->select(
            'rake_resources',
            ['id'],
            ['data_type' => 'file']
        );

        foreach ($files as $file) {
            if ($this->verifyFileIntegrity($file['id'])) {
                $verifiedFiles++;
            } else {
                $corruptedFiles++;
            }
        }

        return [
            'total_files' => $totalFiles,
            'verified_files' => $verifiedFiles,
            'corrupted_files' => $corruptedFiles,
            'integrity_rate' => $totalFiles > 0 ? ($verifiedFiles / $totalFiles) * 100 : 0
        ];
    }

    /**
     * Cleanup corrupted files
     *
     * @return array
     */
    public function cleanupCorruptedFiles(): array
    {
        $files = $this->databaseAdapter->select(
            'rake_resources',
            ['id', 'metadata'],
            ['data_type' => 'file']
        );

        $cleaned = 0;
        $errors = [];

        foreach ($files as $file) {
            if (!$this->verifyFileIntegrity($file['id'])) {
                $metadata = json_decode($file['metadata'], true);
                $filePath = $metadata['file_path'] ?? null;

                if ($filePath && file_exists($filePath)) {
                    if (unlink($filePath)) {
                        $cleaned++;
                    } else {
                        $errors[] = "Cannot delete file: {$filePath}";
                    }
                }

                // Mark resource as corrupted
                $this->databaseAdapter->update(
                    'rake_resources',
                    ['import_status' => 'corrupted'],
                    ['id' => $file['id']]
                );
            }
        }

        return [
            'cleaned_files' => $cleaned,
            'errors' => $errors
        ];
    }

    /**
     * Lấy thông tin file theo checksum
     *
     * @param string $checksum
     * @return array|null
     */
    public function getFileInfoByChecksum(string $checksum): ?array
    {
        $resourceId = $this->checksumManager->getResourceIdByChecksum($checksum);

        if (!$resourceId) {
            return null;
        }

        $result = $this->databaseAdapter->select(
            'rake_resources',
            ['*'],
            ['id' => $resourceId],
            1
        );

        if (empty($result)) {
            return null;
        }

        $resource = $result[0];
        $metadata = json_decode($resource['metadata'], true);

        return [
            'resource_id' => $resource['id'],
            'checksum' => $checksum,
            'file_path' => $metadata['file_path'] ?? null,
            'file_size' => $metadata['file_size'] ?? null,
            'import_status' => $resource['import_status']
        ];
    }

    /**
     * Lấy upload directory
     *
     * @return string
     */
    public function getUploadDir(): string
    {
        return $this->uploadDir;
    }

    /**
     * Set upload directory
     *
     * @param string $uploadDir
     */
    public function setUploadDir(string $uploadDir): void
    {
        $this->uploadDir = rtrim($uploadDir, '/') . '/';

        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }
    }
}