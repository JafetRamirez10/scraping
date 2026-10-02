<?php

declare(strict_types=1);

return [
    'scrape' => [
        'target_per_category' => (int) env('SCRAPE_TARGET_PER_CATEGORY', 10),
        'interval_days' => (int) env('SCRAPE_INTERVAL_DAYS', 2),
        'delay_seconds' => (int) env('SCRAPE_DELAY_SECONDS', 3),
        'website_timeout' => (int) env('SCRAPE_WEBSITE_TIMEOUT', 10),
    ],

    'email' => [
        'daily_limit' => (int) env('PROSPECT_DAILY_EMAIL_LIMIT', 10),
        'max_per_domain_per_day' => (int) env('MAX_EMAILS_PER_DOMAIN_PER_DAY', 3),
        'max_per_category_per_day' => (int) env('MAX_EMAILS_PER_CATEGORY_PER_DAY', 10),
        'send_window_start' => env('EMAIL_SEND_WINDOW_START', '09:00'),
        'send_window_end' => env('EMAIL_SEND_WINDOW_END', '18:00'),
        'send_days' => array_filter(explode(',', env('EMAIL_SEND_DAYS', 'mon,tue,wed,thu,fri'))),
    ],

    'sequence' => [
        'activate_discovered_batch' => (int) env('ACTIVATE_DISCOVERED_BATCH', 50),
        'step1_open_window_days' => (int) env('STEP1_OPEN_WINDOW_DAYS', 5),
        'step2_schedule_days_after_open' => (int) env('STEP2_SCHEDULE_DAYS_AFTER_OPEN', 5),
        'step3_schedule_days_after_step2' => (int) env('STEP3_SCHEDULE_DAYS_AFTER_STEP2', 15),
        'step2_engagement_window_days' => (int) env('STEP2_ENGAGEMENT_WINDOW_DAYS', 7),
    ],


    'alerts' => [
        'bounce_rate_threshold' => (float) env('BOUNCE_RATE_ALERT_THRESHOLD', 0.04),
        'complaint_rate_threshold' => (float) env('COMPLAINT_RATE_ALERT_THRESHOLD', 0.001),
        'serpapi_credits_threshold' => (int) env('SERPAPI_CREDITS_ALERT_THRESHOLD', 20),
    ],
];
