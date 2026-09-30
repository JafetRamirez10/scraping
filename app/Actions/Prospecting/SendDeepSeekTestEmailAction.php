<?php

declare(strict_types=1);

namespace App\Actions\Prospecting;

use App\Mail\TemplateTestMail;
use App\Models\EmailTemplate;
use App\Models\Prospect;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Throwable;

class SendDeepSeekTestEmailAction
{
    public function __construct(
        private readonly PersonalizeProspectEmailAction $personalizeProspectEmail,
    ) {}

    /**
     * @return array{personalized_by_ai: bool, subject: string}
     */
    public function execute(EmailTemplate $template, Prospect $prospect, string $recipientEmail): array
    {
        if ((string) config('services.deepseek.key') === '') {
            throw ValidationException::withMessages([
                'email' => 'Falta DEEPSEEK_API_KEY en el .env del servidor.',
            ]);
        }

        $prospect->loadMissing('category');

        $rendered = $this->personalizeProspectEmail->personalize(
            prospect: $prospect,
            template: $template,
            step: (int) $template->step,
            force: true,
        );

        try {
            Mail::to($recipientEmail)->send(new TemplateTestMail(
                subjectLine: '[PRUEBA IA] '.$rendered['subject'],
                htmlBody: $rendered['body_html'],
                textBody: $rendered['body_text'],
            ));
        } catch (Throwable $exception) {
            Log::error('DeepSeek test email send failed', [
                'template_id' => $template->id,
                'prospect_id' => $prospect->id,
                'error' => $exception->getMessage(),
            ]);

            throw ValidationException::withMessages([
                'email' => 'No se pudo enviar el correo de prueba. Verifica SMTP/Brevo.',
            ]);
        }

        return [
            'personalized_by_ai' => $rendered['personalized_by_ai'],
            'subject' => $rendered['subject'],
        ];
    }
}
