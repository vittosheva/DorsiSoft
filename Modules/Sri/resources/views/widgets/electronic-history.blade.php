<x-filament-widgets::widget>
    @if ($this->shouldShowWidget())
        {{ $this->content }}
    @endif
</x-filament-widgets::widget>
