<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\SequenceCancelReason;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProspectSequence extends Model
{
    protected $fillable = [
        'prospect_id',
        'current_step',
        'started_at',
        'completed_at',
        'cancelled_at',
        'cancel_reason',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'cancel_reason' => SequenceCancelReason::class,
        ];
    }

    public function prospect(): BelongsTo
    {
        return $this->belongsTo(Prospect::class);
    }

    public function emails(): HasMany
    {
        return $this->hasMany(ProspectEmail::class);
    }

    public function isActive(): bool
    {
        return $this->completed_at === null && $this->cancelled_at === null;
    }
}
