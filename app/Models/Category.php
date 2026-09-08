<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'search_query',
        'target_email_count',
        'scrape_interval_days',
        'is_active',
        'last_scraped_at',
        'next_scrape_at',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'last_scraped_at' => 'datetime',
            'next_scrape_at' => 'datetime',
        ];
    }

    public function prospects(): HasMany
    {
        return $this->hasMany(Prospect::class);
    }

    public function scrapeRuns(): HasMany
    {
        return $this->hasMany(ScrapeRun::class);
    }

    public function scopeDueForScrape($query)
    {
        return $query
            ->where('is_active', true)
            ->where(function ($query): void {
                $query
                    ->whereNull('next_scrape_at')
                    ->orWhere('next_scrape_at', '<=', now());
            });
    }
}
