<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiSetting extends Model
{
    public const SERPAPI_DISABLED_MESSAGE = 'SerpAPI / SearchAPI está desactivado en la configuración. Actívalo en Filament (Integraciones) para ejecutar scrapes. / SerpAPI / SearchAPI is disabled in settings. Enable it in Filament (Integrations) to run scrapes.';

    protected $fillable = [
        'deepseek_enabled',
        'serpapi_enabled',
        'deepseek_model',
        'daily_limit',
        'system_prompt',
    ];

    protected function casts(): array
    {
        return [
            'deepseek_enabled' => 'boolean',
            'serpapi_enabled' => 'boolean',
            'daily_limit' => 'integer',
        ];
    }

    public static function current(): self
    {
        return static::query()->firstOrCreate(
            ['id' => 1],
            [
                'deepseek_enabled' => false,
                'serpapi_enabled' => true,
                'deepseek_model' => (string) config('services.deepseek.model', 'deepseek-chat'),
                'daily_limit' => (int) config('services.deepseek.daily_limit', 200),
                'system_prompt' => null,
            ],
        );
    }

    public static function serpApiEnabled(): bool
    {
        return (bool) static::current()->serpapi_enabled;
    }

    public function defaultSystemPrompt(): string
    {
        return <<<'PROMPT'
Eres un copywriter de cold email B2B en español (LATAM).
Reescribe asunto y cuerpo usando la plantilla como base y el contexto de la empresa.

Asunto (subject) — muy importante:
- Debe parecer un mensaje humano/frío, no publicidad ni newsletter.
- Corto (máx. ~7 palabras), específico y fácil de abrir.
- Usa curiosidad suave o relevancia concreta del contexto (empresa, categoría, snippet).
- Evita mayúsculas excesivas, emojis, signos de exclamación y palabras spam: gratis, oferta, oportunidad única, increíble, descuento, urgente, 100%, limited.
- No uses fórmulas típicas de marketing tipo "Mejora tus ventas con..." o "Descubre cómo...".
- Prefiere tonos naturales, p. ej. referencia a un detalle real o una pregunta breve.

Cuerpo:
- Tono profesional, breve y natural. No seas genérico ni vendedor agresivo.
- Usa solo datos del contexto; no inventes métricas, clientes ni logros.
- Conserva la intención y el CTA de la plantilla.
- No incluyas enlaces ni texto de darse de baja, cancelar suscripción o unsubscribe.

Responde SOLO JSON válido con las claves: subject, body_html, body_text.
PROMPT;
    }

    public function resolvedSystemPrompt(): string
    {
        $prompt = trim((string) $this->system_prompt);

        return $prompt !== '' ? $prompt : $this->defaultSystemPrompt();
    }
}
