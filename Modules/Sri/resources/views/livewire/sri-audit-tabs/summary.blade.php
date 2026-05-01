<div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
    @foreach ($summaryItems as $item)
        <div class="rounded-xl border border-gray-200 p-3 dark:border-white/10">
            <div class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ $item['label'] }}</div>
            <div @class([
                'mt-2 text-sm text-gray-700 dark:text-gray-300',
                'break-all font-mono text-xs' => $item['monospace'],
                'font-semibold text-gray-900 dark:text-white' => ! $item['monospace'],
            ])>{{ $item['value'] }}</div>
        </div>
    @endforeach

    <div class="rounded-xl border border-gray-200 p-3 md:col-span-2 xl:col-span-3 dark:border-white/10">
        <div class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Latest error') }}</div>
        <div class="mt-2 text-sm text-danger-700 dark:text-danger-400">{{ $latestError }}</div>
    </div>
</div>