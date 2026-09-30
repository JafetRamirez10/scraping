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
        Schema::create('ai_settings', function (Blueprint $table): void {
            $table->id();
            $table->boolean('deepseek_enabled')->default(false);
            $table->string('deepseek_model', 100)->default('deepseek-chat');
            $table->unsignedSmallInteger('daily_limit')->default(200);
            $table->text('system_prompt')->nullable();
            $table->timestamps();
        });

        DB::table('ai_settings')->insert([
            'deepseek_enabled' => false,
            'deepseek_model' => 'deepseek-chat',
            'daily_limit' => 200,
            'system_prompt' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Schema::table('prospect_emails', function (Blueprint $table): void {
            $table->string('rendered_subject')->nullable()->after('error_message');
            $table->text('rendered_body_html')->nullable()->after('rendered_subject');
            $table->text('rendered_body_text')->nullable()->after('rendered_body_html');
            $table->boolean('personalized_by_ai')->default(false)->after('rendered_body_text');
        });
    }

    public function down(): void
    {
        Schema::table('prospect_emails', function (Blueprint $table): void {
            $table->dropColumn([
                'rendered_subject',
                'rendered_body_html',
                'rendered_body_text',
                'personalized_by_ai',
            ]);
        });

        Schema::dropIfExists('ai_settings');
    }
};
