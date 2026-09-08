<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ScrapeRunStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ScrapeRun extends Model
{
    protected $fillable = [
        'category_id',
        'status',
        'serpapi_search_id',
        'emails_found',
        'emails_saved',
        'started_at',
        'finished_at',
        'error_message',
    ];

    protected function casts(): array
    {
        return [
            'status' => ScrapeRunStatus::class,
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function urls(): HasMany
    {
        return $this->hasMany(ScrapeRunUrl::class);
    }
}
