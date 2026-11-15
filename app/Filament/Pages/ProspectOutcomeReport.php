<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Enums\ProspectOutcome;
use App\Models\Prospect;
use Carbon\Carbon;
use Filament\Forms;
use Illuminate\Support\Facades\DB;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProspectOutcomeReport extends Page implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static string $view = 'filament.pages.prospect-outcome-report';

    protected static ?string $navigationGroup = 'Prospección';

    protected static ?string $navigationLabel = 'Reportes de seguimiento';

    protected static ?string $title = 'Reportes de seguimiento';

    protected static ?int $navigationSort = 20;

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill($this->lastMonthRange());
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\DatePicker::make('from')
                    ->label('Desde')
                    ->required()
                    ->native(false),
                Forms\Components\DatePicker::make('to')
                    ->label('Hasta')
                    ->required()
                    ->native(false),
            ])
            ->columns(2)
            ->statePath('data');
    }

    public function apply(): void
    {
        $this->form->getState();
        $this->resetTable();
    }

    public function lastMonth(): void
    {
        $this->form->fill($this->lastMonthRange());
        $this->resetTable();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => $this->reportQuery())
            ->columns([
                Tables\Columns\TextColumn::make('company_name')->label('Empresa')->searchable(),
                Tables\Columns\TextColumn::make('email')->label('Email')->searchable(),
                Tables\Columns\TextColumn::make('category.name')->label('Categoría'),
                Tables\Columns\TextColumn::make('outcome')->label('Seguimiento')->badge(),
                Tables\Columns\TextColumn::make('followed_at')
                    ->label('Fecha')
                    ->getStateUsing(fn (Prospect $record): ?Carbon => $record->outcome_at ?? $record->updated_at)
                    ->dateTime('d/m/Y H:i'),
            ])
            ->defaultSort('outcome_at', 'desc')
            ->paginated([10, 25, 50]);
    }

    /**
     * @return array<string, int>
     */
    public function summary(): array
    {
        $counts = (clone $this->reportQuery())
            ->reorder()
            ->get(['id', 'outcome'])
            ->countBy(fn (Prospect $prospect): string => $prospect->outcome instanceof ProspectOutcome
                ? $prospect->outcome->value
                : (string) $prospect->outcome);

        $summary = [];

        foreach (ProspectOutcome::cases() as $case) {
            $summary[$case->getLabel()] = (int) ($counts[$case->value] ?? 0);
        }

        return $summary;
    }

    /**
     * @return Builder<Prospect>
     */
    private function reportQuery(): Builder
    {
        [$from, $to] = $this->range();

        return Prospect::query()
            ->with('category:id,name')
            ->whereNotNull('outcome')
            ->whereBetween(DB::raw('COALESCE(outcome_at, updated_at)'), [$from, $to]);
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function range(): array
    {
        $state = $this->form->getRawState();
        $fallback = $this->lastMonthRange();
        $from = Carbon::parse((string) ($state['from'] ?? $fallback['from']))->startOfDay();
        $to = Carbon::parse((string) ($state['to'] ?? $fallback['to']))->endOfDay();

        return [$from, $to];
    }

    /**
     * @return array{from: string, to: string}
     */
    private function lastMonthRange(): array
    {
        return [
            'from' => now()->subMonth()->toDateString(),
            'to' => now()->toDateString(),
        ];
    }
}
