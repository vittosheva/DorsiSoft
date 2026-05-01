<div class="space-y-3">
    <div class="grid gap-3 md:grid-cols-2">
        <div class="rounded-xl border border-gray-200 p-3 dark:border-white/10">
            <div class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Signed XML path') }}</div>
            <div class="mt-2 break-all font-mono text-xs text-gray-700 dark:text-gray-300">{{ $xmlPath }}</div>
        </div>
        <div class="rounded-xl border border-gray-200 p-3 dark:border-white/10">
            <div class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Authorized XML path') }}</div>
            <div class="mt-2 break-all font-mono text-xs text-gray-700 dark:text-gray-300">{{ $ridePath }}</div>
        </div>
    </div>

    <details class="rounded-xl border border-gray-200 p-3 dark:border-white/10">
        <summary class="cursor-pointer text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Preview of sent XML') }}</summary>
        <pre class="mt-3 max-h-96 overflow-auto whitespace-pre-wrap break-all text-xs text-gray-600 dark:text-gray-300">{{ $xmlPreview }}</pre>
    </details>
</div>