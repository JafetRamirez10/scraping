<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

class MailRushRateLimited extends RuntimeException
{
    public function __construct(public readonly int $retryAfterSeconds = 60)
    {
        parent::__construct('MailRush rate limit reached.');
    }
}
