<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\Prospecting\CheckEngagementTimeoutsAction;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class CheckEngagementTimeoutsJob implements ShouldQueue
{
    use Queueable;

    public function handle(CheckEngagementTimeoutsAction $action): void
    {
        $action->execute();
    }
}
