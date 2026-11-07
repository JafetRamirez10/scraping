<?php

use App\Jobs\ActivateDiscoveredProspectsJob;
use App\Jobs\CheckEngagementTimeoutsJob;
use App\Jobs\ProcessDueCategoryScrapesJob;
use App\Jobs\SendScheduledProspectEmailsJob;
use App\Jobs\UpdateSerpApiCreditsCacheJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::job(new ProcessDueCategoryScrapesJob)->everyThirtyMinutes();
Schedule::job(new ActivateDiscoveredProspectsJob)->everyFifteenMinutes();
Schedule::job(new SendScheduledProspectEmailsJob)
    ->hourly()
    ->when(fn (): bool => strtolower((string) config('mail.provider', 'brevo')) !== 'mailrush');

Schedule::job(new SendScheduledProspectEmailsJob)
    ->everyMinute()
    ->when(fn (): bool => strtolower((string) config('mail.provider', 'brevo')) === 'mailrush');
Schedule::job(new CheckEngagementTimeoutsJob)->everySixHours();
Schedule::job(new UpdateSerpApiCreditsCacheJob)->dailyAt('02:00');
