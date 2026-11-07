<?php

declare(strict_types=1);

namespace App\Actions\Prospecting;

use App\Enums\ProspectEmailStatus;
use App\Enums\SequenceCancelReason;
use App\Models\Prospect;
use App\Models\ProspectEmail;
use Illuminate\Support\Facades\Log;

class HandleMailRushWebhookAction
{
    public function __construct(
        private readonly HandleBrevoWebhookAction $brevoWebhook,
        private readonly CancelProspectSequenceAction $cancelSequence,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function execute(array $payload): void
    {
        $event = (string) ($payload['event_type'] ?? '');

        if ($event === '' || $event === 'test') {
            return;
        }

        $data = is_array($payload['data'] ?? null) ? $payload['data'] : [];
        $prospectEmail = $this->findByMessageId($this->messageIds($event, $data));
        $email = $this->emailFrom($data);

        if ($email === '' && $prospectEmail?->prospect !== null) {
            $email = strtolower((string) $prospectEmail->prospect->email);
        }

        if ($event === 'reply') {
            $this->handleReply($prospectEmail, $email);

            return;
        }

        $brevoEvent = match ($event) {
            'open' => 'opened',
            'click' => 'click',
            'bounce' => str_starts_with(strtolower((string) ($data['bounce_category'] ?? '')), 'soft')
                ? 'soft_bounce'
                : 'hard_bounce',
            'complaint' => 'complaint',
            'unsubscribe' => 'unsubscribed',
            default => null,
        };

        if ($brevoEvent === null) {
            return;
        }

        if ($email === '') {
            Log::warning('MailRush webhook could not match a prospect', [
                'event_type' => $event,
            ]);

            return;
        }

        $this->brevoWebhook->execute($brevoEvent, [
            'email' => $email,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<int, string>
     */
    private function messageIds(string $event, array $data): array
    {
        $related = trim((string) ($data['related_outgoing_message_id'] ?? ''));
        $messageId = trim((string) ($data['message_id'] ?? ''));
        $ordered = in_array($event, ['bounce', 'complaint', 'reply'], true)
            ? [$related, $messageId]
            : [$messageId, $related];

        return array_values(array_unique(array_filter($ordered, fn (string $value): bool => $value !== '')));
    }

    /**
     * @param  array<int, string>  $messageIds
     */
    private function findByMessageId(array $messageIds): ?ProspectEmail
    {
        if ($messageIds === []) {
            return null;
        }

        return ProspectEmail::query()
            ->with('prospect')
            ->whereIn('provider_message_id', $messageIds)
            ->latest('sent_at')
            ->first();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function emailFrom(array $data): string
    {
        foreach (['email', 'original_recipient', 'recipient_email', 'recipientEmail', 'recipient'] as $key) {
            $value = strtolower(trim((string) ($data[$key] ?? '')));

            if (filter_var($value, FILTER_VALIDATE_EMAIL)) {
                return $value;
            }
        }

        $from = trim((string) ($data['from'] ?? ''));

        if (preg_match('/<([^>]+)>/', $from, $matches) === 1) {
            $value = strtolower(trim($matches[1]));

            if (filter_var($value, FILTER_VALIDATE_EMAIL)) {
                return $value;
            }
        }

        $from = strtolower($from);

        return filter_var($from, FILTER_VALIDATE_EMAIL) ? $from : '';
    }

    private function handleReply(?ProspectEmail $prospectEmail, string $email): void
    {
        $prospect = $prospectEmail?->prospect;

        if ($prospect === null && $email !== '') {
            $prospect = Prospect::query()->where('email', $email)->first();
        }

        if ($prospect === null) {
            Log::warning('MailRush webhook could not match a prospect', [
                'event_type' => 'reply',
            ]);

            return;
        }

        $prospectEmail ??= ProspectEmail::query()
            ->where('prospect_id', $prospect->id)
            ->where('status', ProspectEmailStatus::Sent)
            ->latest('sent_at')
            ->first();

        if ($prospectEmail !== null && $prospectEmail->replied_at === null) {
            $prospectEmail->update(['replied_at' => now()]);
        }

        $this->cancelSequence->execute($prospect, SequenceCancelReason::Reply);
    }
}
