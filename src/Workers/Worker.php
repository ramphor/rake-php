<?php

namespace Rake\Workers;

/**
 * Worker class for handling URL detection and validation
 */
class Worker
{
    private $id;
    private $detectionRules;
    private $detectionLogic;
    private $priority;
    private $isArchive;
    private $archiveProductsWrapper;
    private $archiveUrlContains;
    private $parser;

    public function __construct(array $config)
    {
        $this->id = $config['id'] ?? 'unknown';
        $this->detectionRules = $config['detectionRules'] ?? [];
        $this->detectionLogic = $config['detectionLogic'] ?? 'and';
        $this->priority = $config['priority'] ?? 0;
        $this->isArchive = $config['isArchive'] ?? false;
        $this->archiveProductsWrapper = $config['archiveProductsWrapper'] ?? null;
        $this->archiveUrlContains = $config['archiveUrlContains'] ?? null;
        $this->parser = $config['parser'] ?? [];
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getDetectionRules(): array
    {
        return $this->detectionRules;
    }

    public function getDetectionLogic(): string
    {
        return $this->detectionLogic;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    public function isArchive(): bool
    {
        return $this->isArchive;
    }

    public function getArchiveProductsWrapper(): ?string
    {
        return $this->archiveProductsWrapper;
    }

    public function getArchiveUrlContains(): ?string
    {
        return $this->archiveUrlContains;
    }

    public function getParser(): array
    {
        return $this->parser;
    }

    /**
     * Check if URL matches this worker's detection rules
     */
    public function matchesUrl(string $url): bool
    {
        if (empty($this->detectionRules)) {
            return true; // No rules means accept all URLs
        }

        $matchedRules = 0;

        foreach ($this->detectionRules as $rule) {
            $ruleType = $rule['type'] ?? '';
            $ruleValue = $rule['pattern'] ?? $rule['value'] ?? '';

            $matches = false;

            switch ($ruleType) {
                case 'url-format':
                    $matches = $this->matchesUrlFormat($url, $ruleValue);
                    break;

                case 'html-contains':
                    // For archive detection, assume match
                    $matches = true;
                    break;

                case 'dom-value':
                case 'tag-attribute':
                    // Content extraction rules, assume match
                    $matches = true;
                    break;
            }

            if ($matches) {
                $matchedRules++;
            }

            if ($this->detectionLogic === 'or' && $matches) {
                return true;
            }
        }

        return $this->detectionLogic === 'and' && $matchedRules === count($this->detectionRules);
    }

    /**
     * Check if URL matches a specific format pattern
     */
    private function matchesUrlFormat(string $url, string $pattern): bool
    {
        $pattern = str_replace('*', '.*', preg_quote($pattern, '/'));
        return preg_match("/^{$pattern}$/i", $url);
    }

    /**
     * Check if this worker can handle archive pages
     */
    public function canHandleArchive(): bool
    {
        return $this->isArchive && !empty($this->archiveProductsWrapper);
    }

    /**
     * Get worker configuration as array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'detectionRules' => $this->detectionRules,
            'detectionLogic' => $this->detectionLogic,
            'priority' => $this->priority,
            'isArchive' => $this->isArchive,
            'archiveProductsWrapper' => $this->archiveProductsWrapper,
            'archiveUrlContains' => $this->archiveUrlContains,
            'parser' => $this->parser,
        ];
    }
}
