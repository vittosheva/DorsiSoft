<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament::section>
            <x-slot name="heading">{{ __('Filters') }}</x-slot>
            {{ $this->filtersForm }}
            <div class="mt-4 flex justify-end">
                <x-filament::button wire:click="applyFilters">
                    {{ __('Process') }}
                </x-filament::button>
            </div>
        </x-filament::section>

        @php $entries = $this->getKardexEntries(); @endphp

        @if ($this->productId === null || $this->warehouseId === null)
            <x-filament::section>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    {{ __('Select a product and warehouse to view the kardex.') }}
                </p>
            </x-filament::section>
        @elseif ($entries->isEmpty())
            <x-filament::section>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    {{ __('No movements found for the selected filters.') }}
                </p>
            </x-filament::section>
        @else
            <x-filament::section>
                <x-slot name="heading">{{ __('Kardex Ledger') }}</x-slot>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm divide-y divide-gray-200 dark:divide-gray-700">
                        <thead>
                            <tr class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                <th class="px-3 py-2 text-left">{{ __('Date') }}</th>
                                <th class="px-3 py-2 text-left">{{ __('Reference') }}</th>
                                <th class="px-3 py-2 text-left">{{ __('Document') }}</th>
                                <th class="px-3 py-2 text-left">{{ __('Lot') }}</th>
                                <th class="px-3 py-2 text-right">{{ __('In Qty') }}</th>
                                <th class="px-3 py-2 text-right">{{ __('Out Qty') }}</th>
                                <th class="px-3 py-2 text-right">{{ __('Unit Cost') }}</th>
                                <th class="px-3 py-2 text-right">{{ __('Balance Qty') }}</th>
                                <th class="px-3 py-2 text-right">{{ __('Avg. Cost') }}</th>
                                <th class="px-3 py-2 text-right">{{ __('Balance Value') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @foreach ($entries as $entry)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                    <td class="px-3 py-2 whitespace-nowrap">
                                        {{ \Illuminate\Support\Carbon::parse($entry['movement_date'])->toFormattedDateString() }}
                                    </td>
                                    <td class="px-3 py-2 font-mono text-xs">
                                        {{ $entry['reference_code'] ?? '—' }}
                                    </td>
                                    <td class="px-3 py-2">{{ $entry['document_type'] }}</td>
                                    <td class="px-3 py-2 text-gray-500">{{ $entry['lot_code'] ?? '—' }}</td>
                                    <td class="px-3 py-2 text-right @if($entry['in_quantity'] > 0) text-success-600 dark:text-success-400 @endif">
                                        {{ $entry['in_quantity'] > 0 ? number_format($entry['in_quantity'], 2) : '' }}
                                    </td>
                                    <td class="px-3 py-2 text-right @if($entry['out_quantity'] > 0) text-danger-600 dark:text-danger-400 @endif">
                                        {{ $entry['out_quantity'] > 0 ? number_format($entry['out_quantity'], 2) : '' }}
                                    </td>
                                    <td class="px-3 py-2 text-right">
                                        {{ number_format($entry['unit_cost'], 2) }}
                                    </td>
                                    <td class="px-3 py-2 text-right font-semibold">
                                        {{ number_format($entry['balance_quantity'], 2) }}
                                    </td>
                                    <td class="px-3 py-2 text-right">
                                        {{ number_format($entry['average_cost'], 4) }}
                                    </td>
                                    <td class="px-3 py-2 text-right font-semibold">
                                        {{ number_format($entry['balance_value'], 2) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="font-semibold text-gray-700 dark:text-gray-300 border-t-2 border-gray-300 dark:border-gray-600">
                                <td colspan="4" class="px-3 py-2 text-right text-xs uppercase">{{ __('Totals') }}</td>
                                <td class="px-3 py-2 text-right text-success-700 dark:text-success-400">
                                    {{ number_format($entries->sum('in_quantity'), 2) }}
                                </td>
                                <td class="px-3 py-2 text-right text-danger-700 dark:text-danger-400">
                                    {{ number_format($entries->sum('out_quantity'), 2) }}
                                </td>
                                <td class="px-3 py-2"></td>
                                <td class="px-3 py-2 text-right">
                                    {{ number_format($entries->last()['balance_quantity'] ?? 0, 2) }}
                                </td>
                                <td class="px-3 py-2"></td>
                                <td class="px-3 py-2 text-right">
                                    {{ number_format($entries->last()['balance_value'] ?? 0, 2) }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </x-filament::section>
        @endif
    </div>
</x-filament-panels::page>
