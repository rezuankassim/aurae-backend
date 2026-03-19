<x-filament-widgets::widget class="fi-wi-dashboard-date-filter">
    <x-filament::section>
        <x-slot name="heading">Order Statistics</x-slot>
        <x-slot name="description">{{ $this->getLabel() }}</x-slot>

        <form wire:submit.prevent>
            {{ $this->form }}
        </form>

        <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div class="fi-wi-stats-overview-stat rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="flex items-center gap-x-2">
                    <x-filament::icon
                        icon="lucide-inbox"
                        class="fi-wi-stats-overview-stat-icon h-5 w-5 text-gray-400 dark:text-gray-500"
                    />
                    <span class="text-sm font-medium text-gray-500 dark:text-gray-400">
                        Total Orders
                    </span>
                </div>
                <div class="mt-2 text-3xl font-bold tracking-tight text-gray-950 dark:text-white">
                    {{ number_format($ordersCount) }}
                </div>
            </div>

            <div class="fi-wi-stats-overview-stat rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="flex items-center gap-x-2">
                    <x-filament::icon
                        icon="lucide-circle-dollar-sign"
                        class="fi-wi-stats-overview-stat-icon h-5 w-5 text-gray-400 dark:text-gray-500"
                    />
                    <span class="text-sm font-medium text-gray-500 dark:text-gray-400">
                        Total Revenue
                    </span>
                </div>
                <div class="mt-2 text-3xl font-bold tracking-tight text-gray-950 dark:text-white">
                    RM {{ $revenue }}
                </div>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
