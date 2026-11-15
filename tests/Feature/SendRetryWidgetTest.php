<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ProspectEmailStatus;
use App\Enums\ProspectStatus;
use App\Filament\Widgets\SendRetryWidget;
use App\Models\Category;
use App\Models\EmailTemplate;
use App\Models\Prospect;
use App\Models\ProspectEmail;
use App\Models\ProspectSequence;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SendRetryWidgetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs(User::factory()->create());
    }

    public function test_dashboard_retries_default_to_seven_days_and_accept_a_range(): void
    {
        $recent = $this->email('Empresa reciente', 'reciente@example.org', now()->subDays(2));
        $old = $this->email('Empresa antigua', 'antigua@example.org', now()->subDays(20));

        Livewire::test(SendRetryWidget::class)
            ->assertSee($recent->prospect->company_name)
            ->assertDontSee($old->prospect->company_name)
            ->set('data.from', now()->subDays(30)->toDateString())
            ->set('data.to', now()->toDateString())
            ->call('apply')
            ->assertSee($recent->prospect->company_name)
            ->assertSee($old->prospect->company_name);
    }

    private function email(string $company, string $address, \DateTimeInterface $attemptedAt): ProspectEmail
    {
        $category = Category::query()->create([
            'name' => 'Logística '.$company,
            'slug' => str($company)->slug()->toString(),
            'search_query' => 'logistica',
        ]);
        $template = EmailTemplate::query()->create([
            'name' => 'Paso '.$company,
            'step' => (int) EmailTemplate::query()->max('step') + 1,
            'subject' => 'Hola',
            'body_html' => '<p>Hola</p>',
        ]);
        $prospect = Prospect::query()->create([
            'category_id' => $category->id,
            'company_name' => $company,
            'email' => $address,
            'status' => ProspectStatus::InSequence,
        ]);
        $sequence = ProspectSequence::query()->create([
            'prospect_id' => $prospect->id,
            'started_at' => now(),
        ]);

        return ProspectEmail::query()->create([
            'prospect_id' => $prospect->id,
            'prospect_sequence_id' => $sequence->id,
            'email_template_id' => $template->id,
            'step' => 1,
            'scheduled_at' => now()->addHour(),
            'status' => ProspectEmailStatus::Pending,
            'send_attempts' => 1,
            'last_attempt_at' => $attemptedAt,
            'error_message' => 'Send failed',
        ]);
    }
}
