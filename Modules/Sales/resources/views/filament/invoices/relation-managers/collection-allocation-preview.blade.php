@php
    /** @var \Modules\Finance\Models\CollectionAllocation $record */
    $collection = $record->collection;
@endphp

<div class="space-y-4">
    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
        <div>
            <p class="text-sm text-gray-500">{{ __('Collection') }}</p>
            <p class="font-medium">{{ $collection?->code ?? '—' }}</p>
        </div>

        <div>
            <p class="text-sm text-gray-500">{{ __('Date') }}</p>
            <p class="font-medium">{{ $collection?->collection_date?->format('d/m/Y') ?? '—' }}</p>
        </div>

        <div>
            <p class="text-sm text-gray-500">{{ __('Method') }}</p>
            <p class="font-medium">{{ $collection?->collection_method?->getLabel() ?? '—' }}</p>
        </div>

        <div>
            <p class="text-sm text-gray-500">{{ __('Status') }}</p>
            <p class="font-medium">{{ $collection?->isVoided() ? __('Voided') : __('Active') }}</p>
        </div>

        <div>
            <p class="text-sm text-gray-500">{{ __('Applied') }}</p>
            <p class="font-medium">{{ $record->amount }} {{ $collection?->currency_code ?? 'USD' }}</p>
        </div>

        <div>
            <p class="text-sm text-gray-500">{{ __('Reference') }}</p>
            <p class="font-medium">{{ filled($collection?->reference_number) ? $collection->reference_number : '—' }}</p>
        </div>
    </div>

    <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-700">
        <p class="text-sm text-gray-500">{{ __('Notes') }}</p>
        <p class="mt-1 text-sm font-medium">{{ filled($collection?->notes) ? $collection->notes : '—' }}</p>
    </div>
</div>
