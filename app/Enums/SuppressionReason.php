<?php

declare(strict_types=1);

namespace App\Enums;

enum SuppressionReason: string
{
    case Bounce = 'bounce';
    case Complaint = 'complaint';
    case Unsubscribe = 'unsubscribe';
    case Manual = 'manual';
}
