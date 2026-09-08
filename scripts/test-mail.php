<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Mail\TemplateTestMail;
use Illuminate\Support\Facades\Mail;

try {
    Mail::to('soporte@jramirezr.com')->send(new TemplateTestMail(
        subjectLine: '[PRUEBA] Test SMTP',
        htmlBody: '<p>Prueba SMTP desde PlanScraping</p>',
        textBody: 'Prueba SMTP desde PlanScraping',
    ));
    echo "OK: correo enviado\n";
} catch (Throwable $e) {
    echo get_class($e) . ': ' . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
