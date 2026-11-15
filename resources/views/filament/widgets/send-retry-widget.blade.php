<x-filament-widgets::widget>
    <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <h2 class="text-base font-semibold">Reintentos de envío</h2>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Correos que fallaron y se volvieron a intentar.</p>

        <form wire:submit="apply" class="mt-4 space-y-4">
            {{ $this->form }}

            <div class="flex flex-wrap gap-3">
                <x-filament::button type="submit">
                    Ver rango
                </x-filament::button>
                <x-filament::button type="button" color="gray" wire:click="lastSevenDays">
                    Últimos 7 días
                </x-filament::button>
            </div>
        </form>

        @php($summary = $this->summary())
        <div class="mt-6 grid gap-3 sm:grid-cols-3">
            <div class="rounded-lg bg-gray-50 p-3 dark:bg-white/5">
                <div class="text-sm text-gray-500 dark:text-gray-400">En el periodo</div>
                <div class="mt-1 text-2xl font-semibold">{{ $summary['total'] }}</div>
            </div>
            <div class="rounded-lg bg-gray-50 p-3 dark:bg-white/5">
                <div class="text-sm text-gray-500 dark:text-gray-400">Pendientes de reintento</div>
                <div class="mt-1 text-2xl font-semibold">{{ $summary['pending'] }}</div>
            </div>
            <div class="rounded-lg bg-gray-50 p-3 dark:bg-white/5">
                <div class="text-sm text-gray-500 dark:text-gray-400">Fallidos</div>
                <div class="mt-1 text-2xl font-semibold">{{ $summary['failed'] }}</div>
            </div>
        </div>

        <div class="mt-6">
            {{ $this->table }}
        </div>
    </div>
</x-filament-widgets::widget>
