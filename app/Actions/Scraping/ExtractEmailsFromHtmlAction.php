<?php

declare(strict_types=1);

namespace App\Actions\Scraping;

use App\DataTransferObjects\ExtractedEmail;
use App\Enums\EmailQuality;
use App\Enums\EmailSource;

class ExtractEmailsFromHtmlAction
{
    public function execute(string $html, ?string $pageUrl = null): array
    {
        $emails = [];
        $pattern = '/[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}/';

        if (preg_match_all($pattern, $html, $matches)) {
            foreach (array_unique($matches[0]) as $email) {
                $emails[] = new ExtractedEmail(
                    email: strtolower($email),
                    source: $this->detectSource($pageUrl),
                    quality: $this->detectQuality($pageUrl, $email),
                );
            }
        }

        return $emails;
    }

    public function extractFromSnippet(?string $snippet): array
    {
        if ($snippet === null || $snippet === '') {
            return [];
        }

        $emails = [];
        $pattern = '/[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}/';

        if (preg_match_all($pattern, $snippet, $matches)) {
            foreach (array_unique($matches[0]) as $email) {
                $emails[] = new ExtractedEmail(
                    email: strtolower($email),
                    source: EmailSource::Snippet->value,
                    quality: EmailQuality::Medium->value,
                );
            }
        }

        return $emails;
    }

    private function detectSource(?string $pageUrl): string
    {
        if ($pageUrl === null) {
            return EmailSource::Website->value;
        }

        $path = strtolower(parse_url($pageUrl, PHP_URL_PATH) ?? '');

        if (str_contains($path, 'contact')) {
            return EmailSource::ContactPage->value;
        }

        return EmailSource::Website->value;
    }

    private function detectQuality(?string $pageUrl, string $email): string
    {
        $localPart = strstr($email, '@', true) ?: '';

        if (in_array($localPart, ['info', 'contacto', 'contact', 'ventas', 'hola'], true)) {
            return EmailQuality::Low->value;
        }

        if ($pageUrl !== null && str_contains(strtolower($pageUrl), 'contact')) {
            return EmailQuality::High->value;
        }

        return EmailQuality::Medium->value;
    }
}
