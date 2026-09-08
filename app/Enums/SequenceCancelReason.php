<?php

declare(strict_types=1);

namespace App\Enums;

enum SequenceCancelReason: string
{
    case NoEngagement = 'no_engagement';
    case Bounce = 'bounce';
    case Complaint = 'complaint';
    case Unsubscribe = 'unsubscribe';
    case Manual = 'manual';
}
