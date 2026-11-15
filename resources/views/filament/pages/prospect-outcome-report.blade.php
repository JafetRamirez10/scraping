<x-filament-panels::page>
    <form wire:submit="apply" class="space-y-4">
        {{ $this->form }}

        <div class="flex flex-wrap gap-3">
            <x-filament::button type="submit">
                Ver rango
            </x-filament::button>
            <x-filament::button type="button" color="gray" wire:click="lastMonth">
                Último mes
            </x-filament::button>
        </div>
    </form>

    <div class="mt-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
        @foreach ($this->summary() as $label => $total)
            <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="text-sm text-gray-500 dark:text-gray-400">{{ $label }}</div>
                <div class="mt-1 text-2xl font-semibold">{{ $total }}</div>
            </div>
        @endforeach
    </div>

    <div class="mt-6">
        {{ $this->table }}
    </div>
</x-filament-panels::page>
