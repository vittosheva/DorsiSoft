<div class="space-y-3">
    {{-- Product search bar --}}
    <div class="relative">
        <div class="flex items-center gap-3 rounded-xl border border-gray-200 bg-white px-4 py-2.5 shadow-sm transition focus-within:border-primary-500 focus-within:ring-2 focus-within:ring-primary-500/20 dark:border-gray-700 dark:bg-gray-800">
            <x-heroicon-o-magnifying-glass class="h-4 w-4 shrink-0 text-gray-400" />
            <input
                type="text"
                wire:model.live.debounce.300ms="searchQuery"
                placeholder="{{ __('Search for a product or service...') }}"
                class="w-full border-0 bg-transparent p-0 text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-0 dark:text-white"
            />
        </div>

        @if (count($searchResults) > 0)
            <div class="absolute z-50 mt-1.5 w-full overflow-hidden rounded-xl border border-gray-200 bg-white shadow-xl dark:border-gray-700 dark:bg-gray-800">
                @foreach ($searchResults as $result)
                    <button
                        type="button"
                        wire:click="addProduct({{ $result['id'] }})"
                        class="flex w-full items-center gap-3 px-4 py-3 text-left text-sm transition hover:bg-gray-50 dark:hover:bg-gray-700"
                    >
                        <span class="shrink-0 rounded-md bg-gray-100 px-1.5 py-0.5 font-mono text-xs text-gray-500 dark:bg-gray-700 dark:text-gray-400">{{ $result['code'] }}</span>
                        <span class="flex-1 font-medium text-gray-900 dark:text-white">{{ $result['name'] }}</span>
                        <div class="flex items-center gap-2">
                            <span class="font-semibold text-primary-600 dark:text-primary-400">${{ number_format($result['sale_price'], 2) }}</span>
                            @if ($result['unit'])
                                <span class="text-xs text-gray-400">{{ $result['unit'] }}</span>
                            @endif
                        </div>
                    </button>
                @endforeach
            </div>
        @endif
    </div>

    {{-- Items list --}}
    @if ($document && $document->items->isNotEmpty())
        <div class="overflow-hidden rounded-xl border border-gray-200 shadow-sm dark:border-gray-700">
            {{-- Column headers — dark --}}
            <div class="grid grid-cols-12 gap-2 bg-primary-800 px-4 py-3 text-xs font-semibold uppercase tracking-widest text-gray-200 dark:bg-gray-900">
                <div class="col-span-4">{{ __('Description') }}</div>
                <div class="col-span-1 text-center">{{ __('Quantity') }}</div>
                <div class="col-span-2 text-center">{{ __('Price') }}</div>
                <div class="col-span-1 text-center">{{ __('Discount amount') }}</div>
                <div class="col-span-2 text-center">{{ __('Taxes') }}</div>
                <div class="col-span-1 text-right">{{ __('Total') }}</div>
                <div class="col-span-1"></div>
            </div>

            @foreach ($document->items as $item)
                <div class="border-b border-gray-100 bg-white last:border-0 dark:border-gray-700/50 dark:bg-gray-800">
                    {{-- Main item row --}}
                    <div class="grid grid-cols-12 items-center gap-2 px-4 py-3 transition-colors hover:bg-gray-50/80 dark:hover:bg-gray-700/20">
                        {{-- Expand toggle + description --}}
                        <div class="col-span-4 flex items-center gap-2">
                            <button
                                type="button"
                                wire:click="toggleExpand({{ $item->id }})"
                                class="shrink-0 rounded p-0.5 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-gray-700"
                            >
                                @if ($expandedItems[$item->id] ?? false)
                                    <x-heroicon-s-chevron-up class="h-3.5 w-3.5" />
                                @else
                                    <x-heroicon-s-chevron-down class="h-3.5 w-3.5" />
                                @endif
                            </button>
                            <div class="min-w-0 flex-1">
                                <input
                                    type="text"
                                    value="{{ $item->description ?? $item->product_name }}"
                                    wire:change="updateItemField({{ $item->id }}, 'description', $event.target.value)"
                                    class="w-full border-0 bg-transparent p-0 text-sm font-medium text-gray-900 focus:outline-none focus:ring-0 dark:text-white"
                                />
                                @if ($item->product_code)
                                    <span class="inline-block rounded-md bg-gray-100 px-1.5 py-0.5 font-mono text-xs text-gray-500 dark:bg-gray-700 dark:text-gray-400">
                                        {{ $item->product_code }}
                                    </span>
                                @endif
                            </div>
                        </div>

                        {{-- Quantity --}}
                        <div class="col-span-1">
                            <input
                                type="number"
                                value="{{ $item->quantity }}"
                                min="0.000001"
                                step="0.01"
                                wire:change="updateItemField({{ $item->id }}, 'quantity', $event.target.value)"
                                class="w-full rounded-lg border border-gray-200 bg-gray-50 px-2 py-1.5 text-center text-sm transition focus:border-primary-500 focus:bg-white focus:ring-2 focus:ring-primary-500/20 dark:border-gray-600 dark:bg-gray-700/50 dark:text-white dark:focus:bg-gray-700"
                            />
                        </div>

                        {{-- Unit price --}}
                        <div class="col-span-2 flex items-center gap-1">
                            <span class="shrink-0 text-xs font-medium text-gray-400">$</span>
                            <input
                                type="number"
                                value="{{ $item->unit_price }}"
                                min="0"
                                step="0.01"
                                wire:change="updateItemField({{ $item->id }}, 'unit_price', $event.target.value)"
                                class="w-full rounded-lg border border-gray-200 bg-gray-50 px-2 py-1.5 text-center text-sm transition focus:border-primary-500 focus:bg-white focus:ring-2 focus:ring-primary-500/20 dark:border-gray-600 dark:bg-gray-700/50 dark:text-white dark:focus:bg-gray-700"
                            />
                        </div>

                        {{-- Discount --}}
                        <div class="col-span-1 flex items-center gap-1">
                            <input
                                type="number"
                                value="{{ $item->discount_value ?? 0 }}"
                                min="0"
                                step="0.01"
                                wire:change="updateItemField({{ $item->id }}, 'discount_value', $event.target.value)"
                                class="w-14 rounded-lg border border-gray-200 bg-gray-50 px-1.5 py-1.5 text-center text-xs transition focus:border-primary-500 focus:bg-white focus:ring-2 focus:ring-primary-500/20 dark:border-gray-600 dark:bg-gray-700/50 dark:text-white"
                            />
                            <select
                                wire:change="updateItemField({{ $item->id }}, 'discount_type', $event.target.value)"
                                class="rounded-lg border border-gray-200 bg-gray-50 px-1 py-1.5 text-xs transition focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 dark:border-gray-600 dark:bg-gray-700/50 dark:text-white"
                            >
                                <option value="percentage" @selected(($item->discount_type?->value ?? 'percentage') === 'percentage')>%</option>
                                <option value="fixed" @selected($item->discount_type?->value === 'fixed')>$</option>
                            </select>
                        </div>

                        {{-- Taxes --}}
                        <div class="col-span-2 flex flex-wrap items-center gap-1">
                            @foreach ($item->taxes as $itemTax)
                                <span class="inline-flex items-center gap-0.5 rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-200 dark:bg-emerald-900/30 dark:text-emerald-400 dark:ring-emerald-700">
                                    {{ $itemTax->tax_rate }}%
                                    <button type="button" wire:click="removeTaxFromItem({{ $itemTax->id }})" class="ml-0.5 text-emerald-400 transition hover:text-emerald-600">&times;</button>
                                </span>
                            @endforeach

                            <div x-data="{ open: false }" class="relative">
                                <button
                                    type="button"
                                    @click="open = !open"
                                    class="rounded-full border border-gray-200 px-2 py-0.5 text-xs text-gray-500 transition hover:border-primary-300 hover:bg-primary-50 hover:text-primary-600 dark:border-gray-600 dark:hover:bg-primary-900/30 dark:hover:text-primary-400"
                                >
                                    + {{ __('Taxes') }}
                                </button>
                                <div
                                    x-show="open"
                                    @click.outside="open = false"
                                    class="absolute right-0 z-50 mt-1.5 min-w-max overflow-hidden rounded-xl border border-gray-200 bg-white shadow-xl dark:border-gray-700 dark:bg-gray-800 w-3xs!"
                                >
                                    @foreach ($this->taxes as $tax)
                                        <button
                                            type="button"
                                            wire:click="addTaxToItem({{ $item->id }}, {{ $tax->id }})"
                                            @click="open = false"
                                            class="flex w-full items-center gap-2 px-3 py-2.5 text-left text-xs transition hover:bg-gray-50 dark:hover:bg-gray-700 justify-between"
                                        >
                                            <span class="font-semibold text-gray-800 dark:text-white">{{ $tax->name }}</span>
                                            <span class="rounded-full bg-gray-100 px-1.5 py-0.5 font-mono text-gray-500 dark:bg-gray-700 dark:text-gray-400">{{ $tax->rate }}%</span>
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        {{-- Total --}}
                        <div class="col-span-1 text-right">
                            <span class="text-sm font-bold text-primary-600 dark:text-primary-400">
                                ${{ number_format($item->total, 2) }}
                            </span>
                        </div>

                        {{-- Actions --}}
                        <div class="col-span-1 flex items-center justify-end gap-0.5">
                            <button
                                type="button"
                                wire:click="duplicateItem({{ $item->id }})"
                                class="rounded-lg p-1.5 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-gray-700"
                                title="{{ __('Duplicate') }}"
                            >
                                <x-heroicon-o-document-duplicate class="h-3.5 w-3.5" />
                            </button>
                            <button
                                type="button"
                                wire:click="removeItem({{ $item->id }})"
                                wire:confirm="{{ __('Delete this item?') }}"
                                class="rounded-lg p-1.5 text-gray-400 transition hover:bg-red-50 hover:text-red-500 dark:hover:bg-red-900/30"
                                title="{{ __('Delete') }}"
                            >
                                <x-heroicon-o-trash class="h-3.5 w-3.5" />
                            </button>
                        </div>
                    </div>

                    {{-- Expanded section: tax breakdown + additional details --}}
                    @if ($expandedItems[$item->id] ?? false)
                        @if ($item->taxes->isNotEmpty())
                            <div class="bg-emerald-50/60 px-12 py-2 dark:bg-emerald-900/10">
                                @foreach ($item->taxes as $itemTax)
                                    <div class="flex items-center justify-between py-0.5 text-xs text-emerald-700 dark:text-emerald-400">
                                        <span class="flex items-center gap-1.5">
                                            <x-heroicon-s-check-circle class="h-3 w-3" />
                                            {{ $itemTax->tax_name }} {{ $itemTax->tax_rate }}% {{ __('applied') }}
                                        </span>
                                        <span class="font-semibold">+${{ number_format($itemTax->tax_amount, 2) }}</span>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        <div class="grid grid-cols-2 gap-3 px-12 pb-3 pt-2">
                            <input
                                type="text"
                                value="{{ $item->detail_1 }}"
                                placeholder="{{ __('Detail 1') }}"
                                wire:change="updateItemField({{ $item->id }}, 'detail_1', $event.target.value)"
                                class="rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm placeholder-gray-400 transition focus:border-primary-500 focus:bg-white focus:ring-2 focus:ring-primary-500/20 dark:border-gray-600 dark:bg-gray-700/50 dark:text-white"
                            />
                            <input
                                type="text"
                                value="{{ $item->detail_2 }}"
                                placeholder="{{ __('Detail 2') }}"
                                wire:change="updateItemField({{ $item->id }}, 'detail_2', $event.target.value)"
                                class="rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm placeholder-gray-400 transition focus:border-primary-500 focus:bg-white focus:ring-2 focus:ring-primary-500/20 dark:border-gray-600 dark:bg-gray-700/50 dark:text-white"
                            />
                        </div>
                    @endif
                </div>
            @endforeach

            {{-- Totals bar --}}
            <div class="flex items-center justify-between border-t border-gray-200 bg-gray-50/80 px-5 py-3.5 dark:border-gray-700 dark:bg-gray-800/50">
                <span class="text-sm text-gray-500">
                    <span class="font-semibold text-gray-700 dark:text-gray-300">{{ $document->items->count() }}</span>
                    {{ __('item(s)') }}
                </span>
                <div class="flex items-center gap-5">
                    <div class="text-right">
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-400">{{ __('Subtotal') }}</p>
                        <p class="text-sm font-semibold text-gray-700 dark:text-gray-200">${{ number_format($document->subtotal, 2) }}</p>
                    </div>
                    <div class="h-8 w-px bg-gray-200 dark:bg-gray-600"></div>
                    <div class="text-right">
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-400">{{ __('VAT') }}</p>
                        <p class="text-sm font-semibold text-blue-600 dark:text-blue-400">${{ number_format($document->tax_amount, 2) }}</p>
                    </div>
                    <div class="h-8 w-px bg-gray-200 dark:bg-gray-600"></div>
                    <div class="rounded-xl bg-primary-600 px-4 py-2 shadow-sm">
                        <p class="text-xs font-medium uppercase tracking-wide text-primary-200">{{ __('Total') }}</p>
                        <p class="text-base font-bold text-white">${{ number_format($document->total, 2) }}</p>
                    </div>
                </div>
            </div>
        </div>
    @else
        {{-- Empty state --}}
        <div class="flex flex-col items-center justify-center rounded-xl border-2 border-dashed border-gray-200 py-16 dark:border-gray-700">
            <div class="mb-4 rounded-full bg-gray-100 p-4 dark:bg-gray-800">
                <x-dynamic-component :component="$emptyIconComponent" class="h-8 w-8 text-gray-400" />
            </div>
            <p class="text-sm font-semibold text-gray-600 dark:text-gray-400">{{ __('No items added') }}</p>
            <p class="mt-0.5 text-xs text-gray-400">{{ __('Search for a product or service above to get started') }}</p>
        </div>
    @endif
</div>
