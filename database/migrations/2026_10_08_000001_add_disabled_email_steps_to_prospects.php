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
            $table->boolean('disable_step_1')->default(false)->after('bounce_count');
            $table->boolean('disable_step_2')->default(false)->after('disable_step_1');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::table('prospects', function (Blueprint $table): void {
            $table->dropIndex(['created_at']);
            $table->dropColumn(['disable_step_1', 'disable_step_2']);
        });
    }
};
