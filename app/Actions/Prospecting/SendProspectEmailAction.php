<?php

declare(strict_types=1);

namespace App\Actions\Prospecting;

use App\Enums\ProspectEmailStatus;
use App\Mail\ProspectOutreachMail;
use App\Models\ProspectEmail;
use App\Models\SuppressionListEntry;
use App\Services\Mail\ProspectTemplateRenderer;
use App\Services\Prospecting\EmailSendWindowService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendProspectEmailAction
{
    public function __construct(
        private readonly ProspectTemplateRenderer $templateRenderer,
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

        try {
            $mailable = new ProspectOutreachMail(
                prospectEmail: $prospectEmail,
                subjectLine: $this->templateRenderer->renderSubject($prospectEmail->template, $prospect),
                htmlBody: $this->templateRenderer->renderHtml($prospectEmail->template, $prospect),
                textBody: $this->templateRenderer->renderText($prospectEmail->template, $prospect),
            );

            Mail::to($prospect->email, $prospect->company_name)->send($mailable);

            $prospectEmail->update([
                'status' => ProspectEmailStatus::Sent,
                'sent_at' => now(),
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
