@php
    $searchPlaceholder = $searchPlaceholder ?? __('Search for a product or service...');
    $noResultsMessage = $noResultsMessage ?? __('No results found for ":search". Try another name or code.');
@endphp

<div
    class="relative"
    x-data="{
        activeIndex: -1,
        resultsCount: @js(count($searchResults)),
        move(step) {
            if (this.resultsCount === 0) {
                return;
            }

            this.activeIndex = (this.activeIndex + step + this.resultsCount) % this.resultsCount;
            this.scrollToActive();
        },
        selectActive() {
            if (this.resultsCount === 0) {
                return;
            }

            if (this.activeIndex < 0) {
                this.activeIndex = 0;
            }

            const activeButton = this.$refs.resultsList?.children?.[this.activeIndex];
            activeButton?.click();
        },
        resetActive() {
            this.activeIndex = -1;
        },
        scrollToActive() {
            this.$nextTick(() => {
                const activeButton = this.$refs.resultsList?.children?.[this.activeIndex];

                activeButton?.scrollIntoView({ block: 'nearest' });
            });
        },
    }"
    x-effect="
        resultsCount = @js(count($searchResults));

        if (resultsCount === 0) {
            activeIndex = -1;
        } else if (activeIndex >= resultsCount) {
            activeIndex = resultsCount - 1;
        }
    "
>
    <div class="flex items-center">
        <div class="flex items-center gap-3 px-4 rounded-xl border w-full border-gray-200 bg-white shadow-sm transition focus-within:border-primary-500 focus-within:ring-2 focus-within:ring-primary-500/20 dark:border-gray-700 dark:bg-gray-800">
            <x-heroicon-o-magnifying-glass class="h-5 w-5 shrink-0 text-gray-400" />
            <input
                type="text"
                id="product-search"
                wire:model.live.debounce.300ms="searchQuery"
                @input="resetActive()"
                @keydown.arrow-down.prevent="move(1)"
                @keydown.arrow-up.prevent="move(-1)"
                @keydown.enter.prevent="selectActive()"
                @keydown.escape.prevent="resetActive()"
                placeholder="{{ $searchPlaceholder }}"
                class="py-2.5 flex-1 min-w-0 border-0 bg-transparent p-0 text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-0 dark:text-white"
            />
        </div>
    </div>

    @if (count($searchResults) > 0)
        <div x-ref="resultsList" class="absolute z-50 mt-1.5 w-full overflow-hidden rounded-xl border border-gray-200 bg-white shadow-xl dark:border-gray-700 dark:bg-gray-800">
            @foreach ($searchResults as $result)
                <button
                    type="button"
                    wire:click="addProduct({{ $result['id'] }})"
                    @mouseenter="activeIndex = {{ $loop->index }}"
                    :class="activeIndex === {{ $loop->index }} ? 'bg-gray-50 dark:bg-gray-700' : ''"
                    class="flex w-full items-center gap-3 px-4 py-3 text-left text-sm transition hover:bg-gray-50 dark:hover:bg-gray-700"
                >
                    <span class="shrink-0 rounded-md bg-gray-100 px-1.5 py-0.5 font-mono text-xs text-gray-500 dark:bg-gray-700 dark:text-gray-400">{{ $result['code'] }}</span>
                    <span class="flex-1 font-medium text-gray-900 dark:text-white">{{ $result['name'] }}</span>
                    <div class="flex items-center gap-2">
                        <span class="font-semibold text-primary-600 dark:text-primary-400">{{ $currencySymbol ?? '$' }}{{ number_format($result['sale_price'], 2) }}</span>
                        @if ($result['unit'])
                            <span class="text-xs text-gray-400">{{ $result['unit'] }}</span>
                        @endif
                    </div>
                </button>
            @endforeach
        </div>
    @elseif ($hasSearchedProducts)
        <div class="absolute z-50 mt-1.5 w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm text-gray-600 shadow-xl dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
            {{ __($noResultsMessage, ['search' => $searchQuery]) }}
        </div>
    @endif
</div>
