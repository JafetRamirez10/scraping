<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\EmailQuality;
use App\Enums\EmailSource;
use App\Enums\ProspectStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Prospect extends Model
{
    protected $fillable = [
        'category_id',
        'company_name',
        'website_url',
        'email',
        'email_quality',
        'email_source',
        'status',
        'unsubscribed_at',
        'bounce_count',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'status' => ProspectStatus::class,
            'email_quality' => EmailQuality::class,
            'email_source' => EmailSource::class,
            'unsubscribed_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function sequence(): HasOne
    {
        return $this->hasOne(ProspectSequence::class);
    }

    public function emails(): HasMany
    {
        return $this->hasMany(ProspectEmail::class);
    }

    public function isContactable(): bool
    {
        return ! in_array($this->status, [
            ProspectStatus::Unsubscribed,
            ProspectStatus::Bounced,
            ProspectStatus::Invalid,
            ProspectStatus::NoEngagement,
        ], true);
    }
}
