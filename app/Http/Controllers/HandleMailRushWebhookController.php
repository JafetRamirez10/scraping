<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Prospecting\HandleMailRushWebhookAction;
use App\Models\ProcessedWebhookEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class HandleMailRushWebhookController extends Controller
{
    public function __construct(
        private readonly HandleMailRushWebhookAction $handleWebhook,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $secret = (string) config('services.mailrush.webhook_secret');
        $rawBody = $request->getContent();

        if ($secret !== '' && ! $this->signatureIsValid($rawBody, $secret, (string) $request->header('X-MailRush-Signature'))) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $payload = json_decode($rawBody, true);
        $payload = is_array($payload) ? $payload : [];

        $eventType = (string) ($payload['event_type'] ?? '');
        $eventId = (string) ($payload['event_id'] ?? 'unknown');
        $organizationId = (string) ($payload['organization_id'] ?? '');
        $eventKey = 'mailrush:'.hash('sha256', $organizationId.'|'.$eventType.'|'.$eventId);

        if (ProcessedWebhookEvent::query()->where('event_key', $eventKey)->exists()) {
            return response()->json(['message' => 'Already processed']);
        }

        ProcessedWebhookEvent::query()->create(['event_key' => $eventKey]);

        try {
            $this->handleWebhook->execute($payload);
        } catch (\Throwable $exception) {
            Log::error('MailRush webhook processing failed', [
                'event_type' => $eventType,
            ]);
        }

        return response()->json(['message' => 'OK']);
    }

    private function signatureIsValid(string $rawBody, string $secret, string $header): bool
    {
        $expected = 'sha256='.hash_hmac('sha256', $rawBody, $secret);

        return hash_equals($expected, strtolower(trim($header)));
    }
}
