<div class="flex flex-wrap items-center gap-y-2">
    @foreach ($badges as $badge)
        @if (! $loop->first)
            <div class="mx-3 h-4 w-px self-center bg-gray-200 dark:bg-white/20" aria-hidden="true"></div>
        @endif
        <div class="flex items-center gap-2">
            <span class="text-xs font-normal text-gray-500 dark:text-gray-600 uppercase">{{ $badge['title'] }}:</span>
            <x-filament::badge :color="$badge['color']">
                {{ $badge['label'] }}
            </x-filament::badge>
        </div>
    @endforeach
</div>
