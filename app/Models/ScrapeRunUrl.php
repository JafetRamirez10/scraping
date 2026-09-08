<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ScrapeRunUrlStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScrapeRunUrl extends Model
{
    protected $fillable = [
        'scrape_run_id',
        'url',
        'emails_extracted',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => ScrapeRunUrlStatus::class,
        ];
    }

    public function scrapeRun(): BelongsTo
    {
        return $this->belongsTo(ScrapeRun::class);
    }
}
