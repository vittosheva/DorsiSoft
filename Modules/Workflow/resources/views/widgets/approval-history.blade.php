@php
    // Compatible con Widget Livewire y Vista simple
    $records = isset($this) && method_exists($this, 'getApprovalRecords')
        ? $this->getApprovalRecords()
        : collect($approvalHistory ?? []);
    $shouldShow = isset($this) && method_exists($this, 'shouldShowWidget')
        ? $this->shouldShowWidget()
        : true;
@endphp

<div>
    @if ($shouldShow && $records->isNotEmpty())
        <x-filament::section>
            <x-slot name="heading">
                {{ __('Approval status') }}
            </x-slot>

            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead>
                        <tr class="border-b border-gray-200 dark:border-white/10">
                            <th class="py-2 pr-4 font-medium text-gray-500 dark:text-gray-400">{{ __('Flow') }}</th>
                            <th class="py-2 pr-4 font-medium text-gray-500 dark:text-gray-400">{{ __('Step') }}</th>
                            <th class="py-2 pr-4 font-medium text-gray-500 dark:text-gray-400">{{ __('Decision') }}</th>
                            <th class="py-2 pr-4 font-medium text-gray-500 dark:text-gray-400">{{ __('Approver') }}</th>
                            <th class="py-2 pr-4 font-medium text-gray-500 dark:text-gray-400">{{ __('Date') }}</th>
                            <th class="py-2 font-medium text-gray-500 dark:text-gray-400">{{ __('Notes') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($records as $record)
                            <tr class="border-b border-gray-100 dark:border-white/5 {{ $record->deleted_at ? 'opacity-40' : '' }}">
                                <td class="py-2 pr-4 text-gray-900 dark:text-white font-mono text-xs">
                                    {{ $record->flow_key }}
                                </td>
                                <td class="py-2 pr-4 text-gray-900 dark:text-white font-mono text-xs">
                                    {{ $record->step }}
                                </td>
                                <td class="py-2 pr-4">
                                    <x-filament::badge :color="$record->decision->getColor()">
                                        {{ $record->decision->getLabel() }}
                                        @if ($record->deleted_at)
                                            ({{ __('Reset') }})
                                        @endif
                                    </x-filament::badge>
                                </td>
                                <td class="py-2 pr-4 text-gray-700 dark:text-gray-300">
                                    {{ $record->approver?->name ?? '—' }}
                                </td>
                                <td class="py-2 pr-4 text-gray-500 dark:text-gray-400 text-xs">
                                    {{ $record->decided_at?->format('d/m/Y H:i') ?? '—' }}
                                </td>
                                <td class="py-2 text-gray-500 dark:text-gray-400">
                                    {{ $record->notes ?? '—' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-filament::section>
    @endif
</div>
