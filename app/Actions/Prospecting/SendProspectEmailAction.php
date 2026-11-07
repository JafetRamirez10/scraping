<?php

declare(strict_types=1);

namespace App\Actions\Prospecting;

use App\Enums\ProspectEmailStatus;
use App\Exceptions\MailRushRateLimited;
use App\Mail\ProspectOutreachMail;
use App\Models\ProspectEmail;
use App\Models\SuppressionListEntry;
use App\Services\Mail\OutboundMailer;
use App\Services\Prospecting\EmailSendWindowService;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendProspectEmailAction
{
    public function __construct(
        private readonly PersonalizeProspectEmailAction $personalizeProspectEmail,
        private readonly EmailSendWindowService $sendWindowService,
        private readonly OutboundMailer $outboundMailer,
    ) {}

    public function execute(ProspectEmail $prospectEmail): bool
    {
        $prospectEmail->load(['prospect.category', 'template', 'sequence']);

        $prospect = $prospectEmail->prospect;

        if ($prospect->isStepDisabled((int) $prospectEmail->step)) {
            $prospectEmail->update([
                'status' => ProspectEmailStatus::Skipped,
                'error_message' => SetProspectEmailStepAction::DISABLED_MESSAGE,
            ]);

            return false;
        }

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

            $messageId = $this->outboundMailer->send(
                mailable: $mailable,
                toEmail: $prospect->email,
                toName: $prospect->company_name,
                subject: $rendered['subject'],
                html: $rendered['body_html'],
                text: $rendered['body_text'],
                idempotencyKey: 'prospect-email-'.$prospectEmail->id,
            );

            $prospectEmail->update([
                'status' => ProspectEmailStatus::Sent,
                'sent_at' => now(),
                'rendered_subject' => $rendered['subject'],
                'rendered_body_html' => $rendered['body_html'],
                'rendered_body_text' => $rendered['body_text'],
                'personalized_by_ai' => $rendered['personalized_by_ai'],
                'provider_message_id' => $messageId,
            ]);

            $prospectEmail->sequence->update(['current_step' => $prospectEmail->step]);

            $this->sendWindowService->recordDomainSend($prospect->email);
            $this->sendWindowService->incrementDailyStat('sent_count');

            return true;
        } catch (MailRushRateLimited $exception) {
            Log::warning('MailRush rate limit reached', [
                'prospect_email_id' => $prospectEmail->id,
                'retry_after_seconds' => $exception->retryAfterSeconds,
            ]);

            throw $exception;
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
