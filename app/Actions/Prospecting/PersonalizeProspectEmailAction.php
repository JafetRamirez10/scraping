<?php

declare(strict_types=1);

namespace App\Actions\Prospecting;

use App\Models\AiSetting;
use App\Models\EmailTemplate;
use App\Models\Prospect;
use App\Models\ProspectEmail;
use App\Services\Ai\DeepSeekClient;
use App\Services\Mail\ProspectTemplateRenderer;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class PersonalizeProspectEmailAction
{
    public function __construct(
        private readonly DeepSeekClient $deepSeekClient,
        private readonly ProspectTemplateRenderer $templateRenderer,
    ) {}

    /**
     * @return array{subject: string, body_html: string, body_text: string, personalized_by_ai: bool}
     */
    public function execute(ProspectEmail $prospectEmail): array
    {
        $prospectEmail->loadMissing(['prospect.category', 'template']);

        return $this->personalize(
            prospect: $prospectEmail->prospect,
            template: $prospectEmail->template,
            step: (int) $prospectEmail->step,
        );
    }

    /**
     * @return array{subject: string, body_html: string, body_text: string, personalized_by_ai: bool}
     */
    public function personalize(
        Prospect $prospect,
        EmailTemplate $template,
        int $step,
        bool $force = false,
    ): array {
        $prospect->loadMissing('category');

        $baseSubject = $this->templateRenderer->renderSubject($template, $prospect);
        $baseHtml = $this->templateRenderer->renderHtmlBase($template, $prospect);
        $baseText = $this->templateRenderer->renderTextBase($template, $prospect);

        $fallback = [
            'subject' => $baseSubject,
            'body_html' => $this->templateRenderer->finalizeUnsubscribe($baseHtml, $prospect),
            'body_text' => $this->templateRenderer->finalizeUnsubscribe($baseText, $prospect),
            'personalized_by_ai' => false,
        ];

        $settings = AiSetting::current();

        if (! $force && ! $settings->deepseek_enabled) {
            return $fallback;
        }

        if ((string) config('services.deepseek.key') === '') {
            return $fallback;
        }

        if (! $force && ! $this->withinDailyLimit($settings->daily_limit)) {
            Log::warning('DeepSeek daily personalization limit reached');

            return $fallback;
        }

        try {
            $userPrompt = $this->buildUserPrompt(
                prospect: $prospect,
                step: $step,
                baseSubject: $baseSubject,
                baseHtml: $baseHtml,
                baseText: $baseText,
            );

            $response = $this->deepSeekClient->chat([
                ['role' => 'system', 'content' => $settings->resolvedSystemPrompt()],
                ['role' => 'user', 'content' => $userPrompt],
            ], $settings->deepseek_model);

            $parsed = $this->parseResponse($response['content'], [
                'subject' => $baseSubject,
                'body_html' => $baseHtml,
                'body_text' => $baseText,
                'personalized_by_ai' => false,
            ]);

            if ($parsed === null) {
                return $fallback;
            }

            $bodyHtml = $this->ensureUnsubscribePlaceholder($parsed['body_html'], $baseHtml, isHtml: true);
            $bodyText = $this->ensureUnsubscribePlaceholder($parsed['body_text'], $baseText, isHtml: false);

            if (! $force) {
                $this->incrementDailyUsage();
            }

            return [
                'subject' => $parsed['subject'],
                'body_html' => $this->templateRenderer->finalizeUnsubscribe($bodyHtml, $prospect),
                'body_text' => $this->templateRenderer->finalizeUnsubscribe($bodyText, $prospect),
                'personalized_by_ai' => true,
            ];
        } catch (Throwable $exception) {
            Log::error('DeepSeek personalization failed', [
                'prospect_id' => $prospect->id,
                'template_id' => $template->id,
                'step' => $step,
                'message' => $exception->getMessage(),
            ]);

            return $fallback;
        }
    }

    private function buildUserPrompt(
        Prospect $prospect,
        int $step,
        string $baseSubject,
        string $baseHtml,
        string $baseText,
    ): string {
        $metadata = is_array($prospect->metadata) ? $prospect->metadata : [];
        $category = $prospect->category?->name ?? '';

        $context = [
            'step' => $step,
            'company_name' => $prospect->company_name,
            'website_url' => $prospect->website_url,
            'category' => $category,
            'search_title' => $metadata['title'] ?? $prospect->company_name,
            'search_snippet' => $metadata['snippet'] ?? null,
            'search_url' => $metadata['url'] ?? $prospect->website_url,
        ];

        return "Personaliza este correo de prospección (paso {$step}).\n"
            ."Contexto JSON:\n".json_encode($context, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)."\n\n"
            ."Plantilla asunto:\n{$baseSubject}\n\n"
            ."Plantilla HTML:\n{$baseHtml}\n\n"
            ."Plantilla texto:\n{$baseText}\n\n"
            .'Devuelve JSON con subject, body_html y body_text.';
    }

    /**
     * @param  array{subject: string, body_html: string, body_text: string, personalized_by_ai: bool}  $fallback
     * @return array{subject: string, body_html: string, body_text: string}|null
     */
    private function parseResponse(string $content, array $fallback): ?array
    {
        $decoded = json_decode($content, true);

        if (! is_array($decoded)) {
            if (preg_match('/\{.*\}/s', $content, $matches) === 1) {
                $decoded = json_decode($matches[0], true);
            }
        }

        if (! is_array($decoded)) {
            return null;
        }

        $subject = trim((string) ($decoded['subject'] ?? ''));
        $bodyHtml = trim((string) ($decoded['body_html'] ?? ''));
        $bodyText = trim((string) ($decoded['body_text'] ?? ''));

        if ($subject === '' || $bodyHtml === '') {
            return null;
        }

        if ($bodyText === '') {
            $bodyText = trim(strip_tags($bodyHtml));
        }

        if (mb_strlen($subject) > 255) {
            $subject = mb_substr($subject, 0, 255);
        }

        return [
            'subject' => $subject,
            'body_html' => $bodyHtml,
            'body_text' => $bodyText !== '' ? $bodyText : $fallback['body_text'],
        ];
    }

    private function ensureUnsubscribePlaceholder(string $content, string $fallback, bool $isHtml): string
    {
        if (str_contains($content, '{{ unsubscribe_url }}')) {
            return $content;
        }

        if (! str_contains($fallback, '{{ unsubscribe_url }}')) {
            return $content;
        }

        return $isHtml
            ? rtrim($content)."\n<p><a href=\"{{ unsubscribe_url }}\">Darme de baja</a></p>"
            : rtrim($content)."\n\nDarme de baja: {{ unsubscribe_url }}";
    }

    private function withinDailyLimit(int $dailyLimit): bool
    {
        if ($dailyLimit <= 0) {
            return false;
        }

        return $this->dailyUsage() < $dailyLimit;
    }

    private function dailyUsage(): int
    {
        return (int) Cache::get($this->dailyUsageCacheKey(), 0);
    }

    private function incrementDailyUsage(): void
    {
        $key = $this->dailyUsageCacheKey();

        if (! Cache::has($key)) {
            Cache::put($key, 1, now()->endOfDay());

            return;
        }

        Cache::increment($key);
    }

    private function dailyUsageCacheKey(): string
    {
        return 'deepseek_personalizations_'.now()->format('Y-m-d');
    }
}
