<?php

declare(strict_types=1);

namespace App\Services\Prospecting;

use App\Models\DailyEmailStat;
use App\Models\ProspectEmail;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class EmailSendWindowService
{
    public function isWithinSendWindow(?Carbon $moment = null): bool
    {
        $moment ??= now();

        $allowedDays = config('prospecting.email.send_days', ['mon', 'tue', 'wed', 'thu', 'fri']);
        $dayKey = strtolower($moment->format('D'));

        if (! in_array($dayKey, $allowedDays, true)) {
            return false;
        }

        $start = config('prospecting.email.send_window_start', '09:00');
        $end = config('prospecting.email.send_window_end', '18:00');

        $time = $moment->format('H:i');

        return $time >= $start && $time <= $end;
    }

    public function nextAvailableSendTime(?Carbon $from = null): Carbon
    {
        $moment = ($from ?? now())->copy();
        $allowedDays = config('prospecting.email.send_days', ['mon', 'tue', 'wed', 'thu', 'fri']);
        $start = config('prospecting.email.send_window_start', '09:00');

        for ($attempt = 0; $attempt < 14; $attempt++) {
            $dayKey = strtolower($moment->format('D'));

            if (in_array($dayKey, $allowedDays, true)) {
                [$hour, $minute] = array_map('intval', explode(':', $start));
                $candidate = $moment->copy()->setTime($hour, $minute, 0);

                if ($candidate->lessThan($moment)) {
                    $candidate = $moment->copy();
                }

                if ($this->isWithinSendWindow($candidate)) {
                    return $candidate;
                }
            }

            $moment = $moment->addDay()->startOfDay();
        }

        return now()->addHour();
    }

    public function canSendMoreToday(): bool
    {
        $dailyLimit = (int) config('prospecting.email.daily_limit', 10);
        $sentToday = $this->sentCountToday();

        return $sentToday < $dailyLimit;
    }

    public function sentCountToday(): int
    {
        return (int) DailyEmailStat::query()
            ->whereDate('date', today())
            ->value('sent_count');
    }

    public function canSendToDomain(string $email): bool
    {
        $domain = substr(strrchr($email, '@') ?: '', 1);
        $limit = (int) config('prospecting.email.max_per_domain_per_day', 3);
        $cacheKey = 'domain_email_count:' . today()->toDateString() . ':' . $domain;
        $count = (int) Cache::get($cacheKey, 0);

        return $count < $limit;
    }

    public function recordDomainSend(string $email): void
    {
        $domain = substr(strrchr($email, '@') ?: '', 1);
        $cacheKey = 'domain_email_count:' . today()->toDateString() . ':' . $domain;
        $count = (int) Cache::get($cacheKey, 0);
        Cache::put($cacheKey, $count + 1, now()->endOfDay());
    }

    public function incrementDailyStat(string $field): void
    {
        $stat = DailyEmailStat::query()->firstOrCreate(
            ['date' => today()],
            ['sent_count' => 0, 'failed_count' => 0, 'bounced_count' => 0, 'complaint_count' => 0],
        );

        $stat->increment($field);
    }
}
