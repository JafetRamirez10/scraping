<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ProspectOutcome;
use App\Enums\ProspectStatus;
use App\Filament\Pages\ProspectOutcomeReport;
use App\Models\Category;
use App\Models\Prospect;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProspectOutcomeReportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs(User::factory()->create());
    }

    public function test_report_defaults_to_the_last_month_and_accepts_a_date_range(): void
    {
        $category = Category::query()->create([
            'name' => 'Logística',
            'slug' => 'logistica-reporte',
            'search_query' => 'logistica',
        ]);

        Prospect::query()->create([
            'category_id' => $category->id,
            'company_name' => 'Empresa reciente',
            'email' => 'reciente@example.org',
            'status' => ProspectStatus::InSequence,
            'outcome' => ProspectOutcome::Hired,
            'outcome_at' => now()->subDays(3),
        ]);
        Prospect::query()->create([
            'category_id' => $category->id,
            'company_name' => 'Empresa antigua',
            'email' => 'antigua@example.org',
            'status' => ProspectStatus::InSequence,
            'outcome' => ProspectOutcome::NotInterested,
            'outcome_at' => now()->subMonths(3),
        ]);

        Livewire::test(ProspectOutcomeReport::class)
            ->assertSee('Empresa reciente')
            ->assertDontSee('Empresa antigua')
            ->set('data.from', now()->subMonths(4)->toDateString())
            ->set('data.to', now()->toDateString())
            ->call('apply')
            ->assertSee('Empresa reciente')
            ->assertSee('Empresa antigua');
    }
}
