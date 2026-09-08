<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('search_query');
            $table->unsignedTinyInteger('target_email_count')->default(10);
            $table->unsignedTinyInteger('scrape_interval_days')->default(2);
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_scraped_at')->nullable();
            $table->timestamp('next_scrape_at')->nullable();
            $table->timestamps();
        });

        Schema::create('email_templates', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->unsignedTinyInteger('step');
            $table->string('subject');
            $table->text('body_html');
            $table->text('body_text')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique('step');
        });

        Schema::create('scrape_runs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->string('status', 20)->default('pending');
            $table->string('serpapi_search_id')->nullable();
            $table->unsignedSmallInteger('emails_found')->default(0);
            $table->unsignedSmallInteger('emails_saved')->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();
        });

        Schema::create('scrape_run_urls', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('scrape_run_id')->constrained()->cascadeOnDelete();
            $table->string('url', 2048);
            $table->unsignedTinyInteger('emails_extracted')->default(0);
            $table->string('status', 20)->default('pending');
            $table->timestamps();
        });

        Schema::create('prospects', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->string('company_name')->nullable();
            $table->string('website_url', 2048)->nullable();
            $table->string('email')->unique();
            $table->string('email_quality', 20)->default('medium');
            $table->string('email_source', 30)->default('website');
            $table->string('status', 30)->default('discovered');
            $table->timestamp('unsubscribed_at')->nullable();
            $table->unsignedTinyInteger('bounce_count')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['status', 'category_id']);
        });

        Schema::create('prospect_sequences', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('prospect_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('current_step')->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancel_reason', 30)->nullable();
            $table->timestamps();
        });

        Schema::create('prospect_emails', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('prospect_id')->constrained()->cascadeOnDelete();
            $table->foreignId('prospect_sequence_id')->constrained()->cascadeOnDelete();
            $table->foreignId('email_template_id')->constrained()->restrictOnDelete();
            $table->unsignedTinyInteger('step');
            $table->timestamp('scheduled_at');
            $table->timestamp('sent_at')->nullable();
            $table->string('status', 20)->default('pending');
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('clicked_at')->nullable();
            $table->timestamp('replied_at')->nullable();
            $table->string('provider_message_id')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->unique(['prospect_sequence_id', 'step']);
            $table->index(['status', 'scheduled_at']);
        });

        Schema::create('suppression_list', function (Blueprint $table): void {
            $table->id();
            $table->string('email')->unique();
            $table->string('reason', 30);
            $table->timestamp('suppressed_at');
            $table->timestamps();
        });

        Schema::create('processed_webhook_events', function (Blueprint $table): void {
            $table->id();
            $table->string('event_key')->unique();
            $table->timestamps();
        });

        Schema::create('daily_email_stats', function (Blueprint $table): void {
            $table->id();
            $table->date('date')->unique();
            $table->unsignedSmallInteger('sent_count')->default(0);
            $table->unsignedSmallInteger('failed_count')->default(0);
            $table->unsignedSmallInteger('bounced_count')->default(0);
            $table->unsignedSmallInteger('complaint_count')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_email_stats');
        Schema::dropIfExists('processed_webhook_events');
        Schema::dropIfExists('suppression_list');
        Schema::dropIfExists('prospect_emails');
        Schema::dropIfExists('prospect_sequences');
        Schema::dropIfExists('prospects');
        Schema::dropIfExists('scrape_run_urls');
        Schema::dropIfExists('scrape_runs');
        Schema::dropIfExists('email_templates');
        Schema::dropIfExists('categories');
    }
};
