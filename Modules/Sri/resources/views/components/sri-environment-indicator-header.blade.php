<div
    x-data="{
        open: false,
        toggle() {
            this.open ? this.close() : this.open = true
        },
        close() {
            this.open = false
        }
    }"
    x-on:keydown.escape.prevent.stop="close()"
    class="fi-topbar-item"
>
    <a
        href="{{ $route }}"
        x-on:click="toggle"
        x-tooltip="{
            content: '{{ __('TEST ENVIRONMENT: Electronic documents will be sent to SRI test servers and will NOT have legal validity') }}',
            theme: $store.theme,
        }"
        class="flex items-center justify-center rounded-lg px-2 py-2 transition duration-75 bg-green-200 hover:bg-green-300 dark:bg-slate-300 dark:hover:bg-slate-200"
    >
        <img src="{{ asset('img/sri-small.png') }}" alt="{{ __('SRI') }}" class="h-5 w-5 object-contain" />
    </a>
</div>
