<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Prospecting\HandleBrevoWebhookAction;
use App\Models\ProcessedWebhookEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class HandleBrevoWebhookController extends Controller
{
    public function __construct(
        private readonly HandleBrevoWebhookAction $handleWebhook,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $secret = config('services.brevo.webhook_secret');

        if ($secret && $request->header('X-Brevo-Token') !== $secret) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $event = (string) ($request->input('event') ?? '');
        $messageId = (string) ($request->input('message-id') ?? $request->input('messageId') ?? 'unknown');
        $eventKey = $event . ':' . $messageId;

        if (ProcessedWebhookEvent::query()->where('event_key', $eventKey)->exists()) {
            return response()->json(['message' => 'Already processed']);
        }

        ProcessedWebhookEvent::query()->create(['event_key' => $eventKey]);

        try {
            $this->handleWebhook->execute($event, $request->all());
        } catch (\Throwable $exception) {
            Log::error('Brevo webhook processing failed', [
                'event' => $event,
            ]);
        }

        return response()->json(['message' => 'OK']);
    }
}
