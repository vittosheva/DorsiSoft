@props([
    'actions' => [],
    'actionsAlignment' => null,
    'breadcrumbs' => [],
    'heading' => null,
    'subheading' => null,
    'beforeActions' => [],
])
<header
    {{
        $attributes->class([
            'fi-header',
            'fi-header-has-breadcrumbs' => $breadcrumbs,
        ])
    }}
>
    <div>
        @if ($breadcrumbs)
            <x-filament::breadcrumbs :breadcrumbs="$breadcrumbs" />
        @endif

        @if (filled($heading))
            @if(filled($beforeActions))
                <div class="fi-page-header-with-before-actions flex items-center gap-x-3">
                    {{-- Before-heading actions (e.g. back button) --}}
                    <div class="flex shrink-0 items-center gap-x-1">
                        @foreach ($beforeActions as $action)
                            {{ $action }}
                        @endforeach
                    </div>
                    <h1 class="fi-header-heading">
                        {{ $heading }}
                    </h1>
                </div>
            @else
                <h1 class="fi-header-heading">
                    {{ $heading }}
                </h1>
            @endif
        @endif

        @if (filled($subheading))
            <p class="fi-header-subheading">
                {{ $subheading }}
            </p>
        @endif
    </div>

    @php
        $beforeActions = \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::PAGE_HEADER_ACTIONS_BEFORE, scopes: $this->getRenderHookScopes());
        $afterActions = \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::PAGE_HEADER_ACTIONS_AFTER, scopes: $this->getRenderHookScopes());
    @endphp

    @if (filled($beforeActions) || $actions || filled($afterActions))
        <div class="fi-header-actions-ctn">
            {{ $beforeActions }}

            @if ($actions)
                <x-filament::actions
                    :actions="$actions"
                    :alignment="$actionsAlignment"
                />
            @endif

            {{ $afterActions }}
        </div>
    @endif
</header>
