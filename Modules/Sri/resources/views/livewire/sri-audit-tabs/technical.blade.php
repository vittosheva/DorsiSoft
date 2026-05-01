@if ($exchanges === [])
    <div class="rounded-xl border border-dashed border-gray-300 p-6 text-sm text-gray-500 dark:border-white/10 dark:text-gray-400">{{ __('This document does not yet have any recorded technical interactions.') }}</div>
@else
    <div class="space-y-3">
        @foreach ($exchanges as $exchange)
            <div class="rounded-xl border border-gray-200 p-4 dark:border-white/10">
                <div class="flex flex-wrap items-center gap-2">
                    <x-filament::badge :color="$exchange['status_color']">
                        {{ $exchange['status'] }}
                    </x-filament::badge>
                    <div class="text-sm font-semibold text-gray-900 dark:text-white">{{ $exchange['service'] }} / {{ $exchange['operation'] }}</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ $exchange['created_at'] }}</div>
                </div>

                <div class="mt-3 grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                    <div>
                        <div class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Environment') }}</div>
                        <div class="mt-1 text-sm text-gray-700 dark:text-gray-300">{{ $exchange['environment'] }}</div>
                    </div>
                    <div>
                        <div class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Endpoint') }}</div>
                        <div class="mt-1 break-all text-xs text-gray-700 dark:text-gray-300">{{ $exchange['endpoint'] }}</div>
                    </div>
                    <div>
                        <div class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Duration') }}</div>
                        <div class="mt-1 text-sm text-gray-700 dark:text-gray-300">{{ $exchange['duration'] }}</div>
                    </div>
                    <div>
                        <div class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('User') }}</div>
                        <div class="mt-1 text-sm text-gray-700 dark:text-gray-300">{{ $exchange['user'] }}</div>
                    </div>
                </div>

                @if ($exchange['error_message'])
                    <div class="mt-3 rounded-lg bg-danger-50 p-3 text-sm text-danger-700 dark:bg-danger-500/10 dark:text-danger-400">{{ $exchange['error_message'] }}</div>
                @endif

                @if (filled($exchange['sri_error_detail'] ?? null))
                    <div class="mt-3 rounded-lg bg-danger-50 p-3 text-sm text-danger-700 dark:bg-danger-500/10 dark:text-danger-400">
                        <div class="font-semibold">{{ __('SRI Detail') }}</div>
                        <div class="mt-1">{{ $exchange['sri_error_detail'] }}</div>
                    </div>
                @endif

                <details class="mt-3 rounded-lg border border-gray-200 p-3 dark:border-white/10">
                    <summary class="cursor-pointer text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Response Summary') }}</summary>
                    <pre class="mt-3 overflow-x-auto whitespace-pre-wrap break-all text-xs text-gray-600 dark:text-gray-300">{{ $exchange['response_summary'] }}</pre>
                </details>
            </div>
        @endforeach
    </div>
@endif