<div class="space-y-3">
    @error('pendingItems')
        <div class="rounded-xl border border-danger-200 bg-danger-50 px-4 py-3 text-sm font-medium text-danger-700 dark:border-danger-800 dark:bg-danger-950/30 dark:text-danger-300">
            {{ $message }}
        </div>
    @enderror

    {{-- Product search bar --}}
    @if (! $isReadOnly)
        @include('sales::livewire.partials.product-search')
    @endif

    {{-- Items list --}}
    @if (count($pendingItems) > 0)
        <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900/40">
            <div class="overflow-x-auto">
                <div class="min-w-260">
                    {{-- Column headers --}}
                    <div class="grid grid-cols-12 gap-3 bg-primary-900 px-5 py-3.5 text-sm font-semibold uppercase tracking-[0.14em]- text-gray-100 dark:bg-gray-950">
                        <div class="col-span-3">{{ __('Description') }}</div>
                        <div class="col-span-1 text-center">{{ __('Quantity') }}</div>
                        <div class="col-span-2 text-center">{{ __('Price') }}</div>
                        <div class="col-span-2 text-center">{{ __('Discount amount') }}</div>
                        <div class="col-span-2 text-center">{{ __('Taxes') }}</div>
                        <div class="col-span-1 text-right">{{ __('Total') }}</div>
                        <div class="col-span-1"></div>
                    </div>

                    @foreach ($pendingItems as $item)
                        <div
                            wire:key="item-{{ $item['_key'] }}"
                            @class([
                                'border-b border-gray-200/80 last:border-0 dark:border-gray-700/60',
                                'bg-white dark:bg-gray-800/70' => $loop->odd,
                                'bg-gray-50/60 dark:bg-gray-800/50' => $loop->even,
                            ])
                        >
                            {{-- Main item row --}}
                            <div class="grid grid-cols-12 items-center gap-3 px-5 py-3.5 transition-colors hover:bg-gray-50 dark:hover:bg-gray-700/25">
                                {{-- Expand toggle + description --}}
                                <div class="col-span-3 flex items-center gap-2.5">
                                    <button
                                        type="button"
                                        wire:click="toggleExpand('{{ $item['_key'] }}')"
                                        class="shrink-0 rounded-md p-1 text-gray-500 transition hover:bg-gray-100 hover:text-gray-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500/60 dark:text-gray-300 dark:hover:bg-gray-700 dark:hover:text-white"
                                    >
                                        @if ($expandedItems[$item['_key']] ?? false)
                                            <x-heroicon-s-chevron-up class="h-5 w-5" />
                                        @else
                                            <x-heroicon-s-chevron-down class="h-5 w-5" />
                                        @endif
                                    </button>
                                    <div class="min-w-0 flex-1">
                                        <input
                                            type="text"
                                            value="{{ $item['description'] ?? $item['product_name'] }}"
                                            wire:change="updateItemField('{{ $item['_key'] }}', 'description', $event.target.value)"
                                            @disabled($isReadOnly)
                                            class="w-full border-0 bg-transparent p-0 text-sm font-semibold text-gray-900 focus:outline-none focus:ring-0 dark:text-white"
                                        />
                                        @if ($item['product_code'])
                                            <span class="mt-1 inline-flex items-center rounded-md bg-gray-100 px-2 py-0.5 font-mono text-[11px] font-medium text-gray-700 dark:bg-gray-700/80 dark:text-gray-200">
                                                {{ $item['product_code'] }}
                                            </span>
                                        @endif
                                    </div>
                                </div>

                                {{-- Quantity --}}
                                <div class="col-span-1">
                                    <input
                                        type="number"
                                        name="quantity_{{ $item['_key'] }}"
                                        value="{{ $item['quantity'] }}"
                                        min="0.01"
                                        step="0.01"
                                        wire:change="updateItemField('{{ $item['_key'] }}', 'quantity', $event.target.value)"
                                        @disabled($isReadOnly)
                                        class="w-full rounded-lg border border-gray-300 bg-white px-2 py-2 text-center text-sm font-medium text-gray-800 transition focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 dark:border-gray-600 dark:bg-gray-700/70 dark:text-white"
                                    />
                                </div>

                                {{-- Unit price --}}
                                <div class="col-span-2 flex items-center gap-1.5">
                                    <span class="shrink-0 text-sm font-semibold text-gray-500 dark:text-gray-300">$</span>
                                    <input
                                        type="number"
                                        name="unit_price_{{ $item['_key'] }}"
                                        value="{{ $item['unit_price'] }}"
                                        min="0"
                                        step="0.01"
                                        wire:change="updateItemField('{{ $item['_key'] }}', 'unit_price', $event.target.value)"
                                        @disabled($isReadOnly)
                                        class="w-full rounded-lg border border-gray-300 bg-white px-2 py-2 text-right text-sm font-medium text-gray-800 transition focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 dark:border-gray-600 dark:bg-gray-700/70 dark:text-white"
                                    />
                                </div>

                                {{-- Discount --}}
                                <div class="col-span-2 flex items-center gap-1.5 justify-center">
                                    <input
                                        type="number"
                                        value="{{ $item['discount_value'] ?? 0 }}"
                                        min="0"
                                        step="0.01"
                                        wire:change="updateItemField('{{ $item['_key'] }}', 'discount_value', $event.target.value)"
                                        @disabled($isReadOnly)
                                        class="w-full rounded-lg border border-gray-300 bg-white px-1.5 py-2 text-right text-sm font-medium text-gray-800 transition focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 dark:border-gray-600 dark:bg-gray-700/70 dark:text-white"
                                    />
                                    <select
                                        wire:change="updateItemField('{{ $item['_key'] }}', 'discount_type', $event.target.value)"
                                        @disabled($isReadOnly)
                                        class="rounded-lg border border-gray-300 bg-white px-1.5 py-2 text-xs font-semibold text-gray-700 transition focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 dark:border-gray-600 dark:bg-gray-700/70 dark:text-white"
                                    >
                                        <option value="percentage" @selected(($item['discount_type'] ?? 'percentage') === 'percentage')>%</option>
                                        <option value="fixed" @selected(($item['discount_type'] ?? '') === 'fixed')>$</option>
                                    </select>
                                </div>

                                {{-- Taxes --}}
                                <div class="col-span-2 flex flex-col items-start gap-1.5">
                                    <div class="flex w-full flex-wrap items-center gap-1.5 justify-center">
                                        @foreach ($item['taxes'] as $itemTax)
                                            @php($isFixedTax = ($itemTax['tax_calculation_type'] ?? 'percentage') === 'fixed')
                                            <span wire:key="tax-{{ $itemTax['_key'] }}" class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-200 dark:bg-emerald-900/25 dark:text-emerald-300 dark:ring-emerald-700/70">
                                                {{ $itemTax['tax_type'] }} {{ $isFixedTax ? '$'.number_format((float) $itemTax['tax_rate'], 2) : number_format((float) $itemTax['tax_rate'], 2).'%' }}
                                                @if (! $isReadOnly)
                                                    <button type="button" wire:click="removeTaxFromItem('{{ $item['_key'] }}', '{{ $itemTax['_key'] }}')" class="text-emerald-500 transition hover:text-emerald-700 dark:text-emerald-300 dark:hover:text-emerald-100">&times;</button>
                                                @endif
                                            </span>
                                        @endforeach
                                    </div>

                                    @if (! $isReadOnly)
                                        <div x-data="{ open: false }" class="relative">
                                            <button
                                                type="button"
                                                @click="open = !open"
                                                class="rounded-full border border-gray-300 px-2.5 py-1 text-xs font-medium text-gray-700 transition hover:border-primary-300 hover:bg-primary-50 hover:text-primary-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500/40 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-primary-900/30 dark:hover:text-primary-200"
                                            >
                                                + {{ __('Taxes') }}
                                            </button>
                                            <div
                                                x-show="open"
                                                @click.outside="open = false"
                                                class="absolute right-0 z-50 mt-1.5 min-w-max overflow-hidden rounded-xl border border-gray-200 bg-white shadow-xl dark:border-gray-700 dark:bg-gray-800 w-3xs!"
                                            >
                                                @foreach ($this->taxes as $tax)
                                                    @php($taxType = $tax->type instanceof \BackedEnum ? $tax->type->value : (string) $tax->type)
                                                    @php($isDisabled = ! $this->canAddPendingTaxType($item['_key'], $taxType))
                                                    <button
                                                        type="button"
                                                        wire:click="addTaxToItem('{{ $item['_key'] }}', {{ $tax->id }})"
                                                        @click="open = false"
                                                        @disabled($isDisabled)
                                                        class="flex w-full items-center gap-2 px-3 py-2.5 text-left text-xs transition justify-between {{ $isDisabled ? 'cursor-not-allowed bg-gray-50 text-gray-400 opacity-70 dark:bg-gray-900/40 dark:text-gray-500' : 'hover:bg-gray-50 dark:hover:bg-gray-700' }}"
                                                    >
                                                        <span class="font-semibold {{ $isDisabled ? 'text-gray-400 dark:text-gray-500' : 'text-gray-800 dark:text-white' }}">{{ $tax->name }}</span>
                                                        <span class="rounded-full px-1.5 py-0.5 font-mono {{ $isDisabled ? 'bg-gray-200 text-gray-500 dark:bg-gray-800 dark:text-gray-400' : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300' }}">{{ $taxType }} · {{ (($tax->calculation_type?->value ?? $tax->calculation_type) === 'fixed') ? '$'.number_format((float) $tax->rate, 2) : number_format((float) $tax->rate, 2).'%' }}</span>
                                                    </button>
                                                @endforeach
                                            </div>
                                        </div>

                                        @if ($itemTaxErrors[$item['_key']] ?? false)
                                            <div class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs font-medium text-amber-800 dark:border-amber-700/60 dark:bg-amber-900/20 dark:text-amber-200">
                                                {{ $itemTaxErrors[$item['_key']] }}
                                            </div>
                                        @endif
                                    @endif
                                </div>

                                {{-- Total --}}
                                <div class="col-span-1 text-right">
                                    <span class="text-base font-bold text-primary-700 dark:text-primary-300">
                                        {{ $currencySymbol }}{{ number_format($item['total'], 2) }}
                                    </span>
                                </div>

                                {{-- Actions --}}
                                <div class="col-span-1 flex items-center justify-end gap-1">
                                    @if (! $isReadOnly)
                                        <button
                                            type="button"
                                            wire:click="duplicateItem('{{ $item['_key'] }}')"
                                            x-tooltip="{ content: '{{ __('Duplicate') }}', theme: $store.theme }"
                                            class="rounded-lg p-1.5 text-gray-500 transition hover:bg-gray-100 hover:text-gray-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500/50 dark:text-gray-300 dark:hover:bg-gray-700 dark:hover:text-white"
                                            title="{{ __('Duplicate') }}"
                                        >
                                            <x-heroicon-o-document-duplicate class="h-5 w-5" />
                                        </button>
                                        <button
                                            type="button"
                                            wire:click="removeItem('{{ $item['_key'] }}')"
                                            {{-- wire:confirm="{{ __('Delete this item?') }}" --}}
                                            x-tooltip="{ content: '{{ __('Delete') }}', theme: $store.theme }"
                                            class="rounded-lg p-1.5 text-gray-500 transition hover:bg-red-50 hover:text-red-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-red-500/50 dark:text-gray-300 dark:hover:bg-red-900/30"
                                            title="{{ __('Delete') }}"
                                        >
                                            <x-heroicon-o-trash class="h-5 w-5" />
                                        </button>
                                    @endif
                                </div>
                            </div>

                            {{-- Taxes breakdown (always visible) --}}
                            <div class="bg-emerald-50/70 px-7 py-2.5 dark:bg-emerald-900/10">
                                @if (count($item['taxes']) > 0)
                                    @foreach ($item['taxes'] as $itemTax)
                                        @php($isFixedTax = ($itemTax['tax_calculation_type'] ?? 'percentage') === 'fixed')
                                        <div class="flex items-center justify-between py-0.5 text-sm font-medium text-emerald-800 dark:text-emerald-300">
                                            <span class="flex items-center gap-4">
                                                <x-heroicon-s-check-circle class="h-4 w-4" />
                                                {{ $itemTax['tax_name'] }} · {{ $itemTax['tax_type'] }} · {{ $isFixedTax ? '$'.number_format((float) $itemTax['tax_rate'], 2) : number_format((float) $itemTax['tax_rate'], 2).'%' }}
                                            </span>
                                            <span class="font-semibold">+{{ $currencySymbol }}{{ number_format($itemTax['tax_amount'], 2) }}</span>
                                        </div>
                                    @endforeach
                                @else
                                    <div class="py-0.5 text-sm font-medium text-emerald-800/70 dark:text-emerald-300/70">
                                        {{ __('No taxes applied') }}
                                    </div>
                                @endif
                            </div>

                            {{-- Additional details (collapsed by default) --}}
                            @if ($expandedItems[$item['_key']] ?? false)
                                <div class="grid grid-cols-2 gap-3 px-12 pb-3.5 pt-2.5">
                                    <input
                                        type="text"
                                        value="{{ $item['detail_1'] }}"
                                        placeholder="{{ __('Detail 1') }}"
                                        wire:change="updateItemField('{{ $item['_key'] }}', 'detail_1', $event.target.value)"
                                        @disabled($isReadOnly)
                                        class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-800 placeholder-gray-500 transition focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 dark:border-gray-600 dark:bg-gray-700/70 dark:text-white dark:placeholder-gray-300"
                                    />
                                    <input
                                        type="text"
                                        value="{{ $item['detail_2'] }}"
                                        placeholder="{{ __('Detail 2') }}"
                                        wire:change="updateItemField('{{ $item['_key'] }}', 'detail_2', $event.target.value)"
                                        @disabled($isReadOnly)
                                        class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-800 placeholder-gray-500 transition focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 dark:border-gray-600 dark:bg-gray-700/70 dark:text-white dark:placeholder-gray-300"
                                    />
                                </div>
                            @endif
                        </div>
                    @endforeach

                    {{-- Totals bar --}}
                    <div class="flex items-center justify-between border-t border-gray-200 bg-gray-50/90 px-5 py-4 dark:border-gray-700 dark:bg-gray-800/70">
                        <span class="text-sm font-medium text-gray-600 dark:text-gray-300">
                            <span class="font-semibold text-gray-800 dark:text-white">{{ count($pendingItems) }}</span>
                            {{ __('item(s)') }}
                        </span>
                        <div class="flex items-center gap-5">
                            <div class="text-right">
                                <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Subtotal') }}</p>
                                <p class="text-lg font-semibold text-gray-800 dark:text-gray-100">{{ $currencySymbol }}{{ number_format($this->pendingTotals['subtotal'], 2) }}</p>
                            </div>
                            <div class="h-10 w-px bg-gray-300 dark:bg-gray-600"></div>
                            <div class="text-right">
                                <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">ICE</p>
                                <p class="text-lg font-semibold text-amber-700 dark:text-amber-300">{{ $currencySymbol }}{{ number_format($this->pendingTotals['ice_amount'], 2) }}</p>
                            </div>
                            <div class="h-10 w-px bg-gray-300 dark:bg-gray-600"></div>
                            <div class="text-right">
                                <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">IVA</p>
                                <p class="text-lg font-semibold text-blue-700 dark:text-blue-300">{{ $currencySymbol }}{{ number_format($this->pendingTotals['iva_amount'], 2) }}</p>
                            </div>
                            <div class="h-10 w-px bg-gray-300 dark:bg-gray-600"></div>
                            <div class="text-right">
                                <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Taxes') }}</p>
                                <p class="text-lg font-semibold text-blue-700 dark:text-blue-300">{{ $currencySymbol }}{{ number_format($this->pendingTotals['tax_amount'], 2) }}</p>
                            </div>
                            <div class="h-10 w-px bg-gray-300 dark:bg-gray-600"></div>
                            <div class="text-right rounded-xl bg-primary-700 px-5 py-2.5 shadow-sm dark:bg-primary-800">
                                <p class="text-[11px] font-semibold uppercase tracking-wide text-primary-100">{{ __('Total') }}</p>
                                <p class="text-3xl font-bold text-white">{{ $currencySymbol }}{{ number_format($this->pendingTotals['total'], 2) }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @else
        {{-- Empty state --}}
        <div class="flex flex-col items-center justify-center rounded-xl border-2 border-dashed border-gray-200 py-16 dark:border-gray-700">
            <div class="mb-4 rounded-full bg-gray-100 p-4 dark:bg-gray-800">
                <x-heroicon-o-document-text class="h-8 w-8 text-gray-400" />
            </div>
            <p class="text-base font-semibold text-gray-600 dark:text-gray-400">{{ __('No items added') }}</p>
            <p class="mt-0.5 text-sm text-gray-400">{{ __('Search for a product or service above to get started') }}</p>
        </div>
    @endif
</div>
