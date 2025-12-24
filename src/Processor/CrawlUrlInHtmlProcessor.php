<?php

namespace Rake\Processor;

use Rake\Contracts\Entities\ParsedDataItemInterface;

/**
 * Crawl URLs in HTML Processor
 * Scans all URLs in <a> tags and inserts them into wpd4_rake_data_origins table
 * following data source settings with duplicate checking based on guid and source_id
 */
class CrawlUrlInHtmlProcessor extends AbstractProcessor
{
    /**
     * Process HTML content and extract URLs from <a> tags
     * 
     * @param ParsedDataItemInterface $item Input data item containing HTML content
     * @return ParsedDataItemInterface Processed item with extracted URLs or NullDataItem if failed
     */
    public function process(ParsedDataItemInterface $item): ParsedDataItemInterface
    {
        // Skip if already null item
        if ($item->isNull()) {
            return $item;
        }

        // Get source_id from item metadata or data
        $sourceId = $item->get('source_id') ?? $item->getMetadata('source_id');
        
        if (!$sourceId) {
            return $this->createNullItem('source_id is required for CrawlUrlInHtmlProcessor');
        }

        // Get HTML content
        $htmlContent = $item->get('content') ?? $item->get('html') ?? $item->get('data');
        
        if (empty($htmlContent)) {
            return $this->createNullItem('HTML content is required');
        }

        // Get data source settings
        $dataSourceConfig = $this->getDataSourceConfig($sourceId);
        
        if (!$dataSourceConfig) {
            return $this->createNullItem("Data source configuration not found for source_id: {$sourceId}");
        }

        $this->log('Processing HTML content for URL extraction', [
            'source_id' => $sourceId,
            'content_length' => strlen($htmlContent)
        ]);

        try {
            // Extract URLs from <a> tags
            $extractedUrls = $this->extractUrlsFromHtml($htmlContent, $dataSourceConfig);
            
            if (empty($extractedUrls)) {
                return $this->createNullItem('No URLs found in HTML content');
            }

            // Save URLs to rake_data_origins table
            $savedCount = $this->saveUrlsToDataOrigins($extractedUrls, $sourceId, $dataSourceConfig);
            
            $this->log('URL extraction completed', [
                'urls_found' => count($extractedUrls),
                'urls_saved' => $savedCount,
                'source_id' => $sourceId
            ]);

            // Return item with extraction results
            return $item->set('urls_extracted', count($extractedUrls))
                        ->set('urls_saved', $savedCount)
                        ->set('source_id', $sourceId);

        } catch (\Exception $e) {
            $this->logError('Error processing HTML content', [
                'error' => $e->getMessage(),
                'source_id' => $sourceId
            ]);
            return $this->createNullItem('Processing error: ' . $e->getMessage());
        }
    }

    /**
     * Extract URLs from HTML <a> tags
     * 
     * @param string $html HTML content
     * @param array $dataSourceConfig Data source configuration
     * @return array Array of extracted URLs with metadata
     */
    private function extractUrlsFromHtml(string $html, array $dataSourceConfig): array
    {
        $urls = [];
        
        // Get configuration for URL extraction
        $baseUrl = $dataSourceConfig['base_url'] ?? '';
        $allowedDomains = $dataSourceConfig['allowed_domains'] ?? [];
        $excludedPatterns = $dataSourceConfig['excluded_patterns'] ?? [];
        $includePatterns = $dataSourceConfig['include_patterns'] ?? [];
        $followExternal = $dataSourceConfig['follow_external'] ?? false;
        
        // Use DOMDocument to parse HTML
        $dom = new \DOMDocument();
        
        // Suppress warnings for malformed HTML
        libxml_use_internal_errors(true);
        
        if (!@$dom->loadHTML($html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD)) {
            $this->logError('Failed to parse HTML content');
            libxml_clear_errors();
            return $urls;
        }
        
        libxml_clear_errors();
        
        // Get all <a> tags
        $links = $dom->getElementsByTagName('a');
        
        foreach ($links as $link) {
            /** @var \DOMElement $link */
            $href = $link->getAttribute('href');
            
            if (empty($href)) {
                continue;
            }
            
            // Skip anchor links
            if (strpos($href, '#') === 0) {
                continue;
            }
            
            // Skip javascript links
            if (strpos(strtolower($href), 'javascript:') === 0) {
                continue;
            }
            
            // Skip mailto links
            if (strpos(strtolower($href), 'mailto:') === 0) {
                continue;
            }
            
            // Skip tel links
            if (strpos(strtolower($href), 'tel:') === 0) {
                continue;
            }
            
            // Convert relative URLs to absolute URLs
            $absoluteUrl = $this->makeAbsoluteUrl($href, $baseUrl);
            
            if (empty($absoluteUrl)) {
                continue;
            }
            
            // Validate URL
            if (!filter_var($absoluteUrl, FILTER_VALIDATE_URL)) {
                continue;
            }
            
            // Apply domain filtering
            if (!$this->isUrlAllowed($absoluteUrl, $allowedDomains, $followExternal)) {
                continue;
            }
            
            // Apply pattern filtering
            if (!$this->matchesPatterns($absoluteUrl, $includePatterns, $excludedPatterns)) {
                continue;
            }
            
            // Get link text and title for metadata
            $linkText = $link->textContent;
            /** @var \DOMElement $link */
            $linkTitle = $link->getAttribute('title');
            
            $urls[] = [
                'url' => $absoluteUrl,
                'text' => trim($linkText),
                'title' => $linkTitle,
                'original_href' => $href
            ];
        }
        
        // Remove duplicates while preserving order
        $uniqueUrls = [];
        $seen = [];
        foreach ($urls as $urlData) {
            $url = $urlData['url'];
            if (!isset($seen[$url])) {
                $seen[$url] = true;
                $uniqueUrls[] = $urlData;
            }
        }
        
        return $uniqueUrls;
    }

    /**
     * Convert relative URL to absolute URL
     * 
     * @param string $url Relative or absolute URL
     * @param string $baseUrl Base URL for resolution
     * @return string|null Absolute URL or null if invalid
     */
    private function makeAbsoluteUrl(string $url, string $baseUrl): ?string
    {
        // Already absolute URL
        if (filter_var($url, FILTER_VALIDATE_URL)) {
            return $url;
        }
        
        if (empty($baseUrl)) {
            return null;
        }
        
        try {
            // Use PHP's built-in URL resolution
            return rtrim($baseUrl, '/') . '/' . ltrim($url, '/');
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Check if URL is allowed based on domain restrictions
     * 
     * @param string $url URL to check
     * @param array $allowedDomains Array of allowed domains
     * @param bool $followExternal Whether to follow external links
     * @return bool True if URL is allowed
     */
    private function isUrlAllowed(string $url, array $allowedDomains, bool $followExternal): bool
    {
        if (empty($allowedDomains)) {
            return true; // No restrictions
        }
        
        $parsedUrl = parse_url($url);
        $domain = $parsedUrl['host'] ?? '';
        
        if (empty($domain)) {
            return false;
        }
        
        // Remove www. prefix for comparison
        $domain = strtolower(preg_replace('/^www\./', '', $domain));
        
        foreach ($allowedDomains as $allowedDomain) {
            $allowedDomain = strtolower(preg_replace('/^www\./', '', $allowedDomain));
            
            if ($domain === $allowedDomain || str_ends_with($domain, '.' . $allowedDomain)) {
                return true;
            }
        }
        
        return $followExternal;
    }

    /**
     * Check if URL matches include/exclude patterns
     * 
     * @param string $url URL to check
     * @param array $includePatterns Array of include patterns (regex)
     * @param array $excludedPatterns Array of exclude patterns (regex)
     * @return bool True if URL matches patterns
     */
    private function matchesPatterns(string $url, array $includePatterns, array $excludedPatterns): bool
    {
        // Check exclude patterns first
        foreach ($excludedPatterns as $pattern) {
            if (@preg_match($pattern, $url)) {
                return false;
            }
        }
        
        // If no include patterns, allow all
        if (empty($includePatterns)) {
            return true;
        }
        
        // Check include patterns
        foreach ($includePatterns as $pattern) {
            if (@preg_match($pattern, $url)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Get data source configuration from database
     * 
     * @param int $sourceId Data source ID
     * @return array|null Data source configuration or null if not found
     */
    private function getDataSourceConfig(int $sourceId): ?array
    {
        global $wpdb;
        
        $sourcesTable = $wpdb->prefix . 'rake_data_sources';
        
        $result = $wpdb->get_row($wpdb->prepare(
            "SELECT id, type, name, config FROM {$sourcesTable} WHERE id = %d",
            $sourceId
        ), ARRAY_A);
        
        if (!$result) {
            return null;
        }
        
        // Parse JSON config
        $config = [];
        if (!empty($result['config'])) {
            $config = json_decode($result['config'], true) ?: [];
        }
        
        // Add basic info
        $config['source_id'] = $result['id'];
        $config['source_type'] = $result['type'];
        $config['source_name'] = $result['name'];
        
        return $config;
    }

    /**
     * Save URLs to rake_data_origins table with duplicate checking
     * 
     * @param array $urls Array of URL data
     * @param int $sourceId Source ID
     * @param array $dataSourceConfig Data source configuration
     * @return int Number of URLs saved
     */
    private function saveUrlsToDataOrigins(array $urls, int $sourceId, array $dataSourceConfig): int
    {
        global $wpdb;
        
        $originsTable = $wpdb->prefix . 'rake_data_origins';
        $savedCount = 0;
        
        foreach ($urls as $urlData) {
            $url = $urlData['url'];
            
            // Check if already exists (by source_id and guid combination)
            $existing = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$originsTable} WHERE source_id = %d AND guid = %s",
                $sourceId,
                $url
            ));
            
            if ($existing) {
                continue; // Skip duplicates
            }
            
            // Prepare data for insertion
            $insertData = [
                'source_id' => $sourceId,
                'guid' => $url,
                'raw_data' => json_encode([
                    'url' => $url,
                    'text' => $urlData['text'],
                    'title' => $urlData['title'],
                    'original_href' => $urlData['original_href'],
                    'extracted_at' => current_time('mysql'),
                    'source_config' => $dataSourceConfig
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'fetched_at' => current_time('mysql'),
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql'),
                'source_type' => 'processor',
                'processor_id' => 'CrawlUrlInHtmlProcessor',
                'crawled' => 0, // Not yet crawled
            ];
            
            // Set priority from config or default
            $insertData['priority'] = $dataSourceConfig['priority'] ?? 100;
            
            // Set is_archive if configured
            if (isset($dataSourceConfig['is_archive'])) {
                $insertData['is_archive'] = $dataSourceConfig['is_archive'] ? 1 : 0;
            }
            
            $result = $wpdb->insert($originsTable, $insertData);
            
            if ($result !== false) {
                $savedCount++;
            } else {
                $this->logError('Failed to insert URL into data_origins', [
                    'url' => $url,
                    'error' => $wpdb->last_error
                ]);
            }
        }
        
        return $savedCount;
    }
}
