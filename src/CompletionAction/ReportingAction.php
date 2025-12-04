<?php

namespace Rake\CompletionAction;

/**
 * Reporting Completion Action
 * 
 * Generate và log summary report khi hoàn thành crawl/processing.
 */
class ReportingAction extends AbstractCompletionAction
{
    /**
     * Get action type
     *
     * @return string
     */
    public function getType(): string
    {
        return 'reporting';
    }

    /**
     * Get action name
     *
     * @return string
     */
    public function getName(): string
    {
        return 'Generate Report';
    }

    /**
     * Get action description
     *
     * @return string
     */
    public function getDescription(): string
    {
        return 'Generate summary report of crawl/processing results';
    }

    /**
     * Execute reporting action
     *
     * @param array $context Context data
     * @return array
     */
    public function execute(array $context): array
    {
        $reportType = $this->getConfigValue('report_type', 'summary');
        $includeErrors = $this->getConfigValue('include_errors', true);
        $includeStats = $this->getConfigValue('include_stats', true);

        // Build report data
        $report = $this->buildReport($context, $reportType, $includeErrors, $includeStats);

        // Log report nếu enabled
        if ($this->getConfigValue('log_report', true)) {
            $this->logReport($report);
        }

        // Save report to file nếu enabled
        if ($this->getConfigValue('save_to_file', false)) {
            $filePath = $this->saveReportToFile($report);
            $report['file_path'] = $filePath;
        }

        return [
            'success' => true,
            'message' => 'Report generated successfully',
            'data' => $report
        ];
    }

    /**
     * Build report data
     *
     * @param array $context
     * @param string $reportType
     * @param bool $includeErrors
     * @param bool $includeStats
     * @return array
     */
    private function buildReport(array $context, string $reportType, bool $includeErrors, bool $includeStats): array
    {
        $report = [
            'type' => $reportType,
            'timestamp' => current_time('mysql'),
            'project_name' => $context['project_name'] ?? 'Unknown Project',
            'project_id' => $context['project_id'] ?? null
        ];

        // Summary statistics
        if ($includeStats) {
            $report['statistics'] = [
                'total_items_processed' => $context['total_items_processed'] ?? 0,
                'success_count' => $context['success_count'] ?? 0,
                'failure_count' => $context['failure_count'] ?? 0,
                'skipped_count' => $context['skipped_count'] ?? 0,
                'duration_seconds' => $context['duration_seconds'] ?? 0,
                'start_time' => $context['start_time'] ?? null,
                'end_time' => $context['end_time'] ?? null
            ];
        }

        // Error details
        if ($includeErrors && !empty($context['errors'])) {
            $report['errors'] = array_slice($context['errors'], 0, 50); // Limit to 50 errors
            $report['error_count'] = count($context['errors']);
        }

        // Workers summary
        if (!empty($context['workers_summary'])) {
            $report['workers'] = $context['workers_summary'];
        }

        // Processors summary
        if (!empty($context['processors_summary'])) {
            $report['processors'] = $context['processors_summary'];
        }

        return $report;
    }

    /**
     * Log report (using Logger facade if available)
     *
     * @param array $report
     * @return void
     */
    private function logReport(array $report): void
    {
        $message = sprintf(
            "Crawl completed: %s (ID: %s) - Processed: %d items, Success: %d, Failed: %d, Duration: %ds",
            $report['project_name'] ?? 'Unknown',
            $report['project_id'] ?? 'N/A',
            $report['statistics']['total_items_processed'] ?? 0,
            $report['statistics']['success_count'] ?? 0,
            $report['statistics']['failure_count'] ?? 0,
            $report['statistics']['duration_seconds'] ?? 0
        );

        // Try to use Logger facade if available
        if (class_exists('\Rake\Facade\Logger')) {
            \Rake\Facade\Logger::info($message, ['report' => $report]);
        } else {
            error_log($message);
        }
    }

    /**
     * Save report to file
     *
     * @param array $report
     * @return string|null File path
     */
    private function saveReportToFile(array $report): ?string
    {
        $uploadDir = wp_upload_dir()['basedir'] . '/crawlflow/reports/';
        
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $filename = sprintf(
            'report_%s_%s.json',
            $report['project_id'] ?? 'unknown',
            date('YmdHis')
        );

        $filePath = $uploadDir . $filename;
        
        if (file_put_contents($filePath, json_encode($report, JSON_PRETTY_PRINT)) !== false) {
            return $filePath;
        }

        return null;
    }

    /**
     * Get configuration fields for UI
     *
     * @return array
     */
    public static function getConfigFields(): array
    {
        return [
            [
                'name' => 'report_type',
                'type' => 'select',
                'label' => 'Report Type',
                'description' => 'Type of report to generate',
                'options' => [
                    ['value' => 'summary', 'label' => 'Summary'],
                    ['value' => 'detailed', 'label' => 'Detailed'],
                    ['value' => 'errors_only', 'label' => 'Errors Only']
                ],
                'default' => 'summary',
                'required' => false
            ],
            [
                'name' => 'include_errors',
                'type' => 'checkbox',
                'label' => 'Include Errors',
                'description' => 'Include error details in report',
                'default' => true,
                'required' => false
            ],
            [
                'name' => 'include_stats',
                'type' => 'checkbox',
                'label' => 'Include Statistics',
                'description' => 'Include processing statistics',
                'default' => true,
                'required' => false
            ],
            [
                'name' => 'log_report',
                'type' => 'checkbox',
                'label' => 'Log Report',
                'description' => 'Log report to system log',
                'default' => true,
                'required' => false
            ],
            [
                'name' => 'save_to_file',
                'type' => 'checkbox',
                'label' => 'Save to File',
                'description' => 'Save report as JSON file',
                'default' => false,
                'required' => false
            ]
        ];
    }

    /**
     * Validate configuration
     *
     * @param array $config
     * @return array
     */
    public static function validateConfig(array $config): array
    {
        $errors = [];

        // Validate report_type if provided
        if (isset($config['report_type'])) {
            $validTypes = ['summary', 'detailed', 'errors_only'];
            if (!in_array($config['report_type'], $validTypes)) {
                $errors[] = "Invalid report_type. Must be one of: " . implode(', ', $validTypes);
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
}

