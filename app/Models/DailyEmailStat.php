<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DailyEmailStat extends Model
{
    protected $fillable = [
        'date',
        'sent_count',
        'failed_count',
        'bounced_count',
        'complaint_count',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
        ];
    }
}
