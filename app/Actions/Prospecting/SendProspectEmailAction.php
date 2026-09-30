<?php

declare(strict_types=1);

namespace App\Actions\Prospecting;

use App\Enums\ProspectEmailStatus;
use App\Mail\ProspectOutreachMail;
use App\Models\ProspectEmail;
use App\Models\SuppressionListEntry;
use App\Services\Prospecting\EmailSendWindowService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendProspectEmailAction
{
    public function __construct(
        private readonly PersonalizeProspectEmailAction $personalizeProspectEmail,
        private readonly EmailSendWindowService $sendWindowService,
    ) {}

    public function execute(ProspectEmail $prospectEmail): bool
    {
        $prospectEmail->load(['prospect.category', 'template', 'sequence']);

        $prospect = $prospectEmail->prospect;

        if (! $prospect->isContactable()) {
            $prospectEmail->update([
                'status' => ProspectEmailStatus::Skipped,
                'error_message' => 'Prospect not contactable',
            ]);

            return false;
        }

        if (SuppressionListEntry::query()->where('email', $prospect->email)->exists()) {
            $prospectEmail->update([
                'status' => ProspectEmailStatus::Skipped,
                'error_message' => 'Email suppressed',
            ]);

            return false;
        }

        if (! $this->sendWindowService->canSendMoreToday()) {
            return false;
        }

        if (! $this->sendWindowService->canSendToDomain($prospect->email)) {
            $prospectEmail->update([
                'status' => ProspectEmailStatus::Skipped,
                'error_message' => 'Domain daily limit reached',
            ]);

            return false;
        }

        $rendered = $this->personalizeProspectEmail->execute($prospectEmail);

        try {
            $mailable = new ProspectOutreachMail(
                prospectEmail: $prospectEmail,
                subjectLine: $rendered['subject'],
                htmlBody: $rendered['body_html'],
                textBody: $rendered['body_text'],
            );

            Mail::to($prospect->email, $prospect->company_name)->send($mailable);

            $prospectEmail->update([
                'status' => ProspectEmailStatus::Sent,
                'sent_at' => now(),
                'rendered_subject' => $rendered['subject'],
                'rendered_body_html' => $rendered['body_html'],
                'rendered_body_text' => $rendered['body_text'],
                'personalized_by_ai' => $rendered['personalized_by_ai'],
            ]);

            $prospectEmail->sequence->update(['current_step' => $prospectEmail->step]);

            $this->sendWindowService->recordDomainSend($prospect->email);
            $this->sendWindowService->incrementDailyStat('sent_count');

            return true;
        } catch (Throwable $exception) {
            Log::error('Prospect email send failed', [
                'prospect_email_id' => $prospectEmail->id,
                'prospect_id' => $prospect->id,
                'step' => $prospectEmail->step,
            ]);

            $prospectEmail->update([
                'status' => ProspectEmailStatus::Failed,
                'error_message' => 'Send failed',
            ]);

            $this->sendWindowService->incrementDailyStat('failed_count');

            return false;
        }
    }
}
