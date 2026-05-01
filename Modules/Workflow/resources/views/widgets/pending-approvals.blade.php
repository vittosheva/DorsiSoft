@php
    $items = $this->getPendingItems();
@endphp

<div>
    <x-filament::section>
        <x-slot name="heading">
            {{ __('Pending approvals') }}
        </x-slot>

        @if (!empty($items))
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($items as $item)
                    <div class="flex items-center gap-3 rounded-lg border border-warning-200 bg-warning-50 px-4 py-3 dark:border-warning-700 dark:bg-warning-950">
                        <x-filament::icon
                            icon="heroicon-o-clock"
                            class="h-6 w-6 shrink-0 text-warning-500 dark:text-warning-400"
                        />
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-xs text-warning-600 dark:text-warning-400">
                                {{ $item['label'] }}
                            </p>
                            <p class="text-2xl font-bold leading-none text-warning-700 dark:text-warning-300">
                                {{ number_format($item['count']) }}
                            </p>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center text-gray-400 py-8">
                {{ __('No pending documents') }}
            </div>
        @endif
    </x-filament::section>
</div>
