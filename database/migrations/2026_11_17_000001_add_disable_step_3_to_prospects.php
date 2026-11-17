<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prospects', function (Blueprint $table): void {
            $table->boolean('disable_step_3')->default(false)->after('disable_step_2');
        });

        DB::table('prospects')
            ->where('disable_step_2', true)
            ->update(['disable_step_3' => true]);

        DB::table('prospect_emails')
            ->where('step', 3)
            ->where('status', 'pending')
            ->whereIn('prospect_id', function ($query): void {
                $query->select('id')
                    ->from('prospects')
                    ->where('disable_step_2', true);
            })
            ->update([
                'status' => 'skipped',
                'error_message' => 'step_disabled',
            ]);
    }

    public function down(): void
    {
        Schema::table('prospects', function (Blueprint $table): void {
            $table->dropColumn('disable_step_3');
        });
    }
};
