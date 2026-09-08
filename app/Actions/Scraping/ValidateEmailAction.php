<?php

declare(strict_types=1);

namespace App\Actions\Scraping;

class ValidateEmailAction
{
    /** @var array<int, string> */
    private array $disposableDomains = [
        'mailinator.com',
        'guerrillamail.com',
        'tempmail.com',
        'yopmail.com',
    ];

    public function execute(string $email): bool
    {
        $email = strtolower(trim($email));

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        if (str_starts_with($email, 'noreply@') || str_starts_with($email, 'no-reply@')) {
            return false;
        }

        $domain = substr(strrchr($email, '@') ?: '', 1);

        if ($domain === '' || in_array($domain, $this->disposableDomains, true)) {
            return false;
        }

        return checkdnsrr($domain, 'MX');
    }
}
