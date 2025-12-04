<?php

namespace Rake\CompletionAction;

/**
 * Send Email Notification Completion Action
 * 
 * Send email notification khi hoàn thành crawl/processing.
 */
class SendEmailNotificationAction extends AbstractCompletionAction
{
    /**
     * Get action type
     *
     * @return string
     */
    public function getType(): string
    {
        return 'send_email_notification';
    }

    /**
     * Get action name
     *
     * @return string
     */
    public function getName(): string
    {
        return 'Send Email Notification';
    }

    /**
     * Get action description
     *
     * @return string
     */
    public function getDescription(): string
    {
        return 'Send email notification when crawl/processing is complete';
    }

    /**
     * Execute email notification action
     *
     * @param array $context Context data
     * @return array
     */
    public function execute(array $context): array
    {
        $to = $this->getConfigValue('to', get_option('admin_email'));
        $subject = $this->buildSubject($context);
        $message = $this->buildMessage($context);
        $headers = $this->buildHeaders();

        // Send email
        $sent = wp_mail($to, $subject, $message, $headers);

        if ($sent) {
            return [
                'success' => true,
                'message' => "Email notification sent to {$to}",
                'data' => ['to' => $to, 'subject' => $subject]
            ];
        } else {
            return [
                'success' => false,
                'message' => "Failed to send email notification to {$to}",
                'data' => null
            ];
        }
    }

    /**
     * Build email subject
     *
     * @param array $context
     * @return string
     */
    private function buildSubject(array $context): string
    {
        $customSubject = $this->getConfigValue('subject', '');
        
        if (!empty($customSubject)) {
            // Replace placeholders
            return $this->replacePlaceholders($customSubject, $context);
        }

        // Default subject
        $projectName = $context['project_name'] ?? 'Crawl Project';
        $status = ($context['failure_count'] ?? 0) > 0 ? 'Completed with Errors' : 'Completed Successfully';
        
        return "[CrawlFlow] {$projectName} - {$status}";
    }

    /**
     * Build email message
     *
     * @param array $context
     * @return string
     */
    private function buildMessage(array $context): string
    {
        $customMessage = $this->getConfigValue('message', '');
        
        if (!empty($customMessage)) {
            // Use custom message with placeholders
            return $this->replacePlaceholders($customMessage, $context);
        }

        // Default message
        $projectName = $context['project_name'] ?? 'Unknown Project';
        $totalItems = $context['total_items_processed'] ?? 0;
        $successCount = $context['success_count'] ?? 0;
        $failureCount = $context['failure_count'] ?? 0;
        $duration = $context['duration_seconds'] ?? 0;

        $message = "Hello,\n\n";
        $message .= "Your crawl project '{$projectName}' has completed.\n\n";
        $message .= "=== Summary ===\n";
        $message .= "Total Items Processed: {$totalItems}\n";
        $message .= "Success: {$successCount}\n";
        $message .= "Failed: {$failureCount}\n";
        $message .= "Duration: " . gmdate('H:i:s', $duration) . "\n\n";

        if ($failureCount > 0 && !empty($context['errors'])) {
            $message .= "=== Recent Errors ===\n";
            $errorSample = array_slice($context['errors'], 0, 5);
            foreach ($errorSample as $error) {
                $message .= "- " . ($error['message'] ?? 'Unknown error') . "\n";
            }
            if (count($context['errors']) > 5) {
                $message .= "... and " . (count($context['errors']) - 5) . " more errors\n";
            }
            $message .= "\n";
        }

        $message .= "This is an automated notification from CrawlFlow.\n";

        return $message;
    }

    /**
     * Build email headers
     *
     * @return array
     */
    private function buildHeaders(): array
    {
        $headers = ['Content-Type: text/plain; charset=UTF-8'];

        $from = $this->getConfigValue('from', '');
        if (!empty($from)) {
            $headers[] = "From: {$from}";
        }

        return $headers;
    }

    /**
     * Replace placeholders in text
     *
     * @param string $text
     * @param array $context
     * @return string
     */
    private function replacePlaceholders(string $text, array $context): string
    {
        $placeholders = [
            '{project_name}' => $context['project_name'] ?? 'Unknown',
            '{total_items}' => $context['total_items_processed'] ?? 0,
            '{success_count}' => $context['success_count'] ?? 0,
            '{failure_count}' => $context['failure_count'] ?? 0,
            '{duration}' => gmdate('H:i:s', $context['duration_seconds'] ?? 0),
            '{start_time}' => $context['start_time'] ?? 'N/A',
            '{end_time}' => $context['end_time'] ?? 'N/A'
        ];

        return str_replace(array_keys($placeholders), array_values($placeholders), $text);
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
                'name' => 'to',
                'type' => 'text',
                'label' => 'To Email',
                'description' => 'Recipient email address (leave empty for admin email)',
                'placeholder' => 'admin@example.com',
                'default' => '',
                'required' => false
            ],
            [
                'name' => 'from',
                'type' => 'text',
                'label' => 'From Email',
                'description' => 'Sender email address (optional)',
                'placeholder' => 'noreply@example.com',
                'default' => '',
                'required' => false
            ],
            [
                'name' => 'subject',
                'type' => 'text',
                'label' => 'Subject',
                'description' => 'Email subject (use placeholders: {project_name}, {total_items}, etc.)',
                'placeholder' => '[CrawlFlow] {project_name} - Completed',
                'default' => '',
                'required' => false
            ],
            [
                'name' => 'message',
                'type' => 'textarea',
                'label' => 'Message',
                'description' => 'Email message body (use placeholders: {project_name}, {total_items}, {success_count}, {failure_count}, {duration})',
                'placeholder' => 'Your crawl project {project_name} has completed. Total items: {total_items}',
                'default' => '',
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

        // Validate email format if provided
        if (!empty($config['to']) && !is_email($config['to'])) {
            $errors[] = 'Invalid "to" email address format';
        }

        if (!empty($config['from']) && !is_email($config['from'])) {
            $errors[] = 'Invalid "from" email address format';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
}

