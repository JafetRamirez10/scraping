<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ProspectEmailStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProspectEmail extends Model
{
    protected $fillable = [
        'prospect_id',
        'prospect_sequence_id',
        'email_template_id',
        'step',
        'scheduled_at',
        'sent_at',
        'status',
        'opened_at',
        'clicked_at',
        'replied_at',
        'provider_message_id',
        'error_message',
        'rendered_subject',
        'rendered_body_html',
        'rendered_body_text',
        'personalized_by_ai',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'sent_at' => 'datetime',
            'status' => ProspectEmailStatus::class,
            'opened_at' => 'datetime',
            'clicked_at' => 'datetime',
            'replied_at' => 'datetime',
            'personalized_by_ai' => 'boolean',
        ];
    }

    public function prospect(): BelongsTo
    {
        return $this->belongsTo(Prospect::class);
    }

    public function sequence(): BelongsTo
    {
        return $this->belongsTo(ProspectSequence::class, 'prospect_sequence_id');
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(EmailTemplate::class, 'email_template_id');
    }
}
