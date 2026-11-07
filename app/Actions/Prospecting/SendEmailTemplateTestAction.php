<?php

declare(strict_types=1);

namespace App\Actions\Prospecting;

use App\Mail\TemplateTestMail;
use App\Models\EmailTemplate;
use App\Services\Mail\OutboundMailer;
use App\Services\Mail\ProspectTemplateRenderer;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Throwable;

class SendEmailTemplateTestAction
{
    public function __construct(
        private readonly ProspectTemplateRenderer $templateRenderer,
        private readonly OutboundMailer $outboundMailer,
    ) {}

    public function execute(EmailTemplate $template, string $recipientEmail): void
    {
        $validated = Validator::make(
            ['email' => $recipientEmail],
            ['email' => ['required', 'email', 'max:255']],
        )->validate();

        $recipientEmail = $validated['email'];

        try {
            $subject = '[PRUEBA] '.$this->templateRenderer->renderSubjectPreview($template);
            $html = $this->templateRenderer->renderHtmlPreview($template);
            $text = $this->templateRenderer->renderTextPreview($template);

            $this->outboundMailer->send(
                mailable: new TemplateTestMail(
                    subjectLine: $subject,
                    htmlBody: $html,
                    textBody: $text,
                ),
                toEmail: $recipientEmail,
                toName: null,
                subject: $subject,
                html: $html,
                text: $text,
            );
        } catch (Throwable $exception) {
            Log::error('Email template test send failed', [
                'template_id' => $template->id,
                'step' => $template->step,
                'error' => $exception->getMessage(),
            ]);

            throw ValidationException::withMessages([
                'email' => 'No se pudo enviar el correo de prueba. Verifica el proveedor activo en MAIL_PROVIDER.',
            ]);
        }
    }
}
