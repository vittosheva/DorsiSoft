<div class="flex items-center gap-2">
    <span class="text-xs font-normal text-gray-500 dark:text-gray-600 uppercase">{{ __('Status') }}:</span>
    <x-filament::badge :color="$state->getColor()">
        {{ $state->getLabel() }}
    </x-filament::badge>
</div>
