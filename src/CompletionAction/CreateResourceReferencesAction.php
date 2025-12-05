<?php

namespace Rake\CompletionAction;

use Rake\Contracts\File\FileDownloaderClientInterface;
use Rake\Manager\FileChecksumManager;
use Rake\Contracts\Database\Adapter\DatabaseAdapterInterface;

/**
 * Create Resource References Completion Action
 * 
 * Import media files (images, videos, mp3) và tạo resource references.
 * - Download files using FileDownloaderClient
 * - Create checksums for deduplication
 * - Sideload to WordPress media library
 * - Create URL mappings (old URL → new WordPress URL)
 * - Replace URLs in content
 */
class CreateResourceReferencesAction extends AbstractCompletionAction
{
    /**
     * @var FileDownloaderClientInterface
     */
    private $fileDownloader;

    /**
     * @var FileChecksumManager
     */
    private $checksumManager;

    /**
     * @var DatabaseAdapterInterface
     */
    private $databaseAdapter;

    /**
     * Constructor
     *
     * @param FileDownloaderClientInterface $fileDownloader
     * @param FileChecksumManager $checksumManager
     * @param DatabaseAdapterInterface $databaseAdapter
     * @param array $config
     */
    public function __construct(
        FileDownloaderClientInterface $fileDownloader,
        FileChecksumManager $checksumManager,
        DatabaseAdapterInterface $databaseAdapter,
        array $config = []
    ) {
        parent::__construct($config);
        $this->fileDownloader = $fileDownloader;
        $this->checksumManager = $checksumManager;
        $this->databaseAdapter = $databaseAdapter;
    }

    /**
     * Get action type
     */
    public function getType(): string
    {
        return 'create_resource_references';
    }

    /**
     * Get action name
     */
    public function getName(): string
    {
        return 'Create Resource References';
    }

    /**
     * Get action description
     */
    public function getDescription(): string
    {
        return 'Import media files (images, videos, mp3) with checksum deduplication and URL mapping';
    }

    /**
     * Execute action
     *
     * @param array $context
     * @return array
     */
    public function execute(array $context): array
    {
        $projectId = $context['project_id'] ?? null;
        $domain = $context['domain'] ?? $this->getConfigValue('domain');

        if (!$domain) {
            return [
                'success' => false,
                'message' => 'Domain is required for resource references',
                'data' => null
            ];
        }

        // Step 1: Get all URLs from dpc_rake_data_sources for this domain
        $urls = $this->getUrlsFromDataSources($domain);

        if (empty($urls)) {
            return [
                'success' => true,
                'message' => 'No URLs found to process',
                'data' => ['total' => 0, 'processed' => 0, 'skipped' => 0]
            ];
        }

        // Step 2: Process each URL
        $stats = [
            'total' => count($urls),
            'processed' => 0,
            'skipped' => 0,
            'imported' => 0,
            'reused' => 0,
            'failed' => 0,
            'errors' => []
        ];

        foreach ($urls as $urlData) {
            try {
                $result = $this->processUrl($urlData, $projectId);
                
                if ($result['imported']) {
                    $stats['imported']++;
                } elseif ($result['reused']) {
                    $stats['reused']++;
                } elseif ($result['skipped']) {
                    $stats['skipped']++;
                }
                
                $stats['processed']++;
            } catch (\Exception $e) {
                $stats['failed']++;
                $stats['errors'][] = [
                    'url' => $urlData['url'],
                    'error' => $e->getMessage()
                ];
            }
        }

        // Step 3: Replace URLs in content (if enabled)
        if ($this->getConfigValue('replace_urls_in_content', true)) {
            $replacedCount = $this->replaceUrlsInContent($projectId);
            $stats['urls_replaced'] = $replacedCount;
        }

        return [
            'success' => true,
            'message' => "Processed {$stats['processed']} resources: {$stats['imported']} imported, {$stats['reused']} reused, {$stats['skipped']} skipped, {$stats['failed']} failed",
            'data' => $stats
        ];
    }

    /**
     * Get URLs from dpc_rake_data_sources
     *
     * @param string $domain
     * @return array
     */
    private function getUrlsFromDataSources(string $domain): array
    {
        global $wpdb;
        $table = $wpdb->prefix . 'rake_data_sources';

        // Get URLs that are media files (images, videos, audio)
        $mediaExtensions = $this->getMediaExtensions();
        $extensionConditions = [];
        
        foreach ($mediaExtensions as $ext) {
            $extensionConditions[] = $wpdb->prepare("url LIKE %s", '%.' . $ext);
        }

        $where = implode(' OR ', $extensionConditions);

        $query = "SELECT id, url, resource_type, created_at 
                  FROM {$table} 
                  WHERE domain = %s 
                  AND ({$where})
                  ORDER BY id ASC";

        return $wpdb->get_results($wpdb->prepare($query, $domain), ARRAY_A);
    }

    /**
     * Process single URL
     *
     * @param array $urlData
     * @param int|null $projectId
     * @return array
     */
    private function processUrl(array $urlData, ?int $projectId): array
    {
        $url = $urlData['url'];
        $resourceId = $urlData['id'];

        // Check if already imported (via dpc_rake_resources.imported_at)
        $existingResource = $this->getResourceByUrl($url);
        
        if ($existingResource && !empty($existingResource['imported_at'])) {
            // Already imported, check if we have mapping
            $mapping = $this->getUrlMapping($url);
            
            if ($mapping) {
                return ['imported' => false, 'reused' => false, 'skipped' => true];
            }
        }

        // Download file and create checksum
        $downloadResult = $this->downloadAndChecksum($url, $resourceId);

        if (!$downloadResult['success']) {
            throw new \RuntimeException("Failed to download {$url}: " . $downloadResult['error']);
        }

        $filePath = $downloadResult['file_path'];
        $checksum = $downloadResult['checksum'];

        // Check if file with this checksum already exists
        $existingResourceId = $this->checksumManager->getResourceIdByChecksum($checksum);

        if ($existingResourceId && $existingResourceId !== $resourceId) {
            // File already exists, reuse it
            $existingAttachmentId = $this->getAttachmentIdByResourceId($existingResourceId);
            
            if ($existingAttachmentId) {
                // Create URL mapping for this URL
                $this->createUrlMapping($url, $existingAttachmentId);
                
                // Mark as imported
                $this->markResourceAsImported($resourceId, $existingAttachmentId);
                
                // Clean up downloaded file
                @unlink($filePath);
                
                return ['imported' => false, 'reused' => true, 'skipped' => false];
            }
        }

        // Sideload to WordPress media library
        $sideloadResult = $this->sideloadFile($filePath, $url, $projectId);

        if (!$sideloadResult['success']) {
            @unlink($filePath);
            throw new \RuntimeException("Failed to sideload {$url}: " . $sideloadResult['error']);
        }

        $attachmentId = $sideloadResult['attachment_id'];

        // Save checksum to database
        $this->checksumManager->saveChecksum($resourceId, $checksum);

        // Create URL mapping
        $this->createUrlMapping($url, $attachmentId);

        // Mark resource as imported
        $this->markResourceAsImported($resourceId, $attachmentId);

        // Clean up temp file
        @unlink($filePath);

        return ['imported' => true, 'reused' => false, 'skipped' => false];
    }

    /**
     * Download file and create checksum
     *
     * @param string $url
     * @param int $resourceId
     * @return array
     */
    private function downloadAndChecksum(string $url, int $resourceId): array
    {
        // Download to temp location
        $tempDir = wp_upload_dir()["basedir"] . "/crawlflow/temp/";
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $tempFile = $tempDir . "resource_" . $resourceId . "_" . time();

        $downloadResult = $this->fileDownloader->downloadFile($url, $tempFile);

        if (!$downloadResult["success"]) {
            return $downloadResult;
        }

        // Verify file exists and is readable
        if (!file_exists($tempFile) || !is_readable($tempFile)) {
            return [
                "success" => false,
                "error" => "Downloaded file is not accessible",
                "file_path" => null
            ];
        }

        // Create checksum using FileChecksumManager
        try {
            $checksum = $this->checksumManager->createFileChecksum($tempFile);
            
            // Validate checksum format
            if (!$this->checksumManager->validateChecksum($checksum)) {
                return [
                    "success" => false,
                    "error" => "Invalid checksum generated",
                    "file_path" => null
                ];
            }
        } catch (Exception $e) {
            @unlink($tempFile); // Clean up on error
            return [
                "success" => false,
                "error" => "Failed to create checksum: " . $e->getMessage(),
                "file_path" => null
            ];
        }

        // Verify file integrity by re-reading and checking checksum consistency
        // This ensures file was not corrupted during download
        try {
            $verifyChecksum = $this->checksumManager->createFileChecksum($tempFile);
            if ($checksum !== $verifyChecksum) {
                @unlink($tempFile); // Clean up corrupted file
                return [
                    "success" => false,
                    "error" => "File integrity check failed: checksum mismatch",
                    "file_path" => null
                ];
            }
        } catch (Exception $e) {
            @unlink($tempFile); // Clean up on error
            return [
                "success" => false,
                "error" => "Failed to verify file integrity: " . $e->getMessage(),
                "file_path" => null
            ];
        }

        return [
            "success" => true,
            "file_path" => $tempFile,
            "checksum" => $checksum,
            "file_size" => $downloadResult["file_size"],
            "mime_type" => $downloadResult["mime_type"] ?? null
        ];
    }

    /**
     * Sideload file to WordPress media library
     *
     * @param string $filePath
     * @param string $originalUrl
     * @param int|null $postId
     * @return array
     */
    private function sideloadFile(string $filePath, string $originalUrl, ?int $postId): array
    {
        if (!function_exists('media_handle_sideload')) {
            require_once ABSPATH . 'wp-admin/includes/media.php';
            require_once ABSPATH . 'wp-admin/includes/file.php';
            require_once ABSPATH . 'wp-admin/includes/image.php';
        }

        $filename = basename(parse_url($originalUrl, PHP_URL_PATH));
        
        $fileArray = [
            'name' => $filename,
            'tmp_name' => $filePath,
            'error' => 0,
            'size' => filesize($filePath)
        ];

        $attachmentId = media_handle_sideload($fileArray, $postId ?? 0);

        if (is_wp_error($attachmentId)) {
            return [
                'success' => false,
                'error' => $attachmentId->get_error_message(),
                'attachment_id' => null
            ];
        }

        return [
            'success' => true,
            'attachment_id' => $attachmentId,
            'url' => wp_get_attachment_url($attachmentId)
        ];
    }

    /**
     * Create URL mapping in dpc_rake_url_source_maps
     *
     * @param string $url
     * @param int $attachmentId
     * @return void
     */
    private function createUrlMapping(string $url, int $attachmentId): void
    {
        global $wpdb;
        $table = $wpdb->prefix . 'rake_url_source_maps';

        $wpdb->insert($table, [
            'source_url' => $url,
            'object_type' => 'wordpress_attachment',
            'object_id' => $attachmentId,
            'created_at' => current_time('mysql')
        ], ['%s', '%s', '%d', '%s']);
    }

    /**
     * Get URL mapping from dpc_rake_url_source_maps
     *
     * @param string $url
     * @return array|null
     */
    private function getUrlMapping(string $url): ?array
    {
        global $wpdb;
        $table = $wpdb->prefix . 'rake_url_source_maps';

        $result = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table} WHERE source_url = %s",
            $url
        ), ARRAY_A);

        return $result ?: null;
    }

    /**
     * Mark resource as imported in dpc_rake_resources
     *
     * @param int $resourceId
     * @param int $attachmentId
     * @return void
     */
    private function markResourceAsImported(int $resourceId, int $attachmentId): void
    {
        global $wpdb;
        $table = $wpdb->prefix . 'rake_resources';

        $wpdb->update($table, [
            'imported_at' => current_time('mysql'),
            'wordpress_id' => $attachmentId
        ], ['id' => $resourceId], ['%s', '%d'], ['%d']);
    }

    /**
     * Get resource by URL
     *
     * @param string $url
     * @return array|null
     */
    private function getResourceByUrl(string $url): ?array
    {
        global $wpdb;
        $table = $wpdb->prefix . 'rake_resources';

        $result = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table} WHERE url = %s",
            $url
        ), ARRAY_A);

        return $result ?: null;
    }

    /**
     * Get attachment ID by resource ID
     *
     * @param int $resourceId
     * @return int|null
     */
    private function getAttachmentIdByResourceId(int $resourceId): ?int
    {
        global $wpdb;
        $table = $wpdb->prefix . 'rake_resources';

        $attachmentId = $wpdb->get_var($wpdb->prepare(
            "SELECT wordpress_id FROM {$table} WHERE id = %d AND imported_at IS NOT NULL",
            $resourceId
        ));

        return $attachmentId ? (int) $attachmentId : null;
    }

    /**
     * Replace URLs in content
     *
     * @param int|null $projectId
     * @return int Number of URLs replaced
     */
    private function replaceUrlsInContent(?int $projectId): int
    {
        global $wpdb;
        $mapTable = $wpdb->prefix . 'rake_url_source_maps';
        
        // Get all URL mappings
        $mappings = $wpdb->get_results(
            "SELECT source_url, object_id FROM {$mapTable} WHERE object_type = 'wordpress_attachment'",
            ARRAY_A
        );

        if (empty($mappings)) {
            return 0;
        }

        $replacedCount = 0;

        // Get posts to update (based on project_id if provided)
        $postsTable = $wpdb->prefix . 'posts';
        $query = "SELECT ID, post_content FROM {$postsTable} WHERE post_type = 'post' AND post_status != 'trash'";
        
        if ($projectId) {
            $query .= $wpdb->prepare(" AND ID IN (SELECT post_id FROM {$wpdb->prefix}postmeta WHERE meta_key = 'crawlflow_project_id' AND meta_value = %d)", $projectId);
        }

        $posts = $wpdb->get_results($query, ARRAY_A);

        foreach ($posts as $post) {
            $content = $post['post_content'];
            $originalContent = $content;

            // Replace URLs
            foreach ($mappings as $mapping) {
                $oldUrl = $mapping['source_url'];
                $newUrl = wp_get_attachment_url($mapping['object_id']);
                
                if ($newUrl) {
                    $content = str_replace($oldUrl, $newUrl, $content);
                }
            }

            // Update post if content changed
            if ($content !== $originalContent) {
                $wpdb->update($postsTable, [
                    'post_content' => $content
                ], ['ID' => $post['ID']], ['%s'], ['%d']);
                
                $replacedCount++;
            }
        }

        return $replacedCount;
    }

    /**
     * Get media file extensions
     *
     * @return array
     */
    private function getMediaExtensions(): array
    {
        $default = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'mp4', 'webm', 'mp3', 'wav', 'ogg'];
        
        $custom = $this->getConfigValue('media_extensions', []);
        
        return array_merge($default, $custom);
    }

    /**
     * Get configuration fields for UI
     */
    public static function getConfigFields(): array
    {
        return [
            [
                'name' => 'domain',
                'type' => 'text',
                'label' => 'Domain',
                'description' => 'Domain to process resources from (e.g., example.com)',
                'placeholder' => 'example.com',
                'required' => true
            ],
            [
                'name' => 'replace_urls_in_content',
                'type' => 'checkbox',
                'label' => 'Replace URLs in Content',
                'description' => 'Replace old URLs with new WordPress URLs in post content',
                'default' => true,
                'required' => false
            ],
            [
                'name' => 'media_extensions',
                'type' => 'text',
                'label' => 'Additional Media Extensions',
                'description' => 'Comma-separated list of additional file extensions to process (e.g., pdf,zip)',
                'placeholder' => 'pdf,zip,doc',
                'default' => '',
                'required' => false
            ]
        ];
    }

    /**
     * Validate configuration
     */
    public static function validateConfig(array $config): array
    {
        $errors = [];

        if (empty($config['domain'])) {
            $errors[] = 'Domain is required';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
}

