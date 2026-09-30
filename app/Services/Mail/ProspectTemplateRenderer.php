<?php

declare(strict_types=1);

namespace App\Services\Mail;

use App\Models\EmailTemplate;

class ProspectTemplateRenderer
{
    /** @var array<string, string> */
    private array $previewDefaults = [
        'company_name' => 'Empresa de Ejemplo S.A.',
        'category' => 'Logística',
        'unsubscribe_url' => '#',
    ];

    public function renderSubject(EmailTemplate $template, object $prospect): string
    {
        return $this->replaceVariables($template->subject, $prospect);
    }

    public function renderHtml(EmailTemplate $template, object $prospect): string
    {
        return $this->finalizeUnsubscribe(
            $this->renderHtmlBase($template, $prospect),
            $prospect,
        );
    }

    public function renderText(EmailTemplate $template, object $prospect): string
    {
        return $this->finalizeUnsubscribe(
            $this->renderTextBase($template, $prospect),
            $prospect,
        );
    }

    public function renderHtmlBase(EmailTemplate $template, object $prospect): string
    {
        return $this->replaceVariables($template->body_html, $prospect);
    }

    public function renderTextBase(EmailTemplate $template, object $prospect): string
    {
        if ($template->body_text) {
            return $this->replaceVariables($template->body_text, $prospect);
        }

        return strip_tags($this->renderHtmlBase($template, $prospect));
    }

    public function finalizeUnsubscribe(string $content, object $prospect): string
    {
        return $this->replaceUnsubscribeUrl($content, $prospect);
    }

    public function renderSubjectPreview(EmailTemplate $template): string
    {
        return $this->replacePreviewVariables($template->subject);
    }

    public function renderHtmlPreview(EmailTemplate $template): string
    {
        return $this->replacePreviewVariables($template->body_html);
    }

    public function renderTextPreview(EmailTemplate $template): string
    {
        $body = $template->body_text
            ? $this->replacePreviewVariables($template->body_text)
            : strip_tags($this->renderHtmlPreview($template));

        return $body;
    }

    private function replaceVariables(string $content, object $prospect): string
    {
        $companyName = $prospect->company_name ?? 'su empresa';
        $categoryName = '';

        if (isset($prospect->category)) {
            $categoryName = is_object($prospect->category)
                ? (string) ($prospect->category->name ?? '')
                : (string) $prospect->category;
        }

        return str_replace(
            ['{{ company_name }}', '{{ category }}'],
            [$companyName, $categoryName],
            $content
        );
    }

    private function replaceUnsubscribeUrl(string $content, object $prospect): string
    {
        if (! isset($prospect->id)) {
            return str_replace('{{ unsubscribe_url }}', $this->previewDefaults['unsubscribe_url'], $content);
        }

        $unsubscribeUrl = \Illuminate\Support\Facades\URL::signedRoute('unsubscribe', ['prospect' => $prospect->id]);

        return str_replace('{{ unsubscribe_url }}', $unsubscribeUrl, $content);
    }

    private function replacePreviewVariables(string $content): string
    {
        return str_replace(
            ['{{ company_name }}', '{{ category }}', '{{ unsubscribe_url }}'],
            [
                $this->previewDefaults['company_name'],
                $this->previewDefaults['category'],
                $this->previewDefaults['unsubscribe_url'],
            ],
            $content
        );
    }
}
