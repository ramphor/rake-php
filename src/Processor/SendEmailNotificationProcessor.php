<?php

namespace Rake\Processor;

use Rake\Contracts\Entities\ParsedDataItemInterface;

/**
 * Send Email Notification Processor
 * Sends email notification with extracted data
 */
class SendEmailNotificationProcessor extends AbstractProcessor
{
    /**
     * Process and send email notification
     * 
     * @param ParsedDataItemInterface $item Extracted data item
     * @return ParsedDataItemInterface
     */
    public function process(ParsedDataItemInterface $item): ParsedDataItemInterface
    {
        // Skip if already null item
        if ($item->isNull()) {
            return $item;
        }

        // Get configuration
        $recipients = $this->getConfig('recipients', '');
        $subject = $this->getConfig('subject', 'Crawl Notification');
        $bodyTemplate = $this->getConfig('body', 'Data extracted successfully.');

        // Validate required config
        if (empty($recipients)) {
            return $this->createNullItem('Email recipients are required');
        }

        // Get data
        $data = $item->getData();

        // Replace placeholders in subject and body
        $subject = $this->replacePlaceholders($subject, $data);
        $body = $this->replacePlaceholders($bodyTemplate, $data);

        // Parse recipients (comma-separated)
        $recipientList = array_map('trim', explode(',', $recipients));

        $this->log('Sending email notification', [
            'recipients' => count($recipientList),
            'subject' => $subject,
        ]);

        try {
            $sent = $this->sendEmail($recipientList, $subject, $body, $data);

            if ($sent) {
                $this->log('Email sent successfully');
                return $item->set('email_sent', true);
            } else {
                return $this->createNullItem('Failed to send email');
            }

        } catch (\Exception $e) {
            $this->logError('Email error', ['error' => $e->getMessage()]);
            return $this->createNullItem('Email error: ' . $e->getMessage());
        }
    }

    /**
     * Replace placeholders with data values
     */
    private function replacePlaceholders(string $template, array $data): string
    {
        foreach ($data as $key => $value) {
            if (is_scalar($value)) {
                $template = str_replace('{{' . $key . '}}', (string)$value, $template);
            }
        }
        return $template;
    }

    /**
     * Send email
     */
    private function sendEmail(array $recipients, string $subject, string $body, array $data): bool
    {
        // Use WordPress wp_mail if available
        if (function_exists('wp_mail')) {
            $headers = ['Content-Type: text/plain; charset=UTF-8'];
            
            foreach ($recipients as $recipient) {
                $result = wp_mail($recipient, $subject, $body, $headers);
                if (!$result) {
                    $this->logError('Failed to send email to ' . $recipient);
                    return false;
                }
            }
            
            return true;
        }

        // Fallback to PHP mail()
        $headers = "From: noreply@" . ($_SERVER['SERVER_NAME'] ?? 'localhost') . "\r\n";
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

        foreach ($recipients as $recipient) {
            $result = mail($recipient, $subject, $body, $headers);
            if (!$result) {
                $this->logError('Failed to send email to ' . $recipient);
                return false;
            }
        }

        return true;
    }
}

