<?php

namespace Rake\Actions;

use Rake\Actions\ActionContext;
use Rake\Actions\ActionResult;

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
     * Cache worker priorities by URL to avoid repeated queries
     */
    private static $workerPriorityCache = [];
    
    private $httpClient;

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

            // Get workers from flow config
            $workers = $this->getWorkersFromFlowConfig($flowConfig);

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
                        $sitemapResult = $this->checkSitemapForNewUrls($projectId, $source, $flowConfig, $workers);
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
    private function checkSitemapForNewUrls(int $projectId, array $source, array $flowConfig, array $workers): array
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
                if ($this->saveNewUrl($projectId, $url, $flowConfig, $workers)) {
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

        $sourcesTable = $wpdb->prefix . 'rake_data_sources';
        $archivePages = $wpdb->get_results($wpdb->prepare(
            "SELECT o.id, o.guid, o.metadata FROM {$originsTable} o
             LEFT JOIN {$sourcesTable} s ON o.source_id = s.id
             WHERE o.is_archive = 1
             AND s.tooth_id = %d
             AND o.guid IS NOT NULL
             AND o.guid != ''
             ORDER BY o.id ASC",
            $projectId
        ), ARRAY_A);

        if (empty($archivePages)) {
            $this->log('No archive pages found for project', ['project_id' => $projectId]);
            return $result;
        }

        // Get workers from flow config to detect URLs
        $workers = $this->getWorkersFromFlowConfig($flowConfig);
        if (empty($workers)) {
            $this->log('No workers found in flow config', ['project_id' => $projectId]);
            return $result;
        }

        $allDetectedUrls = [];

        // Process each archive page and detect URLs based on worker rules
        foreach ($archivePages as $archivePage) {
            $pageUrl = $archivePage['guid'];
            $metadata = json_decode($archivePage['metadata'] ?? '{}', true);
            
            // Add page_url and project_id to metadata for logging
            $metadata['page_url'] = $pageUrl;
            $metadata['project_id'] = $projectId;
            
            $this->log('Processing archive page', [
                'project_id' => $projectId,
                'page_url' => $pageUrl
            ]);

            try {
                // Fetch archive page content
                $httpClient = new \Puleeno\Rake\WordPress\Http\WordPressHttpClient();
                $response = $httpClient->get($pageUrl);
                
                if (!$response->isSuccessful()) {
                    $result['errors'][] = "Failed to fetch archive page: {$pageUrl}";
                    continue;
                }

                $htmlContent = $response->getBody();
                
                // Detect URLs based on worker rules
                $detectedUrls = $this->detectUrlsFromArchivePage($htmlContent, $workers, $metadata);
                $allDetectedUrls = array_merge($allDetectedUrls, $detectedUrls);
                
                $this->log('Detected URLs from archive page', [
                    'project_id' => $projectId,
                    'page_url' => $pageUrl,
                    'urls_detected_count' => count($detectedUrls),
                    'total_urls_so_far' => count($allDetectedUrls)
                ]);
                
            } catch (\Exception $e) {
                $result['errors'][] = "Error processing archive page {$pageUrl}: " . $e->getMessage();
                $this->logError('Error processing archive page', [
                    'project_id' => $projectId,
                    'page_url' => $pageUrl,
                    'error' => $e->getMessage()
                ]);
            }
        }

        // Remove duplicates using array_unique
        $uniqueUrls = array_unique($allDetectedUrls);
        $result['urls_found'] = count($uniqueUrls);

        $this->log('Detected URLs from archive pages', [
            'project_id' => $projectId,
            'total_detected' => count($allDetectedUrls),
            'unique_urls' => count($uniqueUrls)
        ]);

        // Check each unique URL if it exists in database
        $newUrlsCount = 0;
        foreach ($uniqueUrls as $url) {
            if ($this->saveNewUrl($projectId, $url, $flowConfig, $workers)) {
                $newUrlsCount++;
                $this->log('Inserted new URL', [
                    'project_id' => $projectId,
                    'url' => $url
                ]);
            }
        }
        
        $this->log('URL processing completed', [
            'project_id' => $projectId,
            'unique_urls_checked' => count($uniqueUrls),
            'new_urls_added' => $newUrlsCount,
            'existing_urls_skipped' => count($uniqueUrls) - $newUrlsCount
        ]);

        return $result;
    }

    /**
     * Get workers from flow config
     */
    private function getWorkersFromFlowConfig(array $flowConfig): array
    {
        $workers = [];
        $nodes = $flowConfig['nodes'] ?? [];
        
        foreach ($nodes as $node) {
            if ($node['type'] === 'worker') {
                $nodeData = $node['data'] ?? [];
                if ($nodeData['enabled'] ?? true) {
                    // Add worker ID for logging
                    $nodeData['id'] = $node['id'] ?? 'unknown';
                    $workers[] = $nodeData;
                }
            }
        }
        
        $this->log('Loaded workers from flow config', [
            'total_workers' => count($workers),
            'worker_ids' => array_column($workers, 'id'),
            'worker_details' => $workers
        ]);
        
        return $workers;
    }

    /**
     * Select appropriate worker based on URL type we expect to find
     */
    private function selectWorkerForUrlType(array $workers, array $archiveMetadata): ?array
    {
        $pageUrl = $archiveMetadata['page_url'] ?? '';
        
        // If we're on a category page (products_list), we expect to find product URLs
        if (strpos($pageUrl, '/products_list/') !== false) {
            // Look for worker that handles product URLs
            foreach ($workers as $worker) {
                $detectionRules = $worker['detectionRules'] ?? [];
                foreach ($detectionRules as $rule) {
                    if ($rule['type'] === 'url-format') {
                        $pattern = $rule['pattern'] ?? $rule['value'] ?? '';
                        // Look for product URL pattern
                        if (strpos($pattern, '/products_detail/') !== false) {
                            $this->log('Selected worker for product URLs', [
                                'worker_id' => $worker['id'] ?? 'unknown',
                                'pattern' => $pattern
                            ]);
                            return $worker;
                        }
                    }
                }
            }
        }
        
        // Fallback: return first worker that can handle archive pages
        foreach ($workers as $worker) {
            if ($worker['isArchive'] ?? false) {
                $this->log('Using fallback archive worker', [
                    'worker_id' => $worker['id'] ?? 'unknown'
                ]);
                return $worker;
            }
        }
        
        return null;
    }

    /**
     * Convert CSS selector to XPath
     * 
     * @param string $cssSelector
     * @return string
     */
    private function cssToXPath(string $cssSelector): string
    {
        // Handle multiple classes in one selector like .con.fr.clean
        if (preg_match('/^\.([a-zA-Z0-9_-]+)(\.[a-zA-Z0-9_-]+)+$/', $cssSelector, $matches)) {
            // Extract all classes
            $classes = explode('.', ltrim($cssSelector, '.'));
            $classConditions = [];
            foreach ($classes as $class) {
                $classConditions[] = 'contains(concat(" ", normalize-space(@class), " "), " ' . $class . ' ")';
            }
            return '//*[' . implode(' and ', $classConditions) . ']';
        }
        
        // Handle basic CSS selectors
        $xpath = $cssSelector;
        
        // Convert class selectors (.class) to XPath
        $xpath = preg_replace('/\.([a-zA-Z0-9_-]+)/', '[@class and contains(concat(" ", normalize-space(@class), " "), " $1 ")]', $xpath);
        
        // Convert ID selectors (#id) to XPath
        $xpath = preg_replace('/#([a-zA-Z0-9_-]+)/', '[@id="$1"]', $xpath);
        
        // Convert element selectors (tag) to XPath
        $xpath = preg_replace('/^([a-zA-Z][a-zA-Z0-9]*)/', '$1', $xpath);
        
        // Handle descendant selectors (space)
        $xpath = preg_replace('/\s+/', '//', $xpath);
        
        // Handle child selectors (>)
        $xpath = preg_replace('/\s*>\s*/', '/', $xpath);
        
        // If the selector doesn't start with // or ., add // for global search
        if (!preg_match('/^\/\/|^\./', $xpath)) {
            $xpath = '//' . $xpath;
        }
        
        return $xpath;
    }

    /**
     * Query DOM elements with both CSS and XPath support
     * 
     * @param DOMXPath $xpath
     * @param string $selector
     * @param DOMNode $context
     * @return DOMNodeList|false
     */
    private function queryElements(\DOMXPath $xpath, string $selector, ?\DOMNode $context = null)
    {
        // Try as XPath first
        $nodes = $xpath->query($selector, $context);
        
        // If XPath fails and it looks like CSS selector, try converting
        if ($nodes === false && preg_match('/[.#]/', $selector)) {
            $xpathSelector = $this->cssToXPath($selector);
            $this->log('Converting CSS selector to XPath', [
                'original_selector' => $selector,
                'converted_xpath' => $xpathSelector
            ]);
            $nodes = $xpath->query($xpathSelector, $context);
        }
        
        return $nodes;
    }

    /**
     * Detect URLs from archive page using all workers
     */
    private function detectUrlsFromArchivePage(string $htmlContent, array $workers, array $archiveMetadata): array
    {
        $detectedUrls = [];
        
        // Create DOM document for parsing
        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML($htmlContent);
        libxml_clear_errors();
        
        $xpath = new \DOMXPath($dom);
        
        $this->log('Starting URL detection with all workers', [
            'project_id' => $archiveMetadata['project_id'] ?? 'unknown',
            'page_url' => $archiveMetadata['page_url'] ?? 'unknown',
            'total_workers' => count($workers)
        ]);
        
        // Phase 1: Extract URLs using workers with wrapper selectors
        $extractedUrls = [];
        foreach ($workers as $worker) {
            $wrapperSelector = $worker['archiveProductsWrapper'] ?? null;
            
            if (empty($wrapperSelector)) {
                $this->log('Worker has no wrapper selector, skipping URL extraction', [
                    'project_id' => $archiveMetadata['project_id'] ?? 'unknown',
                    'worker_id' => $worker['id'] ?? 'unknown'
                ]);
                continue;
            }
            
            $this->log('Extracting URLs with worker', [
                'project_id' => $archiveMetadata['project_id'] ?? 'unknown',
                'worker_id' => $worker['id'] ?? 'unknown',
                'wrapper_selector' => $wrapperSelector
            ]);
            
            // Find wrapper elements using both CSS and XPath support
            $wrapperNodes = $this->queryElements($xpath, $wrapperSelector);
            
            if ($wrapperNodes === false) {
                $this->log('XPath query failed completely', [
                    'project_id' => $archiveMetadata['project_id'] ?? 'unknown',
                    'wrapper_selector' => $wrapperSelector
                ]);
                continue;
            }
            
            if ($wrapperNodes->length === 0) {
                $this->log('No wrapper elements found', [
                    'project_id' => $archiveMetadata['project_id'] ?? 'unknown',
                    'wrapper_selector' => $wrapperSelector
                ]);
                continue;
            }
            
            $this->log('Found product containers', [
                'project_id' => $archiveMetadata['project_id'] ?? 'unknown',
                'page_url' => $archiveMetadata['page_url'] ?? 'unknown',
                'product_containers_found' => $wrapperNodes->length,
                'worker_id' => $worker['id'] ?? 'unknown'
            ]);
            
            // Find all links within wrapper elements
            foreach ($wrapperNodes as $wrapperNode) {
                $links = $wrapperNode->getElementsByTagName('a');
                foreach ($links as $link) {
                    $href = $link->getAttribute('href');
                    if (!empty($href)) {
                        $url = $this->makeAbsoluteUrl($href, $archiveMetadata['base_url'] ?? '', $archiveMetadata['page_url'] ?? '');
                        
                        // Filter URLs based on archiveUrlContains rule
                        if (!empty($url)) {
                            $urlContains = $worker['archiveUrlContains'] ?? null;
                            if (empty($urlContains) || strpos($url, $urlContains) !== false) {
                                $extractedUrls[] = $url;
                            }
                        }
                    }
                }
            }
        }
        
        $this->log('URL extraction completed', [
            'project_id' => $archiveMetadata['project_id'] ?? 'unknown',
            'page_url' => $archiveMetadata['page_url'] ?? 'unknown',
            'extracted_urls_count' => count($extractedUrls)
        ]);
        
        // Phase 2: Validate extracted URLs against ALL workers
        foreach ($extractedUrls as $url) {
            $this->log('Validating extracted URL against all workers', [
                'project_id' => $archiveMetadata['project_id'] ?? 'unknown',
                'url' => $url,
                'total_workers_for_validation' => count($workers)
            ]);
            
            $urlMatchesAnyWorker = false;
            
            foreach ($workers as $worker) {
                $this->log('Checking URL against worker rules', [
                    'url' => $url,
                    'worker_id' => $worker['id'] ?? 'unknown',
                    'detection_logic' => $worker['detectionLogic'] ?? 'and',
                    'detection_rules' => $worker['detectionRules'] ?? []
                ]);
                
                if ($this->urlMatchesWorkerRules($url, $worker)) {
                    $urlMatchesAnyWorker = true;
                    $this->log('URL matches worker rules', [
                        'url' => $url,
                        'worker_id' => $worker['id'] ?? 'unknown'
                    ]);
                    break; // URL matches at least one worker, no need to check others
                }
            }
            
            if ($urlMatchesAnyWorker) {
                $detectedUrls[] = $url;
                $this->log('URL added to detected list', [
                    'project_id' => $archiveMetadata['project_id'] ?? 'unknown',
                    'url' => $url
                ]);
            } else {
                $this->log('URL rejected - no worker matches', [
                    'project_id' => $archiveMetadata['project_id'] ?? 'unknown',
                    'url' => $url
                ]);
            }
        }
        
        return $detectedUrls;
    }

    /**
     * Check if URL matches worker rules
     */
    private function urlMatchesWorkerRules(string $url, array $worker): bool
    {
        $detectionRules = $worker['detectionRules'] ?? [];
        $detectionLogic = $worker['detectionLogic'] ?? 'and';
        
        $this->log('Checking URL against worker rules', [
            'url' => $url,
            'worker_id' => $worker['id'] ?? 'unknown',
            'detection_logic' => $detectionLogic,
            'detection_rules' => $detectionRules
        ]);
        
        if (empty($detectionRules)) {
            $this->log('No detection rules found, accepting URL', ['url' => $url]);
            return true; // No rules means accept all URLs
        }
        
        $matchedRules = 0;
        
        foreach ($detectionRules as $rule) {
            $ruleType = $rule['type'] ?? '';
            $ruleValue = $rule['pattern'] ?? $rule['value'] ?? ''; // Use pattern first, then value as fallback
            
            $matches = false;
            
            switch ($ruleType) {
                case 'url-format':
                    $pattern = str_replace('*', '.*', preg_quote($ruleValue, '/'));
                    // Remove the ^ and $ anchors to match anywhere in the URL
                    $matches = preg_match("/{$pattern}/i", $url);
                    $this->log('URL format rule check', [
                        'url' => $url,
                        'rule_value' => $ruleValue,
                        'pattern' => $pattern,
                        'matches' => (bool)$matches
                    ]);
                    break;
                    
                case 'html-contains':
                    // This would require fetching the URL content, skip for now
                    $matches = true; // Assume it matches for archive page detection
                    $this->log('HTML contains rule check (assumed match)', [
                        'url' => $url,
                        'rule_value' => $ruleValue
                    ]);
                    break;
                    
                case 'dom-value':
                case 'tag-attribute':
                    // These are for content extraction, not URL detection
                    $matches = true;
                    $this->log('DOM rule check (assumed match)', [
                        'url' => $url,
                        'rule_type' => $ruleType,
                        'rule_value' => $ruleValue
                    ]);
                    break;
            }
            
            if ($matches) {
                $matchedRules++;
            }
            
            // For OR logic, if any rule matches, return true immediately
            if ($detectionLogic === 'or' && $matches) {
                $this->log('URL matches worker rules (OR logic)', [
                    'url' => $url,
                    'matched_rules' => $matchedRules,
                    'total_rules' => count($detectionRules)
                ]);
                return true;
            }
        }
        
        // For AND logic, all rules must match
        // For OR logic, at least one rule must match (handled above)
        $result = $detectionLogic === 'and' && $matchedRules === count($detectionRules);
        
        $this->log('URL worker rules check result', [
            'url' => $url,
            'detection_logic' => $detectionLogic,
            'matched_rules' => $matchedRules,
            'total_rules' => count($detectionRules),
            'final_result' => $result
        ]);
        
        return $result;
    }

    /**
     * Make URL absolute
     */
    private function makeAbsoluteUrl(string $url, string $baseUrl = '', string $pageUrl = ''): string
    {
        if (strpos($url, 'http') === 0) {
            return $url; // Already absolute
        }
        
        // Try to extract base URL from pageUrl if baseUrl is empty
        if (empty($baseUrl) && !empty($pageUrl)) {
            $parsedPageUrl = parse_url($pageUrl);
            if (isset($parsedPageUrl['scheme']) && isset($parsedPageUrl['host'])) {
                $baseUrl = $parsedPageUrl['scheme'] . '://' . $parsedPageUrl['host'];
            }
        }
        
        if (empty($baseUrl)) {
            return $url; // Can't make absolute without base URL
        }
        
        return rtrim($baseUrl, '/') . '/' . ltrim($url, '/');
    }

    /**
     * Extract URLs from sitemap XML content
     * 
     * @param string $xmlContent
     * @param string $sitemapUrl
     * @return array
     */
    private function extractUrlsFromSitemap(string $xmlContent, string $sitemapUrl): array
    {
        $urls = [];
        
        try {
            $xml = simplexml_load_string($xmlContent);
            
            if ($xml === false) {
                $this->logError('Failed to parse sitemap XML', ['sitemap_url' => $sitemapUrl]);
                return $urls;
            }
            
            // Handle both sitemap index and urlset formats
            if (isset($xml->url)) {
                // Standard sitemap with URLs
                foreach ($xml->url as $url) {
                    if (isset($url->loc)) {
                        $urls[] = (string)$url->loc;
                    }
                }
            } elseif (isset($xml->sitemap)) {
                // Sitemap index - this would need recursive fetching
                foreach ($xml->sitemap as $sitemap) {
                    if (isset($sitemap->loc)) {
                        $urls[] = (string)$sitemap->loc;
                    }
                }
            }
            
        } catch (\Exception $e) {
            $this->logError('Error parsing sitemap XML', [
                'sitemap_url' => $sitemapUrl,
                'error' => $e->getMessage()
            ]);
        }
        
        return $urls;
    }

    /**
     * Save new URL if it doesn't exist
     * 
     * @param int $projectId
     * @param string $url
     * @param array $flowConfig
     * @return bool
     */
    private function saveNewUrl(int $projectId, string $url, array $flowConfig, array $workers): bool
    {
        global $wpdb;
        $originsTable = $wpdb->prefix . 'rake_data_origins';
        
        try {
            // Check if URL already exists
            $existing = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$originsTable} WHERE guid = %s",
                $url
            ));
            
            $this->log('Checking if URL exists in database', [
                'project_id' => $projectId,
                'url' => $url,
                'url_exists' => !empty($existing),
                'existing_id' => $existing
            ]);
            
            if ($existing) {
                $this->log('URL already exists, skipping', [
                    'project_id' => $projectId,
                    'url' => $url,
                    'existing_id' => $existing
                ]);
                return false; // URL already exists
            }
            
            // Get or create source
            $sourcesTable = $wpdb->prefix . 'rake_data_sources';
            $sourceId = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$sourcesTable} WHERE tooth_id = %d LIMIT 1",
                $projectId
            ));
            
            if (empty($sourceId)) {
                // Create source if not exists
                $baseUrl = $flowConfig['projectSettings']['base_url'] ?? '';
                $wpdb->insert($sourcesTable, [
                    'tooth_id' => $projectId,
                    'type' => 'url',
                    'name' => 'Sitemap Source',
                    'config' => json_encode(['url' => $baseUrl]),
                    'created_at' => current_time('mysql'),
                ]);
                $sourceId = $wpdb->insert_id;
            }
            
            // Insert new URL
            // Get worker priority from cache or calculate
            if (isset(self::$workerPriorityCache[$url])) {
                $workerPriority = self::$workerPriorityCache[$url];
            } else {
                $workerPriority = 100; // Default priority
                foreach ($workers as $worker) {
                    if ($this->urlMatchesWorkerRules($url, $worker)) {
                        $workerPriority = $worker['priority'] ?? 100;
                        break;
                    }
                }
                // Cache the result
                self::$workerPriorityCache[$url] = $workerPriority;
            }
            
            $metadata = [
                'project_id' => $projectId,
                'detected_by' => 'data_update_checker',
                'source_type' => 'sitemap',
                'worker_priority' => $workerPriority,
                'created_at' => current_time('mysql')
            ];
            
            $wpdb->insert($originsTable, [
                'source_id' => $sourceId,
                'guid' => $url,
                'source_type' => 'sitemap',
                'priority' => $workerPriority,
                'crawled' => 0,
                'ignored' => 0,
                'is_archive' => 0,
                'metadata' => json_encode($metadata),
                'created_at' => current_time('mysql')
            ]);
            
            return $wpdb->insert_id > 0;
            
        } catch (\Exception $e) {
            $this->logError('Error saving new URL', [
                'project_id' => $projectId,
                'url' => $url,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}
