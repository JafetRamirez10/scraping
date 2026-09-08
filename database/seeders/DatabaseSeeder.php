<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Category;
use App\Models\EmailTemplate;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'admin@jramirezr.com'],
            [
                'name' => 'Jafet Ramirez',
                'password' => Hash::make('password'),
            ],
        );

        $categories = [
            ['name' => 'Logística', 'query' => 'empresa de logística México contacto'],
            ['name' => 'Contabilidad', 'query' => 'empresa de contabilidad México contacto'],
            ['name' => 'Bufete de abogados', 'query' => 'bufete de abogados México contacto'],
        ];

        foreach ($categories as $index => $category) {
            Category::query()->updateOrCreate(
                ['slug' => Str::slug($category['name'])],
                [
                    'name' => $category['name'],
                    'search_query' => $category['query'],
                    'target_email_count' => 10,
                    'scrape_interval_days' => 2,
                    'is_active' => true,
                    'next_scrape_at' => now()->addDays($index),
                ],
            );
        }

        $templates = [
            [
                'step' => 1,
                'name' => 'Prospectación inicial',
                'subject' => '{{ company_name }} — propuesta breve',
                'body_html' => '<p>Hola,</p><p>Soy Jafet Ramirez. Me puse en contacto porque vi {{ company_name }} y creo que podemos aportar valor en {{ category }}.</p><p>¿Tendrías 10 minutos esta semana para una llamada breve?</p><p>Saludos,<br>Jafet Ramirez<br>soporte@jramirezr.com</p><p><a href="{{ unsubscribe_url }}">Darme de baja</a></p>',
                'body_text' => "Hola,\n\nSoy Jafet Ramirez. Me puse en contacto porque vi {{ company_name }} y creo que podemos aportar valor en {{ category }}.\n\n¿Tendrías 10 minutos esta semana?\n\nSaludos,\nJafet Ramirez\n\nDarme de baja: {{ unsubscribe_url }}",
            ],
            [
                'step' => 2,
                'name' => 'Seguimiento',
                'subject' => 'Re: {{ company_name }} — información adicional',
                'body_html' => '<p>Hola de nuevo,</p><p>Te escribo por si no viste mi mensaje anterior sobre {{ company_name }}.</p><p>Con gusto comparto más detalles si te interesa.</p><p>Saludos,<br>Jafet Ramirez</p><p><a href="{{ unsubscribe_url }}">Darme de baja</a></p>',
                'body_text' => "Hola de nuevo,\n\nTe escribo por si no viste mi mensaje anterior sobre {{ company_name }}.\n\nSaludos,\nJafet Ramirez\n\nDarme de baja: {{ unsubscribe_url }}",
            ],
            [
                'step' => 3,
                'name' => 'Último contacto',
                'subject' => 'Última nota para {{ company_name }}',
                'body_html' => '<p>Hola,</p><p>Este será mi último mensaje. Si en el futuro deseas retomar la conversación, estaré disponible.</p><p>Saludos,<br>Jafet Ramirez</p><p><a href="{{ unsubscribe_url }}">Darme de baja</a></p>',
                'body_text' => "Hola,\n\nEste será mi último mensaje. Si deseas retomar la conversación, estaré disponible.\n\nSaludos,\nJafet Ramirez\n\nDarme de baja: {{ unsubscribe_url }}",
            ],
        ];

        foreach ($templates as $template) {
            EmailTemplate::query()->updateOrCreate(
                ['step' => $template['step']],
                [
                    'name' => $template['name'],
                    'subject' => $template['subject'],
                    'body_html' => $template['body_html'],
                    'body_text' => $template['body_text'],
                    'is_active' => true,
                ],
            );
        }
    }
}
