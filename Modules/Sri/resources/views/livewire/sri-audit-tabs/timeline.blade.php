@if ($events === [])
    <div class="rounded-xl border border-dashed border-gray-300 p-6 text-sm text-gray-500 dark:border-white/10 dark:text-gray-400">{{ __('This document does not yet have a recorded SRI history.') }}</div>
@else
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead>
                <tr class="border-b border-gray-200 dark:border-white/10">
                    <th class="py-2 pr-4 font-medium text-gray-500 dark:text-gray-400" width="15%">{{ __('Event') }}</th>
                    <th class="py-2 pr-4 font-medium text-gray-500 dark:text-gray-400" width="15%">{{ __('Status') }}</th>
                    <th class="py-2 pr-4 font-medium text-gray-500 dark:text-gray-400" width="13%">{{ __('User') }}</th>
                    <th class="py-2 pr-4 font-medium text-gray-500 dark:text-gray-400" width="13%">{{ __('Date') }}</th>
                    <th class="py-2 font-medium text-gray-500 dark:text-gray-400" width="44%">{{ __('Details') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($events as $event)
                    <tr class="border-b border-gray-100 dark:border-white/5">
                        <td class="py-2 pr-4">
                            <x-filament::badge :color="$event['color']">
                                {{ $event['label'] }}
                            </x-filament::badge>
                        </td>
                        <td class="py-2 pr-4 font-mono text-xs text-gray-500 dark:text-gray-400">{{ $event['status_transition'] }}</td>
                        <td class="py-2 pr-4 text-gray-700 dark:text-gray-300">{{ $event['user'] }}</td>
                        <td class="py-2 pr-4 text-xs text-gray-500 dark:text-gray-400">{{ $event['created_at'] }}</td>
                        <td @class([
                            'py-2 text-xs text-gray-500 dark:text-gray-400',
                            'text-danger-600 dark:text-danger-400' => $event['detail_is_error'],
                        ])>
                            @foreach ($event['detail_lines'] as $detailLine)
                                <div class="break-all">{{ $detailLine }}</div>
                            @endforeach
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif