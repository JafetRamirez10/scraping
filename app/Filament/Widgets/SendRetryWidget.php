<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Enums\ProspectEmailStatus;
use App\Filament\Resources\ProspectResource;
use App\Models\ProspectEmail;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Builder;

class SendRetryWidget extends Widget implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    protected static string $view = 'filament.widgets.send-retry-widget';

    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 'full';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill($this->lastSevenDaysRange());
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

    public function lastSevenDays(): void
    {
        $this->form->fill($this->lastSevenDaysRange());
        $this->resetTable();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => $this->retryQuery())
            ->columns([
                Tables\Columns\TextColumn::make('prospect.company_name')
                    ->label('Empresa')
                    ->url(fn (ProspectEmail $record): string => ProspectResource::getUrl('view', ['record' => $record->prospect_id])),
                Tables\Columns\TextColumn::make('prospect.email')->label('Email'),
                Tables\Columns\TextColumn::make('step')->label('Paso'),
                Tables\Columns\TextColumn::make('send_attempts')->label('Intentos fallidos'),
                Tables\Columns\TextColumn::make('status')->label('Estado')->badge(),
                Tables\Columns\TextColumn::make('last_attempt_at')->label('Último intento')->dateTime('d/m/Y H:i'),
                Tables\Columns\TextColumn::make('scheduled_at')->label('Próximo envío')->dateTime('d/m/Y H:i'),
            ])
            ->defaultSort('last_attempt_at', 'desc')
            ->paginated([10, 25, 50]);
    }

    /**
     * @return array{total: int, pending: int, failed: int}
     */
    public function summary(): array
    {
        $query = $this->retryQuery();

        return [
            'total' => (clone $query)->count(),
            'pending' => (clone $query)->where('status', ProspectEmailStatus::Pending)->count(),
            'failed' => (clone $query)->where('status', ProspectEmailStatus::Failed)->count(),
        ];
    }

    /**
     * @return Builder<ProspectEmail>
     */
    private function retryQuery(): Builder
    {
        [$from, $to] = $this->range();

        return ProspectEmail::query()
            ->with('prospect:id,company_name,email')
            ->where('send_attempts', '>', 0)
            ->whereBetween('last_attempt_at', [$from, $to]);
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function range(): array
    {
        $state = $this->form->getRawState();
        $fallback = $this->lastSevenDaysRange();
        $from = Carbon::parse((string) ($state['from'] ?? $fallback['from']))->startOfDay();
        $to = Carbon::parse((string) ($state['to'] ?? $fallback['to']))->endOfDay();

        return [$from, $to];
    }

    /**
     * @return array{from: string, to: string}
     */
    private function lastSevenDaysRange(): array
    {
        return [
            'from' => now()->subDays(6)->toDateString(),
            'to' => now()->toDateString(),
        ];
    }
}
