<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prospects', function (Blueprint $table): void {
            $table->timestamp('outcome_at')->nullable()->after('outcome');
        });

        Schema::table('prospect_emails', function (Blueprint $table): void {
            $table->unsignedTinyInteger('send_attempts')->default(0)->after('error_message');
            $table->timestamp('last_attempt_at')->nullable()->after('send_attempts');
        });
    }

    public function down(): void
    {
        Schema::table('prospects', function (Blueprint $table): void {
            $table->dropColumn('outcome_at');
        });

        Schema::table('prospect_emails', function (Blueprint $table): void {
            $table->dropColumn(['send_attempts', 'last_attempt_at']);
        });
    }
};
