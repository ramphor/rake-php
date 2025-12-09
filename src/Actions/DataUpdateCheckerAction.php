<?php

namespace Rake\Actions;

/**
 * Data Update Checker Action
 * 
 * Bonus Phase action that checks for new data from data sources:
 * - For XML sitemap sources: Re-fetch sitemap to find new URLs
 * - For URL sources with is_archive=1: Fetch from archive pages to find new URLs (not existing in database)
 * 
 * This action runs at the end of Phase 1 to discover new items that may have been added.
 */
class DataUpdateCheckerAction extends AbstractContextAction
{
    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct(
            'data_update_checker',
            'Data Update Checker',
            'Check data sources for new items (sitemap updates, archive page new URLs)',
            ['phase1'],
            20 // Lower priority than other actions, runs after main Phase 1 work
        );
    }

    /**
     * Execute action
     * 
     * @param ActionContext $context
     * @return ActionResult
     */
    public function execute(ActionContext $context): ActionResult
    {
        $projectId = $context->getProjectId();
        $flowConfig = $context->getFlowConfig();

        try {
            $this->log('Starting check for new data', [
                'project_id' => $projectId,
            ]);

            $results = [
                'sitemap_urls_found' => 0,
                'archive_urls_found' => 0,
                'urls_saved' => 0,
                'errors' => [],
            ];

            // Get data sources from flow config
            $dataSources = $this->getDataSourcesFromFlowConfig($flowConfig);

            foreach ($dataSources as $source) {
                $sourceType = $source['type'] ?? 'url';
                
                try {
                    if ($sourceType === 'sitemap') {
                        // Check sitemap for new URLs
                        $sitemapResult = $this->checkSitemapForNewUrls($projectId, $source, $flowConfig);
                        $results['sitemap_urls_found'] += $sitemapResult['urls_found'];
                        $results['urls_saved'] += $sitemapResult['urls_saved'];
                        if (!empty($sitemapResult['errors'])) {
                            $results['errors'] = array_merge($results['errors'], $sitemapResult['errors']);
                        }
                    } elseif ($sourceType === 'url') {
                        // Check archive pages for new URLs
                        $archiveResult = $this->checkArchivePagesForNewUrls($projectId, $source, $flowConfig);
                        $results['archive_urls_found'] += $archiveResult['urls_found'];
                        $results['urls_saved'] += $archiveResult['urls_saved'];
                        if (!empty($archiveResult['errors'])) {
                            $results['errors'] = array_merge($results['errors'], $archiveResult['errors']);
                        }
                    }
                } catch (\Exception $e) {
                    $results['errors'][] = "Error processing source {$sourceType}: " . $e->getMessage();
                    $this->logError('Error processing source', [
                        'project_id' => $projectId,
                        'source_type' => $sourceType,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $this->log('Completed check for new data', [
                'project_id' => $projectId,
                'results' => $results,
            ]);

            return ActionResult::success($results, [
                'total_urls_found' => $results['sitemap_urls_found'] + $results['archive_urls_found'],
            ]);

        } catch (\Exception $e) {
            $this->logError('Failed to check for new data', [
                'project_id' => $projectId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return ActionResult::error(
                $e->getMessage(),
                ['project_id' => $projectId],
                ['exception' => get_class($e)]
            );
        }
    }

    /**
     * Get data sources from flow config
     * 
     * @param array $flowConfig
     * @return array
     */
    private function getDataSourcesFromFlowConfig(array $flowConfig): array
    {
        $sources = [];
        $nodes = $flowConfig['nodes'] ?? [];

        foreach ($nodes as $node) {
            if ($node['type'] === 'dataSource' || $node['type'] === 'start') {
                $nodeData = $node['data'] ?? [];
                $sourceType = $nodeData['sourceType'] ?? 'url';
                
                // Check for URL source
                $sourceUrl = $nodeData['sourceValue'] ?? $nodeData['url'] ?? null;
                if (empty($sourceUrl) && isset($nodeData['sourceType']) && $nodeData['sourceType'] === 'url') {
                    $sourceUrl = $nodeData['sourceValue'] ?? null;
                }
                
                if (!empty($sourceUrl)) {
                    $sources[] = [
                        'type' => $sourceType,
                        'url' => $sourceUrl,
                        'config' => $nodeData,
                    ];
                }
            }
        }

        return $sources;
    }

    /**
     * Check sitemap for new URLs
     * 
     * @param int $projectId
     * @param array $source
     * @param array $flowConfig
     * @return array
     */
    private function checkSitemapForNewUrls(int $projectId, array $source, array $flowConfig): array
    {
        $result = [
            'urls_found' => 0,
            'urls_saved' => 0,
            'errors' => [],
        ];

        $sitemapUrl = $source['url'] ?? null;
        if (empty($sitemapUrl)) {
            return $result;
        }

        try {
            // Fetch sitemap using HttpDataSource
            $httpClient = new \CrawlFlow\DataSources\HttpDataSource();
            $response = $httpClient->fetch($sitemapUrl);
            
            if (isset($response['status_code']) && $response['status_code'] === 200) {
                $xmlContent = $response['body'] ?? '';
                $urls = $this->extractUrlsFromSitemap($xmlContent, $sitemapUrl);
            } else {
                $statusCode = $response['status_code'] ?? 'unknown';
                $result['errors'][] = "Failed to fetch sitemap: HTTP {$statusCode}";
                return $result;
            }

            $this->log('Extracted URLs from sitemap', [
                'project_id' => $projectId,
                'sitemap_url' => $sitemapUrl,
                'urls_count' => count($urls),
            ]);

            // Save only new URLs (not existing in database)
            foreach ($urls as $url) {
                if ($this->saveNewUrl($projectId, $url, $flowConfig)) {
                    $result['urls_saved']++;
                }
                $result['urls_found']++;
            }

        } catch (\Exception $e) {
            $result['errors'][] = "Error checking sitemap: " . $e->getMessage();
        }

        return $result;
    }

    /**
     * Check archive pages for new URLs
     * 
     * @param int $projectId
     * @param array $source
     * @param array $flowConfig
     * @return array
     */
    private function checkArchivePagesForNewUrls(int $projectId, array $source, array $flowConfig): array
    {
        $result = [
            'urls_found' => 0,
            'urls_saved' => 0,
            'errors' => [],
        ];

        global $wpdb;
        $originsTable = $wpdb->prefix . 'rake_data_origins';

        // Get all archive pages (is_archive = 1) for this project
        // Since there's no project_id column, we'll get all archive pages
        // and filter by checking if they're related to this project's data sources
        $archivePages = $wpdb->get_results(
            "SELECT id, guid, metadata FROM {$originsTable} 
            WHERE is_archive = 1 
            AND guid IS NOT NULL 
            AND guid != ''
            ORDER BY id ASC",
            ARRAY_A
        );
        
        // Filter archive pages by project (check metadata)
        // Archive pages should have project_id in metadata (set when is_archive flag is set in Phase 2)
        $filteredArchivePages = [];
        foreach ($archivePages as $page) {
            $metadata = json_decode($page['metadata'] ?? '{}', true);
            // Check if page belongs to this project via metadata
            if (isset($metadata['project_id']) && (int)$metadata['project_id'] === $projectId) {
                $filteredArchivePages[] = $page;
            }
        }
        
        // If no filtered results, try to get archive pages by checking source_id
        if (empty($filteredArchivePages)) {
            $sourcesTable = $wpdb->prefix . 'rake_data_sources';
            $projectSources = $wpdb->get_col($wpdb->prepare(
                "SELECT id FROM {$sourcesTable} WHERE tooth_id = %d",
                $projectId
            ));
            
            if (!empty($projectSources)) {
                $sourceIds = implode(',', array_map('intval', $projectSources));
                $archivePages = $wpdb->get_results(
                    "SELECT id, guid, metadata FROM {$originsTable} 
                    WHERE is_archive = 1 
                    AND guid IS NOT NULL 
                    AND guid != ''
                    AND source_id IN ({$sourceIds})
                    ORDER BY id ASC",
                    ARRAY_A
                );
            } else {
                $archivePages = [];
            }
        } else {
            $archivePages = $filteredArchivePages;
        }

        if (empty($archivePages)) {
            $this->log('No archive pages found', [
                'project_id' => $projectId,
            ]);
            return $result;
        }

        $this->log('Found archive pages to check', [
            'project_id' => $projectId,
            'archive_pages_count' => count($archivePages),
        ]);

        $httpClient = new \CrawlFlow\DataSources\HttpDataSource();

        foreach ($archivePages as $archivePage) {
            try {
                $archiveUrl = $archivePage['guid'];
                
                // Fetch archive page HTML
                $response = $httpClient->fetch($archiveUrl);
                
                if (isset($response['status_code']) && $response['status_code'] === 200) {
                    $htmlContent = $response['body'] ?? '';
                    
                    // Use UrlDataSourceHandler to extract URLs with proper settings
                    if (class_exists('\CrawlFlow\Cron\Phase1\UrlDataSourceHandler')) {
                        $handler = new \CrawlFlow\Cron\Phase1\UrlDataSourceHandler();
                        
                        // Get URL settings from source config (if available)
                        // Use default settings for archive pages
                        $urlSettings = [
                            'scope' => 'current-url',
                            'excludeExtensions' => [],
                            'excludePatterns' => [],
                            'whitelistPatterns' => [],
                            'domainPolicy' => 'all',
                            'domainWhitelist' => [],
                        ];
                        
                        // Use reflection to call protected extractUrls method
                        $reflection = new \ReflectionClass($handler);
                        $method = $reflection->getMethod('extractUrls');
                        $method->setAccessible(true);
                        $urls = $method->invoke($handler, $htmlContent, $archiveUrl, $urlSettings);
                    } else {
                        // Fallback: simple extraction
                        $urls = $this->extractUrlsFromHtml($htmlContent, $archiveUrl);
                    }
                } else {
                    $statusCode = $response['status_code'] ?? 'unknown';
                    $result['errors'][] = "Failed to fetch archive page {$archiveUrl}: HTTP {$statusCode}";
                    continue;
                }

                $this->log('Extracted URLs from archive page', [
                    'project_id' => $projectId,
                    'archive_url' => $archiveUrl,
                    'urls_count' => count($urls),
                ]);

                // Save only new URLs (not existing in database)
                foreach ($urls as $url) {
                    if ($this->saveNewUrl($projectId, $url, $flowConfig)) {
                        $result['urls_saved']++;
                    }
                    $result['urls_found']++;
                }

            } catch (\Exception $e) {
                $result['errors'][] = "Error checking archive page {$archivePage['guid']}: " . $e->getMessage();
            }
        }

        return $result;
    }

    /**
     * Extract URLs from sitemap XML
     * Handles both sitemap index and regular sitemap
     * 
     * @param string $xmlContent
     * @param string $baseUrl
     * @return array
     */
    private function extractUrlsFromSitemap(string $xmlContent, string $baseUrl): array
    {
        $urls = [];
        
        libxml_use_internal_errors(true);
        $xml = @simplexml_load_string($xmlContent);
        
        if ($xml === false) {
            libxml_clear_errors();
            return $urls;
        }

        // Get sitemap namespace
        $namespaces = $xml->getNamespaces(true);
        $sitemapNs = 'http://www.sitemaps.org/schemas/sitemap/0.9';
        foreach ($namespaces as $prefix => $ns) {
            if (strpos($ns, 'sitemap') !== false) {
                $sitemapNs = $ns;
                break;
            }
        }

        // Handle sitemap index (contains <sitemap> elements)
        if ($xml->getName() === 'sitemapindex' || isset($xml->sitemap)) {
            foreach ($xml->sitemap as $sitemap) {
                $loc = (string)($sitemap->loc ?? '');
                if (!empty($loc)) {
                    $urls[] = $this->normalizeUrl($loc, $baseUrl);
                }
            }
            
            // Also try with namespace
            if (empty($urls) && isset($xml->children($sitemapNs)->sitemap)) {
                foreach ($xml->children($sitemapNs)->sitemap as $sitemap) {
                    $loc = (string)($sitemap->loc ?? '');
                    if (!empty($loc)) {
                        $urls[] = $this->normalizeUrl($loc, $baseUrl);
                    }
                }
            }
        }

        // Handle regular sitemap (contains <url> elements)
        if ($xml->getName() === 'urlset' || isset($xml->url)) {
            foreach ($xml->url as $url) {
                $loc = (string)($url->loc ?? '');
                if (!empty($loc)) {
                    $urls[] = $this->normalizeUrl($loc, $baseUrl);
                }
            }
            
            // Also try with namespace
            if (empty($urls) && isset($xml->children($sitemapNs)->url)) {
                foreach ($xml->children($sitemapNs)->url as $url) {
                    $loc = (string)($url->loc ?? '');
                    if (!empty($loc)) {
                        $urls[] = $this->normalizeUrl($loc, $baseUrl);
                    }
                }
            }
        }

        libxml_clear_errors();
        return array_unique($urls);
    }

    /**
     * Extract URLs from HTML
     * 
     * @param string $htmlContent
     * @param string $baseUrl
     * @return array
     */
    private function extractUrlsFromHtml(string $htmlContent, string $baseUrl): array
    {
        $urls = [];
        
        // Use DOMDocument to extract URLs
        libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        @$dom->loadHTML($htmlContent);
        libxml_clear_errors();

        $xpath = new \DOMXPath($dom);
        
        // Extract all href attributes from <a> tags
        $links = $xpath->query('//a[@href]');
        
        foreach ($links as $link) {
            $href = $link->getAttribute('href');
            if (!empty($href)) {
                $absoluteUrl = $this->normalizeUrl($href, $baseUrl);
                if (!empty($absoluteUrl) && filter_var($absoluteUrl, FILTER_VALIDATE_URL)) {
                    $urls[] = $absoluteUrl;
                }
            }
        }

        return array_unique($urls);
    }

    /**
     * Normalize URL to absolute
     * 
     * @param string $url
     * @param string $baseUrl
     * @return string
     */
    private function normalizeUrl(string $url, string $baseUrl): string
    {
        if (empty($url)) {
            return '';
        }

        // If already absolute, return as is
        if (preg_match('/^https?:\/\//', $url)) {
            return $url;
        }

        // Parse base URL
        $baseParts = parse_url($baseUrl);
        if (!$baseParts) {
            return '';
        }

        $scheme = $baseParts['scheme'] ?? 'http';
        $host = $baseParts['host'] ?? '';
        $basePath = $baseParts['path'] ?? '/';

        // Handle relative URLs
        if (strpos($url, '/') === 0) {
            // Absolute path
            return $scheme . '://' . $host . $url;
        } else {
            // Relative path
            $baseDir = dirname($basePath);
            if ($baseDir === '.') {
                $baseDir = '/';
            }
            return $scheme . '://' . $host . $baseDir . '/' . ltrim($url, '/');
        }
    }

    /**
     * Save new URL if it doesn't exist
     * 
     * @param int $projectId
     * @param string $url
     * @param array $flowConfig
     * @return bool True if saved, false if already exists
     */
    private function saveNewUrl(int $projectId, string $url, array $flowConfig): bool
    {
        global $wpdb;
        $originsTable = $wpdb->prefix . 'rake_data_origins';

        // Check if URL already exists
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$originsTable} WHERE guid = %s",
            $url
        ));

        if ($existing) {
            return false; // Already exists
        }

        // Detect worker priority
        $priority = 100; // Default
        if (!empty($url) && filter_var($url, FILTER_VALIDATE_URL)) {
            $priority = $this->detectWorkerPriority($projectId, $flowConfig, $url);
        }

        // Insert new URL with metadata
        $now = current_time('mysql');
        $metadata = [
            'project_id' => $projectId,
            'source' => 'data_update_checker',
        ];
        
        $result = $wpdb->insert(
            $originsTable,
            [
                'guid' => $url,
                'raw_data' => '', // Phase 1 does not fetch raw_data
                'fetched_at' => $now, // avoid NULL constraint errors
                'crawled' => 0,
                'ignored' => 0,
                'is_archive' => 0,
                'metadata' => json_encode($metadata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'source_type' => 'bonus_phase',
                'priority' => $priority,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            ['%s', '%s', '%s', '%d', '%d', '%d', '%s', '%s', '%d', '%s', '%s']
        );

        return $result !== false;
    }

    /**
     * Detect worker priority for URL
     * 
     * @param int $projectId
     * @param array $flowConfig
     * @param string $url
     * @return int
     */
    private function detectWorkerPriority(int $projectId, array $flowConfig, string $url): int
    {
        try {
            if (class_exists('\CrawlFlow\Cron\WorkerCacheService')) {
                $workerCacheService = new \CrawlFlow\Cron\WorkerCacheService();
                $reception = $workerCacheService->getReception($projectId, $flowConfig);
                $workers = $reception->getWorkers();
                
                // Create a mock raw item for detection
                $mockRawItem = [
                    'id' => 0,
                    'guid' => $url,
                    'raw_data' => '',
                ];
                
                // Check each worker (already sorted by priority)
                foreach ($workers as $worker) {
                    if ($worker->canHandle($mockRawItem)) {
                        return $worker->getPriority();
                    }
                }
            }
        } catch (\Exception $e) {
            $this->log('Error detecting worker priority', [
                'project_id' => $projectId,
                'url' => $url,
                'error' => $e->getMessage(),
            ]);
        }
        
        // Default priority if no worker matches
        return 100;
    }

}

