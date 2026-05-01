@php
    /** @var \Modules\Sales\Models\Invoice $record */
@endphp

<div class="space-y-4">
    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
        <div>
            <p class="text-sm text-gray-500">{{ __('Code') }}</p>
            <p class="font-medium">{{ $record->code ?? '—' }}</p>
        </div>

        <div>
            <p class="text-sm text-gray-500">{{ __('Issue Date') }}</p>
            <p class="font-medium">{{ $record->issue_date?->format('d/m/Y') ?? '—' }}</p>
        </div>

        <div>
            <p class="text-sm text-gray-500">{{ __('Due Date') }}</p>
            <p class="font-medium">{{ $record->due_date?->format('d/m/Y') ?? '—' }}</p>
        </div>

        <div>
            <p class="text-sm text-gray-500">{{ __('Status') }}</p>
            <p class="font-medium">{{ $record->status?->getLabel() ?? '—' }}</p>
        </div>

        <div>
            <p class="text-sm text-gray-500">{{ __('SRI Status') }}</p>
            <p class="font-medium">{{ $record->electronic_status?->getLabel() ?? '—' }}</p>
        </div>

        <div>
            <p class="text-sm text-gray-500">{{ __('Total') }}</p>
            <p class="font-medium">{{ $record->total }} {{ $record->currency_code }}</p>
        </div>

        <div>
            <p class="text-sm text-gray-500">{{ __('Paid') }}</p>
            <p class="font-medium">{{ $record->paid_amount }} {{ $record->currency_code }}</p>
        </div>

        <div>
            <p class="text-sm text-gray-500">{{ __('Credited') }}</p>
            <p class="font-medium">{{ $record->credited_amount }} {{ $record->currency_code }}</p>
        </div>
    </div>

    <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-700">
        <p class="text-sm text-gray-500">{{ __('Notes') }}</p>
        <p class="mt-1 text-sm font-medium">{{ filled($record->notes) ? $record->notes : '—' }}</p>
    </div>
</div>
