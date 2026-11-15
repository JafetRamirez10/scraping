<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('prospects')
            ->whereNotNull('outcome')
            ->whereNull('outcome_at')
            ->update(['outcome_at' => DB::raw('updated_at')]);
    }

    public function down(): void
    {
        // The previous dates cannot be distinguished from dates saved later.
    }
};
