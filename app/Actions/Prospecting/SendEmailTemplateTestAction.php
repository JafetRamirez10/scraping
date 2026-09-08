<?php

declare(strict_types=1);

namespace App\Actions\Prospecting;

use App\Mail\TemplateTestMail;
use App\Models\EmailTemplate;
use App\Services\Mail\ProspectTemplateRenderer;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Throwable;

class SendEmailTemplateTestAction
{
    public function __construct(
        private readonly ProspectTemplateRenderer $templateRenderer,
    ) {}

    public function execute(EmailTemplate $template, string $recipientEmail): void
    {
        $validated = Validator::make(
            ['email' => $recipientEmail],
            ['email' => ['required', 'email', 'max:255']],
        )->validate();

        $recipientEmail = $validated['email'];

        try {
            $subject = '[PRUEBA] ' . $this->templateRenderer->renderSubjectPreview($template);

            Mail::to($recipientEmail)->send(new TemplateTestMail(
                subjectLine: $subject,
                htmlBody: $this->templateRenderer->renderHtmlPreview($template),
                textBody: $this->templateRenderer->renderTextPreview($template),
            ));
        } catch (Throwable $exception) {
            Log::error('Email template test send failed', [
                'template_id' => $template->id,
                'step' => $template->step,
                'error' => $exception->getMessage(),
            ]);

            throw ValidationException::withMessages([
                'email' => 'No se pudo enviar el correo de prueba. Verifica la configuración SMTP de Brevo.',
            ]);
        }
    }
}
