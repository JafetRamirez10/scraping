<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\SuppressionReason;
use Illuminate\Database\Eloquent\Model;

class SuppressionListEntry extends Model
{
    protected $table = 'suppression_list';

    protected $fillable = [
        'email',
        'reason',
        'suppressed_at',
    ];

    protected function casts(): array
    {
        return [
            'reason' => SuppressionReason::class,
            'suppressed_at' => 'datetime',
        ];
    }
}
