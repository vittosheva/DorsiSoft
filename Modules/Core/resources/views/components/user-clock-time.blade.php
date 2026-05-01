<div
    class="fi-dropdown-list-item fi-ac-grouped-action"
    x-data="{}"
>
    <x-filament::icon-button icon="heroicon-o-clock" label="Language switcher" class="fi-icon fi-size-md" />
    <span
        x-data='{ time: new Date().toLocaleTimeString("{{ $locale }}", { timeZone: "{{ $timezone }}" }) }'
        x-init='setInterval(() => time = new Date().toLocaleTimeString("{{ $locale }}", { timeZone: "{{ $timezone }}" }), {{ $refreshInterval }})'
        x-text="time"
    ></span>
</div>