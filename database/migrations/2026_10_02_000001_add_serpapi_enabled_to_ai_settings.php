<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_settings', function (Blueprint $table): void {
            $table->boolean('serpapi_enabled')->default(true)->after('deepseek_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('ai_settings', function (Blueprint $table): void {
            $table->dropColumn('serpapi_enabled');
        });
    }
};
